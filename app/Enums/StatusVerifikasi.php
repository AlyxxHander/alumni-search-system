<?php

namespace App\Enums;

enum StatusVerifikasi: string
{
    case PENDING = 'PENDING';
    case CONFIRMED = 'CONFIRMED';
    case REJECTED = 'REJECTED';
    case SKIPPED = 'SKIPPED';

    public function label(): string
    {
        return match ($this) {
            self::PENDING => 'Pending',
            self::CONFIRMED => 'Dikonfirmasi',
            self::REJECTED => 'Ditolak',
            self::SKIPPED => 'Dilewati',
        };
    }
}
