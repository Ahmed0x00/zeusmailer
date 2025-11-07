<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use App\Models\SmtpTest;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use Illuminate\Support\Facades\Validator;

class SmtpTestController extends Controller
{
    public function index()
    {
        return view('smtp.test');
    }

    /**
     * Start a new batch: parse input lines and create pending DB rows.
     * Expects: smtp_input (lines host|port|user|pass|from_email[|from_name]),
     * test_email, from_name (optional), subject (optional)
     */
    public function start(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'smtp_input' => 'required|string',
            'test_email' => 'required|email',
            'from_name' => 'nullable|string|max:255',
            'subject' => 'nullable|string|max:255',
            'html_body' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => 'validation', 'messages' => $validator->errors()], 422);
        }

        $batchId = (string) Str::uuid();
        $lines = array_filter(array_map('trim', preg_split("/\r\n|\n|\r/", $request->smtp_input)));

        $inserted = 0;
        foreach ($lines as $line) {
            // ignore comments and empty lines
            if ($line === '' || ($line[0] ?? '') === '#')
                continue;

            $parts = array_map('trim', explode('|', $line));
            // require at least 5 parts: host|port|username|password|from_email
            if (count($parts) < 5)
                continue;

            // normalize values and provide sensible defaults
            $host = $parts[0];
            $port = is_numeric($parts[1]) ? (int) $parts[1] : 587;
            $username = $parts[2] ?? '';
            $password = $parts[3] ?? '';
            $from_email = $parts[4] ?? $username;
            $from_name_line = $parts[5] ?? null;

            SmtpTest::create([
                'batch_id' => $batchId,
                'host' => $host,
                'port' => $port,
                'username' => $username,
                'password' => $password,
                'from_email' => $from_email,
                'from_name' => $request->input('from_name', $from_name_line ?? $from_email),
                'subject' => $request->input('subject', 'SMTP Test'),
                'status' => 'pending',
                'html_body' => $request->input('html_body', '<p>This is a test message sent by ZeusMailer SMTP Tester.</p>'),
            ]);

            $inserted++;
        }

        if ($inserted === 0) {
            return response()->json(['error' => 'no_valid_smtp_lines'], 422);
        }

        return response()->json(['batch_id' => $batchId, 'inserted' => $inserted]);
    }

    /**
     * Poll results for a batch.
     * Returns results array plus progress metadata: tested_count, total_count.
     */
    public function poll($batchId)
    {
        $total = SmtpTest::where('batch_id', $batchId)->count();
        $tested = SmtpTest::where('batch_id', $batchId)->whereIn('status', ['success', 'failed'])->count();
        $results = SmtpTest::where('batch_id', $batchId)->orderBy('id')->get();

        return response()->json([
            'batch_id' => $batchId,
            'total' => $total,
            'tested' => $tested,
            'results' => $results,
        ]);
    }

    /**
     * Run the next pending SMTP test in this batch.
     * The frontend should call this repeatedly until it returns status 'done'.
     * Expects JSON { batch_id, test_email }.
     */
    public function runNext(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'batch_id' => 'required|string',
            'test_email' => 'required|email',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => 'validation', 'messages' => $validator->errors()], 422);
        }

        $batchId = $request->input('batch_id');

        // Get next pending row and lock for update to avoid duplicates in concurrent calls
        $test = SmtpTest::where('batch_id', $batchId)
            ->where('status', 'pending')
            ->orderBy('id')
            ->lockForUpdate()
            ->first();

        if (!$test) {
            // nothing left to test
            $total = SmtpTest::where('batch_id', $batchId)->count();
            $tested = SmtpTest::where('batch_id', $batchId)->whereIn('status', ['success', 'failed'])->count();

            return response()->json([
                'status' => 'done',
                'total' => $total,
                'tested' => $tested,
            ]);
        }

        // mark testing
        $test->status = 'testing';
        $test->message = null;
        $test->debug = null;
        $test->save();

        $debug = '';
        $resultStatus = 'failed';
        $resultMessage = null;

        try {
            $mail = new PHPMailer(true);

            $mail->isSMTP();
            $mail->Host = $test->host;
            $mail->Port = (int) $test->port;
            $mail->SMTPAuth = true;
            $mail->Username = $test->username;
            $mail->Password = $test->password;

            // choose encryption based on port or known best-effort (465 -> ssl, else starttls)
            if ((int) $test->port === 465) {
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
            } else {
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            }

            // small timeouts to avoid very long blocking waits
            $mail->Timeout = 20;
            $mail->SMTPAutoTLS = true;

            $mail->setFrom($test->from_email, $test->from_name ?? $test->from_email);
            $mail->addAddress($request->input('test_email'));
            $mail->Subject = $test->subject ?? 'SMTP Test';
            $mail->isHTML(true);
            $mail->Body = $test->html_body ?? "<p>This is a test message sent by ZeusMailer SMTP Tester.</p>";
            $mail->AltBody = strip_tags($mail->Body);

            // capture debug output
            $mail->SMTPDebug = 2;
            $mail->Debugoutput = function ($str, $level) use (&$debug) {
                // keep debug reasonably sized
                $debug .= trim($str) . "\n";
                if (strlen($debug) > 30_000) {
                    $debug = substr($debug, 0, 30_000) . "\n...truncated...";
                }
            };

            $mail->send();
            $resultStatus = 'success';
            $resultMessage = 'Delivered successfully.';
        } catch (Exception $e) {
            $resultStatus = 'failed';
            // prefer PHPMailer message, fallback to exception message
            $resultMessage = $e->getMessage();
            // if debug is empty, record exception message in debug as well
            if (empty(trim($debug))) {
                $debug .= 'EXCEPTION: ' . $e->getMessage() . "\n";
            }
        }

        // update model
        $test->status = $resultStatus;
        $test->message = $resultMessage;
        $test->debug = $debug;
        $test->save();

        // return status and progress so frontend can update UI
        $total = SmtpTest::where('batch_id', $batchId)->count();
        $tested = SmtpTest::where('batch_id', $batchId)->whereIn('status', ['success', 'failed'])->count();

        return response()->json([
            'status' => 'ok',
            'row_id' => $test->id,
            'smtp' => $test->host,
            'username' => $test->username,
            'from_name' => $test->from_name,
            'subject' => $test->subject,
            'result' => $resultStatus,
            'message' => $resultMessage,
            'debug' => $debug,
            'total' => $total,
            'tested' => $tested,
        ]);
    }
}
