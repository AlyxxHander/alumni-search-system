<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Alumni;
use App\Models\TrackingConfig;
use App\Services\SerperService;
use App\Enums\SumberPelacakan;

$alumni = Alumni::where('nama_lengkap', 'Dhio')->first();
if (!$alumni) {
    // Create dummy for test if not exists
    $alumni = new Alumni([
        'nama_lengkap' => 'Dhio',
        'nama_panggilan' => 'Dhio',
        'inisial_belakang' => 'D. Dhio',
        'prodi' => 'Informatika',
        'tahun_lulus' => 2027
    ]);
}

$serper = new SerperService();
$sumber = SumberPelacakan::LINKEDIN;

$config = TrackingConfig::all();
$out = "Config:\n" . json_encode($config, JSON_PRETTY_PRINT) . "\n\n";

$query = $serper->buildQuery($alumni, $sumber);
$out .= "Generated Query: " . $query . "\n";

file_put_contents('query_test.txt', $out);
echo "Done";
