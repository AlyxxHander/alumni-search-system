<?php

namespace App\Enums;

enum SumberPelacakan: string
{
    case GOOGLE_SCHOLAR = 'GOOGLE_SCHOLAR';
    case LINKEDIN = 'LINKEDIN';
    case GITHUB = 'GITHUB';
    case INSTAGRAM = 'INSTAGRAM';
    case FACEBOOK = 'FACEBOOK';
    case TIKTOK = 'TIKTOK';
    case GABUNGAN = 'GABUNGAN';

    public function label(): string
    {
        return match ($this) {
            self::GOOGLE_SCHOLAR => 'Google Scholar',
            self::LINKEDIN => 'LinkedIn',
            self::GITHUB => 'GitHub',
            self::INSTAGRAM => 'Instagram',
            self::FACEBOOK => 'Facebook',
            self::TIKTOK => 'TikTok',
            self::GABUNGAN => 'Gabungan (AI)',
        };
    }

    public function siteFilter(): string
    {
        return match ($this) {
            self::GOOGLE_SCHOLAR => 'site:scholar.google.com',
            self::LINKEDIN => 'site:linkedin.com/in',
            self::GITHUB => 'site:github.com',
            self::INSTAGRAM => 'site:instagram.com',
            self::FACEBOOK => 'site:facebook.com',
            self::TIKTOK => 'site:tiktok.com',
            self::GABUNGAN => '',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::GOOGLE_SCHOLAR => '📚',
            self::LINKEDIN => '💼',
            self::GITHUB => '🐙',
            self::INSTAGRAM => '📸',
            self::FACEBOOK => '👤',
            self::TIKTOK => '🎵',
            self::GABUNGAN => '🤖',
        };
    }
}
