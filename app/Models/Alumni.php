<?php

namespace App\Models;

use App\Enums\StatusPelacakan;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Alumni extends Model
{
    protected $table = 'alumni';

    protected $fillable = [
        'nim',
        'nama_lengkap',
        'nama_panggilan',
        'inisial_belakang',
        'prodi',
        'tahun_lulus',
        'gelar_akademik',
        'status_pelacakan',
    ];

    protected function casts(): array
    {
        return [
            'status_pelacakan' => StatusPelacakan::class,
        ];
    }

    public function trackingResults(): HasMany
    {
        return $this->hasMany(TrackingResult::class);
    }

    public function scopeBelumDilacak($query)
    {
        return $query->where('status_pelacakan', StatusPelacakan::BELUM_DILACAK);
    }

    public function scopePerluUpdate($query)
    {
        return $query->where('status_pelacakan', StatusPelacakan::PERLU_UPDATE);
    }

    public function scopeButuhVerifikasi($query)
    {
        return $query->where('status_pelacakan', StatusPelacakan::BUTUH_VERIFIKASI_MANUAL);
    }

    public function scopePerluDilacak($query)
    {
        return $query->whereIn('status_pelacakan', [
            StatusPelacakan::BELUM_DILACAK,
            StatusPelacakan::PERLU_UPDATE,
        ]);
    }

    public function getVariasiNamaAttribute(): array
    {
        return array_values(array_unique(array_filter([
            $this->nama_lengkap,
            $this->nama_panggilan,
            $this->inisial_belakang,
        ])));
    }
}
