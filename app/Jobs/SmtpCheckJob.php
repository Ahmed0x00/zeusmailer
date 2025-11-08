<?php

namespace App\Jobs;

use App\Models\SmtpBatch;
use App\Models\SmtpResult;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class SmtpCheckJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public string $batchId;
    public string $combo;

    // Tunables — adjust if needed
    public int $connectTimeout = 5; // seconds
    public array $ports = [587, 465];

    /**
     * Create a new job instance.
     *
     * @param string $batchId
     * @param string $combo   // "email:password"
     */
    public function __construct(string $batchId, string $combo)
    {
        $this->batchId = $batchId;
        $this->combo = trim($combo);
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            // Load batch early
            $batch = SmtpBatch::find($this->batchId);
            if (!$batch) {
                Log::warning("SmtpCheckJob: batch {$this->batchId} not found; skipping combo.");
                return;
            }

            // Respect pause
            if ($batch->status === SmtpBatch::STATUS_PAUSED) {
                Log::info("SmtpCheckJob: batch {$this->batchId} is paused; skipping combo.");
                return;
            }

            // Parse combo
            if (!str_contains($this->combo, ':')) {
                $this->persistResult(null, null, null, 'invalid', null, 'invalid combo format');
                $this->pushToBatch(null, 'invalid', null, null, null, null);
                return;
            }

            [$emailRaw, $passwordRaw] = explode(':', $this->combo, 2);
            $email = trim($emailRaw);
            $password = trim($passwordRaw);

            if (empty($email)) {
                $this->persistResult(null, $password, null, 'invalid', null, 'empty email');
                $this->pushToBatch(null, 'invalid', null, null, null, $password);
                return;
            }

            $domain = strtolower(substr(strrchr($email, "@"), 1) ?: '');

            if ($domain === '') {
                $this->persistResult($email, $password, null, 'invalid', null, 'invalid domain');
                $this->pushToBatch($email, 'invalid', null, null, null, $password);
                return;
            }

            // Resolve SMTP host (MX -> provider map -> fallback)
            $smtpHost = $this->resolveSmtpHost($domain);

            // Try ports 587 then 465
            $found = false;
            $workingPort = null;
            $responseTime = null;
            foreach ($this->ports as $port) {
                $start = microtime(true);
                $errNo = 0;
                $errStr = '';
                // Use stream_socket_client to test TCP connect
                $socket = @stream_socket_client("tcp://{$smtpHost}:{$port}", $errNo, $errStr, $this->connectTimeout);
                if ($socket) {
                    // read banner (optional) and close
                    @stream_set_timeout($socket, $this->connectTimeout);
                    @fgets($socket, 512); // non-critical
                    @fclose($socket);
                    $responseTime = round(microtime(true) - $start, 2);
                    $found = true;
                    $workingPort = (int) $port;
                    break;
                }
                // else try next port
            }

            $status = $found ? 'success' : 'failed';
            $message = $found ? "open port {$workingPort}" : 'no responsive smtp port';

            // Persist full result row (every combo must be saved)
            $this->persistResult($email, $password, $smtpHost, $status, $responseTime, $message, $workingPort);

            // Update batch recent window + counters atomically
            $this->pushToBatch($email, $status, $smtpHost, $responseTime, $workingPort, $password, $message);

            // Check if batch is fully processed
            $batch->refresh();
            $batch->checkIfCompleted();

        } catch (Throwable $e) {
            Log::error("SmtpCheckJob exception: " . $e->getMessage(), [
                'batch' => $this->batchId,
                'combo' => $this->combo,
            ]);
            // Report error in batch recent window so UI can show it
            try {
                $this->pushToBatch(null, 'error', null, null, null, null, $e->getMessage());
            } catch (Throwable $inner) {
                Log::error("SmtpCheckJob: failed to push error to batch: " . $inner->getMessage());
            }
        }
    }

    /**
     * Persist the SmtpResult row (idempotent).
     *
     * @param string|null $email
     * @param string|null $password
     * @param string|null $smtpHost
     * @param string $status
     * @param float|null $responseTime
     * @param string|null $message
     * @param int|null $port
     * @return void
     */
    protected function persistResult(?string $email, ?string $password, ?string $smtpHost, string $status, ?float $responseTime = null, ?string $message = null, ?int $port = null): void
    {
        try {
            if ($email === null) {
                // create a placeholder row to keep counts consistent
                SmtpResult::create([
                    'batch_id' => $this->batchId,
                    'email' => null,
                    'password' => $password,
                    'smtp_host' => $smtpHost,
                    'port' => $port,
                    'provider' => null,
                    'status' => $status,
                    'response_time' => $responseTime,
                    'message' => $message,
                ]);
                return;
            }

            // update or create (idempotent): use batch_id + email as unique key
            SmtpResult::updateOrCreate(
                ['batch_id' => $this->batchId, 'email' => $email],
                [
                    'password' => $password,
                    'smtp_host' => $smtpHost,
                    'port' => $port,
                    'provider' => strtolower(substr(strrchr($email, "@"), 1) ?: ''),
                    'status' => $status,
                    'response_time' => $responseTime,
                    'message' => $message,
                ]
            );
        } catch (Throwable $e) {
            Log::error("SmtpCheckJob::persistResult failed: " . $e->getMessage(), [
                'batch' => $this->batchId,
                'email' => $email,
            ]);
        }
    }

    /**
     * Push to batch recent_results and update counters (uses SmtpBatch::pushResult)
     *
     * @param string|null $email
     * @param string $status
     * @param string|null $smtpHost
     * @param float|null $responseTime
     * @param int|null $port
     * @param string|null $password
     * @param string|null $message
     * @return void
     */
    protected function pushToBatch(?string $email, string $status, ?string $smtpHost, ?float $responseTime, ?int $port, ?string $password = null, ?string $message = null): void
    {
        try {
            $batch = SmtpBatch::find($this->batchId);
            if (!$batch)
                return;

            $entry = [
                'email' => $email,
                'password' => $password,
                'status' => $status,
                'smtp_host' => $smtpHost,
                'port' => $port,
                'response_time' => $responseTime,
                'message' => $message,
                'ts' => now()->toDateTimeString(),
            ];

            // pushResult handles locking and trimming recent window
            $batch->pushResult($entry, 1000);
        } catch (Throwable $e) {
            Log::error("SmtpCheckJob::pushToBatch failed: " . $e->getMessage(), [
                'batch' => $this->batchId,
            ]);
        }
    }

    /**
     * Resolve SMTP host using MX record and provider map fallbacks
     *
     * @param string $domain
     * @return string
     */
    protected function resolveSmtpHost(string $domain): string
    {
        try {
            $records = @dns_get_record($domain, DNS_MX);
            if (!empty($records)) {
                usort($records, fn($a, $b) => ($a['pri'] ?? 0) <=> ($b['pri'] ?? 0));
                $target = $records[0]['target'] ?? null;
                if ($target) {
                    return rtrim($target, '.');
                }
            }
        } catch (Throwable $e) {
            // ignore dns failure, fallback below
            Log::debug("MX lookup failed for {$domain}: " . $e->getMessage());
        }

        $providerMap = [
            'gmail.com' => 'smtp.gmail.com',
            'yahoo.com' => 'smtp.mail.yahoo.com',
            'outlook.com' => 'smtp.office365.com',
            'hotmail.com' => 'smtp.office365.com',
            'zoho.com' => 'smtp.zoho.com',
            // add more as needed
        ];

        if (isset($providerMap[$domain])) {
            return $providerMap[$domain];
        }

        // fallback generic
        return "mail.{$domain}";
    }
}
