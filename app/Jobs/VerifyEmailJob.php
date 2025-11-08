<?php

namespace App\Jobs;

use App\Models\VerifyBatch;
use App\Models\VerifyResult;
use App\Services\MailboxVerifier;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class VerifyEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected string $batchId;
    protected string $email;

    /**
     * Create a new job instance.
     */
    public function __construct(string $batchId, string $email)
    {
        $this->batchId = $batchId;
        $this->email = $email;
    }

    /**
     * Execute the job.
     */
    public function handle(MailboxVerifier $verifier): void
    {
        $result = [
            'status' => 'unknown',
            'reason' => 'uninitialized',
            'mx' => null,
            'response' => null,
        ];

        try {
            $result = $verifier->verify($this->email);
        } catch (Throwable $e) {
            Log::error("VerifyEmailJob failed for {$this->email}: " . $e->getMessage());
            $result['status'] = 'error';
            $result['reason'] = 'exception';
            $result['response'] = $e->getMessage();
        }

        // Save result
        VerifyResult::create([
            'batch_id' => $this->batchId,
            'email' => $this->email,
            'status' => $result['status'],
            'reason' => $result['reason'],
            'mx' => $result['mx'],
            'response' => $result['response'],
            'checked_at' => now(),
        ]);

        // Update processed count
        $batch = VerifyBatch::find($this->batchId);
        if ($batch) {
            $batch->increment('processed');

            // mark completed if all done
            if ($batch->processed >= $batch->total) {
                $batch->status = 'completed';
                $batch->save();
            }
        }
    }
}
