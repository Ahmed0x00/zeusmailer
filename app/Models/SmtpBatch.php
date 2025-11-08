<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class SmtpBatch extends Model
{
    use HasFactory;

    // if your migration uses uuid string primary key:
    protected $keyType = 'string';
    public $incrementing = false;

    protected $table = 'smtp_batches';

    protected $fillable = [
        'id',
        'filename',
        'total',
        'processed',
        'success',
        'recent_results',
        'status',
        'combos',
    ];

    protected $casts = [
        'total' => 'integer',
        'processed' => 'integer',
        'success' => 'integer',
        'recent_results' => 'array',
        'combos' => 'array',
    ];

    // Status constants
    public const STATUS_PENDING = 'pending';
    public const STATUS_RUNNING = 'running';
    public const STATUS_PAUSED  = 'paused';
    public const STATUS_FINISHED = 'finished';

    // Boot: generate uuid if not provided
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->{$model->getKeyName()})) {
                $model->{$model->getKeyName()} = (string) Str::uuid();
            }
            if (is_null($model->recent_results)) {
                $model->recent_results = [];
            }
        });
    }

    /* -----------------------
     | Relationships
     |----------------------- */
    public function results()
    {
        return $this->hasMany(SmtpResult::class, 'batch_id');
    }

    /* -----------------------
     | Helpers: progress & recent results
     |----------------------- */

    /**
     * Atomically add a result and update counters.
     *
     * @param array $entry  // ['email'=>..., 'status'=>'success'|'failed', 'smtp_host'=>..., 'port'=>..., 'response_time'=>..., 'ts'=>...]
     * @param int $recentLimit  // how many recent results to keep
     * @return void
     */
    public function pushResult(array $entry, int $recentLimit = 200): void
    {
        // Use transaction to avoid race conditions when multiple jobs update same batch
        DB::transaction(function () use ($entry, $recentLimit) {
            // Reload with lock
            $batch = static::where($this->getKeyName(), $this->getKey())->lockForUpdate()->first();

            if (!$batch) {
                return;
            }

            // decode current recent_results (cast handles null -> array on model, but we re-read raw)
            $recent = $batch->recent_results ?? [];

            // append new entry
            $recent[] = $entry;

            // trim to recentLimit
            if (count($recent) > $recentLimit) {
                $recent = array_slice($recent, -$recentLimit);
            }

            // increment processed and success counters
            $processed = ($batch->processed ?? 0) + 1;
            $success = ($batch->success ?? 0) + (isset($entry['status']) && $entry['status'] === 'success' ? 1 : 0);

            // update attributes
            $batch->processed = $processed;
            $batch->success = $success;
            $batch->recent_results = $recent;

            // If processed reached total, mark finished
            if ($batch->total > 0 && $processed >= $batch->total) {
                $batch->status = self::STATUS_FINISHED;
            }

            $batch->save();
        });
    }

    /**
     * Mark the batch as running (use before dispatching jobs)
     *
     * @return $this
     */
    public function markRunning()
    {
        $this->status = self::STATUS_RUNNING;
        $this->save();
        return $this;
    }

    /**
     * Pause the batch
     *
     * @return $this
     */
    public function pause()
    {
        $this->status = self::STATUS_PAUSED;
        $this->save();
        return $this;
    }

    /**
     * Resume the batch (set to running)
     *
     * @return $this
     */
    public function resume()
    {
        $this->status = self::STATUS_RUNNING;
        $this->save();
        return $this;
    }

    /**
     * Simple helper to return remaining count
     *
     * @return int
     */
    public function remaining(): int
    {
        return max(0, ($this->total ?? 0) - ($this->processed ?? 0));
    }

    public function checkIfCompleted(): void
{
    $total = $this->total ?? 0;
    $done  = $this->results()->count();

    if ($total > 0 && $done >= $total && $this->status !== self::STATUS_FINISHED) {
        $this->update([
            'status' => self::STATUS_FINISHED,
            'completed_at' => now(),
        ]);
    }
}

}
