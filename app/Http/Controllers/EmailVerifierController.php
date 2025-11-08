<?php

namespace App\Http\Controllers;

use App\Models\VerifyBatch;
use App\Jobs\VerifyEmailJob;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class EmailVerifierController extends Controller
{
    /**
     * Show the form to create a new verification batch
     */
    public function create()
    {
        return view('verifier.create');
    }

    /**
     * Handle new batch creation (manual or file upload)
     */
    public function start(Request $request)
    {
        // Validate
        $request->validate([
            'emails' => 'array',
            'file' => 'nullable|file|mimes:txt',
        ]);

        $emails = [];

        // 1️⃣ File upload mode
        if ($request->hasFile('file')) {
            $content = file($request->file('file')->getRealPath(), FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            $emails = array_map('trim', $content);
            $filename = $request->file('file')->getClientOriginalName();
        }
        // 2️⃣ Manual mode
        else {
            $emails = array_filter($request->input('emails', []));
            $filename = 'manual_input.txt';
        }

        // Ensure we have emails
        if (empty($emails)) {
            return response()->json(['error' => 'No emails provided.'], 422);
        }

        // Create batch
        $batchId = Str::uuid()->toString();

        $batch = VerifyBatch::create([
            'id' => $batchId,
            'filename' => $filename,
            'total' => count($emails),
            'processed' => 0,
            'status' => 'running',
        ]);

        // Dispatch each job
        foreach ($emails as $email) {
            VerifyEmailJob::dispatch($batchId, $email);
        }

        return response()->json([
            'message' => 'Batch created successfully',
            'batch_id' => $batchId,
        ]);
    }

    /**
     * Show batch progress (view)
     */
    public function show(VerifyBatch $batch)
    {
        $batch->load('results');
        return view('verifier.show', compact('batch'));
    }

    /**
     * API endpoint for live status (Ajax polling)
     */
    public function status(VerifyBatch $batch)
    {
        return response()->json([
            'id' => $batch->id,
            'status' => $batch->status,
            'total' => $batch->total,
            'processed' => $batch->processed,
            'valid' => $batch->results()->where('status', 'valid')->count(),
            'invalid' => $batch->results()->where('status', 'invalid')->count(),
            'error' => $batch->results()->where('status', 'error')->count(),
        ]);
    }
}
