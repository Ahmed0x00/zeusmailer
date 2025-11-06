<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;
use App\Models\Campaign;

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
        if (!$campaign)
            return;

        $delay = $campaign->delay ?? 1;
        sleep($delay);

        $mail = new PHPMailer(true);
        $debugOutput = ''; // This will hold full SMTP log

        // Capture debug output
        $mail->SMTPDebug = 2;
        $mail->Debugoutput = function ($str, $level) use (&$debugOutput) {
            $debugOutput .= trim($str) . "\n";
        };

        try {
            $mail->isSMTP();
            $mail->Host = $this->smtp['host'];
            $mail->Port = $this->smtp['port'];
            $mail->SMTPAuth = true;
            $mail->Username = $this->smtp['username'];
            $mail->Password = $this->smtp['password'];
            $mail->SMTPSecure = $this->smtp['port'] == 465 ? PHPMailer::ENCRYPTION_SMTPS : PHPMailer::ENCRYPTION_STARTTLS;
            $mail->CharSet = 'UTF-8';

            // FROM NAME: Use campaign's from_name
            $fromName = $campaign->from_name;
            $mail->setFrom($this->smtp['from_email'], $fromName);

            $mail->addAddress($this->to);
            $mail->Subject = $campaign->subject;
            $mail->isHTML(true);
            $mail->Body = $campaign->html_body;

            // SEND — debug is captured here
            $mail->send();

            $campaign->increment('sent');
            $this->log("SUCCESS | SMTP: {$this->smtp['username']} | To: {$this->to}");
            $this->saveDebug($debugOutput, $campaign);

        } catch (Exception $e) {
            $campaign->increment('failed');
            $this->log("FAILED | SMTP: {$this->smtp['username']} | To: {$this->to} | Error: " . $e->getMessage());
            $this->saveDebug($debugOutput . "\nERROR: " . $e->getMessage(), $campaign);
        }
    }

    private function saveDebug($debug, $campaign)
    {
        if (empty($debug))
            return;

        $header = "\n--- EMAIL TO: {$this->to} | SMTP: {$this->smtp['username']} | " . now()->format('H:i:s') . " ---\n";
        $newLog = $header . $debug . "\n";

        // Append to existing debug_log
        $campaign->debug_log = ($campaign->debug_log ?? '') . $newLog;
        $campaign->save();
    }
    private function log($message)
    {
        $campaign = Campaign::find($this->campaignId);
        $log = $campaign->log ?? [];
        $log[] = now()->format('H:i:s') . " | " . $message;
        if (count($log) > 500)
            array_shift($log); // limit
        $campaign->log = $log;
        $campaign->save();
    }

    private function debugLog($message)
    {
        // Optional: Save full SMTP debug to file
        $file = storage_path("logs/smtp_debug_{$this->campaignId}.log");
        file_put_contents($file, $message . PHP_EOL, FILE_APPEND | LOCK_EX);
    }
}