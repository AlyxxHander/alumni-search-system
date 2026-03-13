<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$gemini = app()->make(\App\Services\GeminiService::class);
$res = $gemini->analyzeIdentityBatch(
    ['nama'=>'Andi', 'prodi'=>'Informatika', 'tahun_lulus'=>'2020'],
    [['judul_profil'=>'Andi Setiawan', 'instansi'=>'Google', 'lokasi'=>'Jakarta', 'snippet'=>'Software engineer', 'url_profil'=>'linkedin.com/andi']]
);
print_r($res);
echo "\nDONE\n";
