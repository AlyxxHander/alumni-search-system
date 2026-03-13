<?php

namespace App\Models;

use App\Enums\SumberPelacakan;
use App\Enums\StatusVerifikasi;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TrackingResult extends Model
{
    protected $fillable = [
        'alumni_id',
        'sumber',
        'query_digunakan',
        'judul_profil',
        'instansi',
        'lokasi',
        'url_profil',
        'foto_url',
        'snippet',
        'skor_probabilitas',
        'status_verifikasi',
        'verified_by',
        'verified_at',
        'raw_search_response',
        'raw_gemini_response',
    ];

    protected function casts(): array
    {
        return [
            'sumber' => SumberPelacakan::class,
            'status_verifikasi' => StatusVerifikasi::class,
            'skor_probabilitas' => 'float',
            'raw_search_response' => 'array',
            'raw_gemini_response' => 'array',
            'verified_at' => 'datetime',
        ];
    }

    public function getAlasanGeminiAttribute(): string
    {
        return $this->raw_gemini_response['alasan'] ?? 'Tidak ada alasan yang diberikan oleh sistem.';
    }

    public function alumni(): BelongsTo
    {
        return $this->belongsTo(Alumni::class);
    }

    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function scopePending($query)
    {
        return $query->where('status_verifikasi', StatusVerifikasi::PENDING);
    }

    public function scopeConfirmed($query)
    {
        return $query->where('status_verifikasi', StatusVerifikasi::CONFIRMED);
    }
}
