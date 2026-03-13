<?php
require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$apiKey = env('GEMINI_API_KEY');
$url = "https://generativelanguage.googleapis.com/v1beta/models?key={$apiKey}";
$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$res = curl_exec($ch);
curl_close($ch);

echo "Available Models:\n";
$models = json_decode($res, true);
if (isset($models['models'])) {
    foreach ($models['models'] as $m) {
        if (str_contains($m['name'], 'gemini')) {
            echo "- " . $m['name'] . "\n";
        }
    }
} else {
    echo "Error fetching models: " . print_r($models, true) . "\n";
}

$model = env('GEMINI_MODEL');
echo "\nTesting generation with model: {$model}\n";
$generateUrl = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$apiKey}";
$data = ['contents' => [['parts' => [['text' => 'Hello. Return a JSON object with {"skor": 0.5, "alasan": "Test"} ']]]]];
$ch2 = curl_init($generateUrl);
curl_setopt($ch2, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch2, CURLOPT_POST, true);
curl_setopt($ch2, CURLOPT_POSTFIELDS, json_encode($data));
curl_setopt($ch2, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
$res2 = curl_exec($ch2);
$httpCode = curl_getinfo($ch2, CURLINFO_HTTP_CODE);
curl_close($ch2);

echo "HTTP Code: {$httpCode}\n";
echo "Response: {$res2}\n";
