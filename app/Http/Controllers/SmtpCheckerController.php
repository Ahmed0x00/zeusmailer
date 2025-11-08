<?php

namespace App\Http\Controllers;

use App\Models\SmtpBatch;
use App\Models\SmtpResult;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use App\Jobs\SmtpCheckJob;

class SmtpCheckerController extends Controller
{
    /**
     * UI view
     */
    public function index()
    {
        return view('smtp.create');
    }

    /**
     * Start a new batch of SMTP checks
     */
    public function start(Request $request)
    {
        // Accept either file upload OR combos (string or array).
        // We'll not use strict validation rule here because input can be multiple shapes.
        $file = $request->file('file');
        $rawCombos = $request->input('combos');

        $combos = [];

        // 1) If a file was uploaded, read it
        if ($file && $file->isValid()) {
            try {
                // read file contents and split by newlines
                $contents = $file->get();
                // normalize line endings and split
                $lines = preg_split('/\r\n|\r|\n/', trim($contents));
                foreach ($lines as $line) {
                    $line = trim($line);
                    if ($line !== '')
                        $combos[] = $line;
                }
                $filename = $file->getClientOriginalName();
            } catch (\Throwable $e) {
                return response()->json(['error' => 'Failed to read uploaded file.'], 422);
            }
        } else {
            // 2) If combos[] was sent (FormData with multiple combos[] entries)
            if (is_array($rawCombos)) {
                foreach ($rawCombos as $line) {
                    $line = trim((string) $line);
                    if ($line !== '')
                        $combos[] = $line;
                }
                $filename = $request->input('filename', 'manual');
            } else {
                // 3) If a single multiline textarea named 'combos' was sent
                if (is_string($rawCombos) && trim($rawCombos) !== '') {
                    $lines = preg_split('/\r\n|\r|\n/', trim($rawCombos));
                    foreach ($lines as $line) {
                        $line = trim($line);
                        if ($line !== '')
                            $combos[] = $line;
                    }
                    $filename = $request->input('filename', 'manual');
                } else {
                    // nothing provided
                    return response()->json(['error' => 'No combos provided. Upload a .txt or paste combos.'], 422);
                }
            }
        }

        // Final cleanup - remove duplicates & ensure non-empty
        $combos = array_values(array_filter(array_map('trim', $combos), fn($v) => $v !== ''));
        if (empty($combos)) {
            return response()->json(['error' => 'No valid combos found after parsing.'], 422);
        }

        // Optionally limit ridiculously large files (protect workers). Example:
        // if (count($combos) > 10000) return response()->json(['error'=>'Too many combos'], 422);

        $id = Str::uuid()->toString();

        // Create batch and dispatch jobs
        \DB::transaction(function () use ($id, $combos, $filename) {
            $batch = SmtpBatch::create([
                'id' => $id,
                'combos' => $combos,
                'filename' => $filename ?? 'manual',
                'total' => count($combos),
                'processed' => 0,
                'success' => 0,
                'status' => SmtpBatch::STATUS_RUNNING,
            ]);

            // Dispatch jobs (fire-and-forget)
            foreach ($combos as $combo) {
                SmtpCheckJob::dispatch($id, $combo);
            }
        });

        return response()->json([
            'message' => 'Batch started successfully.',
            'batch_id' => $id,
        ]);
    }



    public function status(SmtpBatch $batch)
    {
        // render the show.blade view instead of returning JSON
        return view('smtp.show', compact('batch'));
    }

    /**
     * Get live batch status
     */
    public function liveStatus(SmtpBatch $batch)
    {
        $batch->refresh();

        return response()->json([
            'id' => $batch->id,
            'status' => $batch->status,
            'total' => $batch->total,
            'processed' => $batch->processed,
            'success' => $batch->success,
            'remaining' => $batch->remaining(),
            'recent_results' => $batch->recent_results ?? [],
        ]);
    }

    /**
     * Pause batch
     */
    public function pause(SmtpBatch $batch)
    {
        if ($batch->status === SmtpBatch::STATUS_FINISHED) {
            return response()->json(['message' => 'Batch already finished.'], 400);
        }

        $batch->pause();
        return response()->json(['message' => 'Batch paused.']);
    }

    /**
     * Resume a paused batch
     */
    public function resume(SmtpBatch $batch)
    {
        if ($batch->status !== SmtpBatch::STATUS_PAUSED) {
            return response()->json(['message' => 'Batch not paused.'], 400);
        }

        $batch->resume();

        // dispatch only remaining combos
        $processedEmails = $batch->results()->pluck('email')->filter()->toArray();
        $remainingCombos = collect($batch->combos)
            ->reject(function ($combo) use ($processedEmails) {
                [$email] = explode(':', $combo, 2);
                return in_array(trim($email), $processedEmails);
            })
            ->values()
            ->all();

        foreach ($remainingCombos as $combo) {
            SmtpCheckJob::dispatch($batch->id, $combo);
        }

        return response()->json([
            'message' => 'Batch resumed.',
            'remaining_jobs' => count($remainingCombos),
        ]);
    }

    /**
     * List all batches
     */
    public function batches()
    {
        $batches = SmtpBatch::orderBy('created_at', 'desc')->paginate(10);
        return view('smtp.batches', compact('batches'));
    }

    /**
     * List results of a batch (paginated)
     */
    // public function results(SmtpBatch $batch, Request $request)
    // {
    //     $query = $batch->results()->orderBy('created_at', 'desc');

    //     if ($request->has('status')) {
    //         $query->where('status', $request->status);
    //     }

    //     return response()->json($query->paginate(50));
    // }

    /**
     * Export batch results as CSV
     */
    public function results(Request $request, SmtpBatch $batch)
    {
        if ($request->boolean('download')) {
            $filter = $request->get('filter', 'success');
            $query = $batch->results();
            if ($filter === 'success')
                $query->where('status', 'success');
            $results = $query->get(['smtp_host', 'port', 'email', 'password']);

            $lines = $results->map(fn($r) => "{$r->smtp_host}|{$r->port}|{$r->email}|{$r->password}")->join("\n");
            return response($lines)
                ->header('Content-Type', 'text/plain')
                ->header('Content-Disposition', "attachment; filename=batch_{$batch->id}_success.txt");
        }

        // Otherwise render blade
        return view('smtp.results', compact('batch'));
    }

}
