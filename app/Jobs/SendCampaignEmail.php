<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use App\Models\Campaign;
use App\Models\DebugLog;
use Illuminate\Support\Facades\Log;

class SendCampaignEmail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $to;
    public $campaignId;
    public $smtp;

    public function __construct($to, $campaignId, $smtp)
    {
        $this->to = $to;
        $this->campaignId = $campaignId;
        $this->smtp = $smtp;
    }

    public function handle()
    {
        $campaign = Campaign::find($this->campaignId);
        if (!$campaign) return;

        $campaign = $campaign->fresh();

        if ($campaign->status !== 'running') {
            Log::info("Campaign {$this->campaignId} is paused/stopped — skipping {$this->to}");
            return;
        }

        // Decode fields
        $sentEmails = is_array($campaign->sent_emails) ? $campaign->sent_emails : (json_decode($campaign->sent_emails, true) ?? []);
        $failedEmails = is_array($campaign->failed_emails) ? $campaign->failed_emails : (json_decode($campaign->failed_emails, true) ?? []);
        $processedEmails = is_array($campaign->processed_emails) ? $campaign->processed_emails : (json_decode($campaign->processed_emails, true) ?? []);

        if (in_array($this->to, $processedEmails)) {
            Log::info("Email {$this->to} already processed — skipping");
            return;
        }

        // Delay per email
        $delay = $campaign->delay ?? 1;
        sleep($delay);

        $debugFile = storage_path('app/temp_debug_' . uniqid() . '.log');
        $mail = new PHPMailer(true);
        $success = false;
        $debugLog = '';
        $errorMessage = '';

        try {
            $mail->SMTPDebug = 2;
            $mail->Debugoutput = function ($str, $level) use ($debugFile) {
                $line = trim($str);
                file_put_contents($debugFile, $line . "\n", FILE_APPEND | LOCK_EX);
                Log::debug("SMTP DEBUG: " . $line);
            };

            $mail->isSMTP();
            $mail->Host = $this->smtp['host'];
            $mail->Port = $this->smtp['port'];
            $mail->SMTPAuth = true;
            $mail->Username = $this->smtp['username'];
            $mail->Password = $this->smtp['password'];
            $mail->SMTPSecure = $this->smtp['port'] == 465 ? PHPMailer::ENCRYPTION_SMTPS : PHPMailer::ENCRYPTION_STARTTLS;
            $mail->CharSet = 'UTF-8';

            $mail->setFrom($this->smtp['from_email'], $campaign->from_name);
            $mail->addAddress($this->to);
            $mail->Subject = $campaign->subject;
            $mail->isHTML(true);
            $mail->Body = $campaign->html_body;

            Log::info("SENDING EMAIL TO: {$this->to}");
            $mail->send();
            $success = true;

        } catch (Exception $e) {
            $errorMessage = $e->getMessage();
            Log::error("SMTP FAILED: " . $errorMessage);
            $debugLog = (file_exists($debugFile) ? file_get_contents($debugFile) : '') . "\nERROR: " . $errorMessage;
        }

        // === Update campaign data ===
        if ($success) {
            $campaign->increment('sent');
            $sentEmails[] = $this->to;
            $campaign->sent_emails = array_unique($sentEmails);
        } else {
            $campaign->increment('failed');
            $failedEmails[] = $this->to;
            $campaign->failed_emails = array_unique($failedEmails);
        }

        $processedEmails[] = $this->to;
        $campaign->processed_emails = array_unique($processedEmails);
        $campaign->current_index = count($processedEmails) - 1;
        $campaign->save();

        // === Save debug log ===
        $finalDebug = file_exists($debugFile) ? file_get_contents($debugFile) : $debugLog;
        if (!$success && empty($finalDebug)) {
            $finalDebug = "No SMTP debug (connection failed)";
        }

        DebugLog::create([
            'campaign_id'   => $this->campaignId,
            'email'         => $this->to,
            'smtp_username' => $this->smtp['username'],
            'status'        => $success ? 'success' : 'failed',
            'debug'         => $finalDebug,
        ]);

        if (file_exists($debugFile)) unlink($debugFile);

        // === Log to campaign ===
        $logMessage = ($success ? 'SUCCESS' : 'FAILED') .
            " | SMTP: {$this->smtp['username']} | To: {$this->to}" .
            ($success ? '' : " | Error: {$errorMessage}");
        $this->appendToCampaignLog($campaign, $logMessage);

        // === Completion check ===
        if ($campaign->sent + $campaign->failed >= $campaign->total_emails) {
            $campaign->status = 'completed';
            $campaign->save();
            $this->exportFailedEmailsFile($campaign);
        }
    }

    private function appendToCampaignLog($campaign, $message)
    {
        $log = is_array($campaign->log) ? $campaign->log : (json_decode($campaign->log, true) ?? []);
        $log[] = now()->format('H:i:s') . " | " . $message;
        if (count($log) > 500) array_shift($log);
        $campaign->log = $log;
        $campaign->save();
    }

    private function exportFailedEmailsFile($campaign)
    {
        $failed = is_array($campaign->failed_emails) ? $campaign->failed_emails : (json_decode($campaign->failed_emails, true) ?? []);
        if (empty($failed)) return;

        $content = implode("\n", $failed);
        file_put_contents(storage_path("app/failed-emails-campaign-{$campaign->id}.txt"), $content);
    }
}
