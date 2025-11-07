<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Campaign;
use App\Models\DebugLog;
use App\Jobs\SendCampaignEmail;
use Illuminate\Support\Facades\DB;

class CampaignController extends Controller
{
    public function index()
    {
        $campaigns = Campaign::latest()->get();
        return view('campaign.index', compact('campaigns'));
    }

    public function create()
    {
        return view('campaign.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'emails' => 'required|file|mimes:txt',
            'smtp_input' => 'required',
            'subject' => 'required',
            'html_body' => 'required',
            'from_name' => 'nullable|string|max:255',
            'delay' => 'nullable|integer|min:0|max:60',
        ]);

        // === 1. PARSE & CLEAN EMAILS ===
        $rawEmails = file($request->file('emails')->path(), FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        $rawEmails = array_map('trim', $rawEmails);

        $validEmails = [];
        foreach ($rawEmails as $email) {
            $cleanEmail = filter_var($email, FILTER_VALIDATE_EMAIL);
            if ($cleanEmail) {
                $validEmails[] = $cleanEmail;
            }
        }

        $uniqueEmails = array_unique($validEmails);
        if (empty($uniqueEmails)) {
            return back()->withErrors(['emails' => 'No valid email addresses found in file.']);
        }

        // === 2. PARSE SMTPs ===
        $smtps = [];
        foreach (explode("\n", trim($request->smtp_input)) as $line) {
            $line = trim($line);
            if (!$line || $line[0] === '#')
                continue;

            $parts = array_map('trim', explode('|', $line));
            if (count($parts) >= 5) {
                $smtps[] = [
                    'host' => $parts[0],
                    'port' => $parts[1],
                    'username' => $parts[2],
                    'password' => $parts[3],
                    'from_email' => $parts[4],
                    'from_name' => $parts[5] ?? $parts[4],
                ];
            }
        }

        if (empty($smtps)) {
            return back()->withErrors(['smtp_input' => 'No valid SMTP accounts found.']);
        }

        // === 3. SAVE CAMPAIGN ===
        $campaign = Campaign::create([
            'subject' => $request->subject,
            'html_body' => $request->html_body,
            'smtp_input' => $request->smtp_input,
            'from_name' => $request->from_name ?? 'ZeusMailer',
            'total_emails' => count($uniqueEmails),
            'delay' => $request->delay ?? 1,
            'status' => 'running',
            'sent_emails' => [],
            'failed_emails' => [],
            'processed_emails' => [],
            'all_emails' => $uniqueEmails,
            'current_index' => 0,
            'log' => ['Campaign started with ' . count($uniqueEmails) . ' valid, unique emails'],
            'queue_name' => 'campaign_' . uniqid(),
        ]);

        // === 4. DISPATCH JOBS ===
        foreach ($uniqueEmails as $index => $email) {
            $smtp = $smtps[$index % count($smtps)];
            SendCampaignEmail::dispatch($email, $campaign->id, $smtp);
            // ->onQueue($campaign->queue_name); // ⚡ no per-index delay
        }

        return redirect()
            ->route('campaign.show', $campaign->id)
            ->with('success', "Campaign #{$campaign->id} queued! ({$campaign->total_emails} emails)");
    }


    public function show($id)
    {
        $campaign = Campaign::findOrFail($id);
        // CORRECT (returns full model)
        $debugLogs = DebugLog::where('campaign_id', $id)
            ->get(['id', 'email', 'smtp_username', 'status', 'debug', 'created_at'])
            ->keyBy('email')
            ->toArray();

        if (request()->ajax()) {
            $processed = $campaign->sent + $campaign->failed;
            $logLines = '';
            foreach (($campaign->log ?? []) as $line) {
                $isSuccess = str_contains($line, 'SUCCESS');
                $isFailed = str_contains($line, 'FAILED');
                $class = $isSuccess ? 'success' : ($isFailed ? 'failed' : 'info');
                $icon = $isSuccess ? 'Check Circle' : ($isFailed ? 'Times Circle' : 'Info Circle');
                $logLines .= "<div class='log-line {$class}'><i class='fas fa-{$icon}'></i> {$line}</div>";
            }

            return response()->json([
                'sent' => $campaign->sent,
                'failed' => $campaign->failed,
                'status' => $campaign->status,
                'logHtml' => $logLines ?: '<div class="text-center text-muted py-4"><i class="fas fa-clock fa-2x mb-3"></i><p>No activity yet...</p></div>'
            ]);
        }

        return view('campaign.show', compact('campaign', 'debugLogs'));
    }

    public function toggleStatus($id)
    {
        $campaign = Campaign::findOrFail($id);

        $oldStatus = $campaign->status;
        $newStatus = $campaign->status === 'running' ? 'paused' : 'running';
        $campaign->status = $newStatus;
        $campaign->save();

        // if ($newStatus === 'paused') {
        //     // 🧨 Kill pending jobs (not yet processed)
        //     DB::table('jobs')->where('queue', $campaign->queue_name)->delete();
        //     $this->addLog("Paused campaign — cleared pending jobs from {$campaign->queue_name}", $campaign);
        // }

        if ($newStatus === 'running' && $oldStatus === 'paused') {
            $smtpInput = $campaign->smtp_input_updated ?: $campaign->smtp_input;
            $smtps = $this->parseSmtps($smtpInput);

            $allEmails = $campaign->all_emails ?? [];

            $processedEmails = is_array($campaign->processed_emails)
                ? $campaign->processed_emails
                : (json_decode($campaign->processed_emails, true) ?? []);

            // Resume from current_index + 1
            $unsentEmails = array_slice($allEmails, $campaign->current_index);

            if (empty($unsentEmails)) {
                $this->addLog("Resumed - no remaining emails", $campaign);
                return back()->with('info', "Campaign #{$id} resumed but no emails left to send.");
            }

            foreach ($unsentEmails as $index => $email) {
                $smtp = $smtps[$index % count($smtps)];
                SendCampaignEmail::dispatch($email, $campaign->id, $smtp);
                // ->onQueue($campaign->queue_name);
            }

            $this->addLog("Resumed campaign — dispatched " . count($unsentEmails) - 1 . " remaining emails", $campaign);
        }

        $action = strtoupper($newStatus);
        return back()->with('success', "Campaign #{$id} {$action}");
    }


    public function destroy($id)
    {
        $campaign = Campaign::findOrFail($id);

        // 🧹 1. Delete pending jobs in this campaign’s queue
        if (!empty($campaign->queue_name)) {
            \DB::table('jobs')->where('queue', $campaign->queue_name)->delete();
        }

        // 🧹 2. Delete related debug logs (optional but recommended)
        \DB::table('debug_logs')->where('campaign_id', $campaign->id)->delete();

        // 🧹 3. Delete the campaign itself
        $campaign->delete();

        return redirect()
            ->route('campaign.index')
            ->with('success', "Campaign #{$id} deleted and its queue cleared!");
    }

    private function parseSmtps($smtp_input)
    {
        $smtps = [];
        foreach (explode("\n", trim($smtp_input)) as $line) {
            $line = trim($line);
            if (!$line || $line[0] === '#')
                continue;

            $parts = array_map('trim', explode('|', $line));
            if (count($parts) >= 5) {
                $smtps[] = [
                    'host' => $parts[0],
                    'port' => $parts[1],
                    'username' => $parts[2],
                    'password' => $parts[3],
                    'from_email' => $parts[4],
                    'from_name' => $parts[5] ?? $parts[4],
                ];
            }
        }
        return $smtps;
    }

    public function updateSmtps(Request $request, $id)
    {
        $campaign = Campaign::findOrFail($id);

        if ($campaign->status !== 'paused') {
            return back()->with('error', 'Can only update SMTPs when campaign is paused.');
        }

        $request->validate([
            'smtp_input' => 'required'
        ]);

        $campaign->smtp_input_updated = $request->smtp_input;
        $campaign->save();

        return back()->with('success', 'SMTPs updated! They will be used when you resume.');
    }

    public function updateCampaign(Request $request, $id)
    {
        $campaign = Campaign::findOrFail($id);

        if ($campaign->status !== 'paused') {
            return back()->with('error', 'You can only edit a campaign while it’s paused.');
        }

        $request->validate([
            'subject' => 'required|string|max:255',
            'html_body' => 'required|string',
            'from_name' => 'nullable|string|max:255',
        ]);

        $campaign->update([
            'subject' => $request->subject,
            'html_body' => $request->html_body,
            'from_name' => $request->from_name ?? $campaign->from_name,
        ]);

        $this->addLog('Campaign details updated while paused.', $campaign);

        return back()->with('success', 'Campaign content updated successfully!');
    }


    private function addLog($message, $campaign)
    {
        $log = $campaign->log ?? [];
        $log[] = now()->format('H:i:s') . " | " . $message;
        $campaign->log = $log;
        $campaign->save();
    }

    public function exportFailedEmails($id)
    {
        $campaign = Campaign::findOrFail($id);

        if ($campaign->status !== 'completed') {
            return back()->with('error', 'Campaign must be completed.');
        }

        $failed = $campaign->failed_emails ?? [];

        if (empty($failed)) {
            return back()->with('info', 'No failed emails to export.');
        }

        $content = implode("\n", $failed);
        $filename = "failed-emails-campaign-{$id}.txt";

        return response($content)
            ->header('Content-Type', 'text/plain')
            ->header('Content-Disposition', "attachment; filename=\"{$filename}\"");
    }
}