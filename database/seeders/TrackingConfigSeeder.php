<?php

namespace Database\Seeders;

use App\Models\TrackingConfig;
use Illuminate\Database\Seeder;

class TrackingConfigSeeder extends Seeder
{
    public function run(): void
    {
        $configs = [
            [
                'key' => 'afiliasi_utama',
                'value' => ['UMM', 'Universitas Muhammadiyah Malang', 'Informatika'],
            ],
            [
                'key' => 'threshold_valid_otomatis',
                'value' => 0.8,
            ],
            [
                'key' => 'threshold_verifikasi_manual',
                'value' => 0.5,
            ],
            [
                'key' => 'sumber_aktif',
                'value' => ['GOOGLE_SCHOLAR', 'LINKEDIN', 'GITHUB', 'INSTAGRAM', 'FACEBOOK', 'TIKTOK'],
            ],
            [
                'key' => 'scheduler_interval',
                'value' => 'daily',
            ],
        ];

        foreach ($configs as $config) {
            TrackingConfig::updateOrCreate(
                ['key' => $config['key']],
                ['value' => $config['value']]
            );
        }
    }
}
