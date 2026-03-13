<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$latest = \App\Models\TrackingResult::orderBy('id', 'desc')->first();
if (!$latest) {
    file_put_contents('inspect_out.txt', "No tracking results found.");
    exit;
}
$out = "Alumni ID: " . $latest->alumni_id . "\n";
$out .= "Query: " . $latest->query_digunakan . "\n";
$out .= "Title: " . $latest->judul_profil . "\n";
$out .= "Skor: " . $latest->skor_probabilitas . "\n";
$out .= "Alasan: " . ($latest->raw_gemini_response['alasan'] ?? 'N/A') . "\n";
$out .= "RAW SEARCH COUNT: " . count($latest->raw_search_response['organic'] ?? []) . "\n";

$out .= "\n--- First 3 Search Results Snippets ---\n";
$organic = $latest->raw_search_response['organic'] ?? [];
for ($i = 0; $i < min(3, count($organic)); $i++) {
    $out .= "[$i] Title: " . $organic[$i]['title'] . "\n";
    $out .= "[$i] Link: " . $organic[$i]['link'] . "\n";
    $out .= "[$i] Snippet: " . $organic[$i]['snippet'] . "\n";
}
file_put_contents('inspect_out.txt', $out);
echo "Done";
