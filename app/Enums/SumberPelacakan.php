<?php

namespace App\Enums;

enum SumberPelacakan: string
{
    case GOOGLE_SCHOLAR = 'GOOGLE_SCHOLAR';
    case LINKEDIN = 'LINKEDIN';
    case GITHUB = 'GITHUB';

    public function label(): string
    {
        return match ($this) {
            self::GOOGLE_SCHOLAR => 'Google Scholar',
            self::LINKEDIN => 'LinkedIn',
            self::GITHUB => 'GitHub',
        };
    }

    public function siteFilter(): string
    {
        return match ($this) {
            self::GOOGLE_SCHOLAR => 'site:scholar.google.com',
            self::LINKEDIN => 'site:linkedin.com/in',
            self::GITHUB => 'site:github.com',
        };
    }
}
