<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BulkTrackingLog extends Model
{
    protected $fillable = [
        'batch_alumni_ids',
        'status',
        'total_alumni',
        'success_count',
        'failed_count',
        'error_message',
        'processed_at',
    ];

    protected function casts(): array
    {
        return [
            'batch_alumni_ids' => 'array',
            'processed_at' => 'datetime',
        ];
    }

    // --- Scopes ---

    public function scopeQueued($query)
    {
        return $query->where('status', 'queued');
    }

    public function scopeProcessing($query)
    {
        return $query->where('status', 'processing');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    // --- Helpers ---

    /**
     * Hitung statistik bulk tracking secara keseluruhan.
     */
    public static function getOverallStats(): array
    {
        return [
            'total_batches' => static::count(),
            'queued' => static::queued()->count(),
            'processing' => static::processing()->count(),
            'completed' => static::completed()->count(),
            'failed' => static::failed()->count(),
            'total_processed' => (int) static::completed()->sum('success_count'),
            'total_failed_alumni' => (int) static::sum('failed_count'),
        ];
    }
}
