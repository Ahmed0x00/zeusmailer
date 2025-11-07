<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use App\Events\SmtpTestCompleted;

class TestSmtpJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $data;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function handle()
    {
        $d = $this->data;
        $status = 'failed';
        $debugLog = '';

        try {
            $mail = new PHPMailer(true);
            $mail->isSMTP();
            $mail->Host = $d['smtp'];
            $mail->Port = $d['port'];
            $mail->SMTPAuth = true;
            $mail->Username = $d['username'];
            $mail->Password = $d['password'];
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;

            $mail->setFrom($d['from_email'], $d['from_name']);
            $mail->addAddress($d['test_email']);
            $mail->Subject = $d['subject'];
            $mail->isHTML(true);
            $mail->Body = $d['html_body'] ?? "<p>This is a test email from ZeusMailer SMTP Tester.</p>";
            $mail->AltBody = strip_tags($mail->Body);


            ob_start();
            $mail->send();
            $status = 'success';
            $debugLog = ob_get_clean();
        } catch (Exception $e) {
            $debugLog = $e->getMessage();
        }

        // Update record in DB
        DB::table('smtp_tests')
            ->where('batch_id', $d['batch_id'])
            ->where('smtp', $d['smtp'])
            ->update([
                'status' => $status,
                'debug' => $debugLog,
                'updated_at' => now(),
            ]);

        // Broadcast event (for websocket updates)
        broadcast(new SmtpTestCompleted([
            'batch_id' => $d['batch_id'],
            'smtp' => $d['smtp'],
            'username' => $d['username'],
            'from_name' => $d['from_name'],
            'subject' => $d['subject'],
            'status' => $status,
            'debug' => $debugLog,
        ]));
    }
}
