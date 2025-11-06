<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Campaign;
use App\Jobs\SendCampaignEmail;

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
        ]);

        // Parse emails
        $emails = array_filter(array_map('trim', file($request->file('emails')->path())));
        $emails = array_filter($emails, 'filter_var', FILTER_VALIDATE_EMAIL);

        // Parse SMTPs
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

        if (empty($emails) || empty($smtps)) {
            return back()->withErrors(['emails' => 'No valid emails or SMTPs']);
        }

        // Save campaign
        $campaign = Campaign::create([
            'subject' => $request->subject,
            'html_body' => $request->html_body,
            'smtp_input' => $request->smtp_input,
            'from_name' => $request->from_name,
            'total_emails' => count($emails),
            'delay' => $request->delay,
            'log' => ['Campaign started...'],
        ]);

        // Dispatch jobs
        foreach ($emails as $index => $email) {
            $smtp = $smtps[$index % count($smtps)];
            SendCampaignEmail::dispatch($email, $campaign->id, $smtp)
                ->delay(now()->addSeconds($index * 1));
        }

        return redirect()->route('campaign.show', $campaign->id)
            ->with('success', "Campaign #{$campaign->id} queued!");
    }

    public function show($id)
    {
        $campaign = Campaign::findOrFail($id);
        return view('campaign.show', compact('campaign'));
    }

    public function destroy($id)
    {
        $campaign = Campaign::findOrFail($id);
        $campaign->delete();
        return redirect()->route('campaign.index')->with('success', "Campaign #{$id} deleted!");
    }
}