<?php

namespace App\Enums;

enum StatusPelacakan: string
{
    case BELUM_DILACAK = 'BELUM_DILACAK';
    case SEDANG_DILACAK = 'SEDANG_DILACAK';
    case VALID_OTOMATIS = 'VALID_OTOMATIS';
    case BUTUH_VERIFIKASI_MANUAL = 'BUTUH_VERIFIKASI_MANUAL';
    case TERVERIFIKASI = 'TERVERIFIKASI';
    case TIDAK_DITEMUKAN = 'TIDAK_DITEMUKAN';
    case IDENTITAS_TIDAK_COCOK = 'IDENTITAS_TIDAK_COCOK';
    case PERLU_UPDATE = 'PERLU_UPDATE';

    public function label(): string
    {
        return match ($this) {
            self::BELUM_DILACAK => 'Belum Dilacak',
            self::SEDANG_DILACAK => 'Sedang Dilacak',
            self::VALID_OTOMATIS => 'Valid (Otomatis)',
            self::BUTUH_VERIFIKASI_MANUAL => 'Butuh Verifikasi',
            self::TERVERIFIKASI => 'Terverifikasi',
            self::TIDAK_DITEMUKAN => 'Tidak Ditemukan',
            self::IDENTITAS_TIDAK_COCOK => 'Identitas Tidak Cocok',
            self::PERLU_UPDATE => 'Perlu Update',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::BELUM_DILACAK => 'gray',
            self::SEDANG_DILACAK => 'blue',
            self::VALID_OTOMATIS => 'green',
            self::BUTUH_VERIFIKASI_MANUAL => 'yellow',
            self::TERVERIFIKASI => 'emerald',
            self::TIDAK_DITEMUKAN => 'red',
            self::IDENTITAS_TIDAK_COCOK => 'orange',
            self::PERLU_UPDATE => 'orange',
        };
    }
}
