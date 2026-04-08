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
        'prodi',
        'tahun_lulus',
        'gelar_akademik',
        'status_pelacakan',
        'skor_keseluruhan',
        // Data akademik tambahan
        'tahun_masuk',
        'tanggal_lulus',
        'fakultas',
        // Kontak (diisi otomatis oleh sistem)
        'email',
        'no_hp',
        // Sosial media (diisi otomatis oleh sistem)
        'linkedin',
        'instagram',
        'facebook',
        'tiktok',
        // Data pekerjaan (diisi otomatis oleh sistem)
        'tempat_bekerja',
        'alamat_bekerja',
        'posisi',
        'jenis_pekerjaan',
        'sosmed_tempat_bekerja',
        'instansi_linkedin',
        'instansi_instagram',
        'instansi_facebook',
        'instansi_tiktok',
    ];

    protected function casts(): array
    {
        return [
            'status_pelacakan' => StatusPelacakan::class,
            'skor_keseluruhan' => 'float',
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
        ])));
    }

    /**
     * Check if alumni has any social media data
     */
    public function getHasSocialMediaAttribute(): bool
    {
        return !empty($this->linkedin) || !empty($this->instagram)
            || !empty($this->facebook) || !empty($this->tiktok);
    }

    /**
     * Check if alumni has employment data
     */
    public function getHasEmploymentDataAttribute(): bool
    {
        return !empty($this->tempat_bekerja) || !empty($this->posisi);
    }

    /**
     * Check if alumni has contact data
     */
    public function getHasContactDataAttribute(): bool
    {
        return !empty($this->email) || !empty($this->no_hp);
    }
}
