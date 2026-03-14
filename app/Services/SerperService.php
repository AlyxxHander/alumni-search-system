<?php

namespace App\Services;

use App\Enums\SumberPelacakan;
use App\Models\Alumni;
use App\Models\TrackingConfig;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SerperService
{
    protected string $apiKey;
    protected string $baseUrl = 'https://google.serper.dev/search';

    public function __construct()
    {
        $this->apiKey = config('services.serper.api_key', env('SERPER_API_KEY', ''));
    }

    public function search(string $query): array
    {
        try {
            $response = Http::withHeaders([
                'X-API-KEY' => $this->apiKey,
                'Content-Type' => 'application/json',
            ])->post($this->baseUrl, [
                'q' => $query,
                'gl' => 'id',
                'hl' => 'id',
                'num' => 5,
            ]);

            if ($response->successful()) {
                return $response->json();
            }

            Log::error('Serper API error', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return [];
        } catch (\Exception $e) {
            Log::error('Serper API exception: ' . $e->getMessage());
            return [];
        }
    }

    public function buildQuery(Alumni $alumni, SumberPelacakan $sumber): string
    {
        $afiliasi = TrackingConfig::getValue('afiliasi_utama', ['UMM', 'Informatika']);
        $afiliasiStr = implode(' ', array_slice($afiliasi, 0, 2));

        $namaVariasi = $alumni->variasi_nama;
        $nama = $namaVariasi[0] ?? $alumni->nama_lengkap;

        $query = "{$nama} {$afiliasiStr} {$sumber->siteFilter()}";

        return $query;
    }

    public function extractResults(array $response): array
    {
        $results = [];
        $organic = $response['organic'] ?? [];

        foreach ($organic as $item) {
            $results[] = [
                'judul_profil' => $item['title'] ?? null,
                'url_profil' => $item['link'] ?? null,
                'snippet' => $item['snippet'] ?? null,
                'instansi' => $this->extractInstansi($item['snippet'] ?? ''),
                'lokasi' => $this->extractLokasi($item['snippet'] ?? ''),
                'foto_url' => $item['imageUrl'] ?? null,
            ];
        }

        return $results;
    }

    protected function extractInstansi(string $snippet): ?string
    {
        // Simple extraction - Gemini will do the real analysis
        if (preg_match('/(?:at|di|@)\s+([^,\.\-]+)/i', $snippet, $matches)) {
            return trim($matches[1]);
        }
        return null;
    }

    protected function extractLokasi(string $snippet): ?string
    {
        // Simple extraction
        $cities = ['Jakarta', 'Surabaya', 'Bandung', 'Malang', 'Yogyakarta', 'Semarang', 'Medan', 'Makassar', 'Bali', 'Denpasar'];
        foreach ($cities as $city) {
            if (stripos($snippet, $city) !== false) {
                return $city;
            }
        }
        return null;
    }
}
