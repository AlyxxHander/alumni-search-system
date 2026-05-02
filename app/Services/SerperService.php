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

    public function search(string $query, int $num): array
    {
        try {
            $response = Http::withHeaders([
                'X-API-KEY' => $this->apiKey,
                'Content-Type' => 'application/json',
            ])->post($this->baseUrl, [
                'q' => $query,
                'gl' => 'id',
                'hl' => 'id',
                'num' => $num,
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

    /**
     * Multi-query search: coba beberapa variasi query untuk satu sumber,
     * lalu gabungkan & deduplikasi hasilnya berdasarkan URL.
     */
    public function searchMultiQuery(Alumni $alumni, SumberPelacakan $sumber): array
    {
        $queries = $this->buildMultiQueries($alumni, $sumber);
        $allOrganic = [];
        $seenUrls = [];
        $mergedResponse = [];

        foreach ($queries as $query) {
            $response = $this->search($query, $this->getNumResults());

            if (empty($response)) continue;

            // Simpan respons pertama sebagai base
            if (empty($mergedResponse)) {
                $mergedResponse = $response;
                $mergedResponse['organic'] = [];
            }

            // Deduplikasi berdasarkan URL
            foreach ($response['organic'] ?? [] as $item) {
                $url = $item['link'] ?? '';
                if ($url && !isset($seenUrls[$url])) {
                    $seenUrls[$url] = true;
                    $allOrganic[] = $item;
                }
            }
        }

        if (!empty($mergedResponse)) {
            $mergedResponse['organic'] = $allOrganic;
        }

        return $mergedResponse;
    }

    /**
     * Bangun multiple variasi query untuk akurasi lebih tinggi
     */
    public function buildMultiQueries(Alumni $alumni, SumberPelacakan $sumber): array
    {
        $namaKampus = TrackingConfig::getValue('nama_kampus', 'UMM');
        $prodi = $alumni->prodi ?? '';
        $fakultas = $alumni->fakultas ?? '';
        $tahunMasuk = $alumni->tahun_masuk ?? '';
        $tahunLulus = $alumni->tahun_lulus ?? '';
        $namaLengkap = $alumni->nama_lengkap;
        $namaPanggilan = $alumni->nama_panggilan;
        $siteFilter = $sumber->siteFilter();

        $queries = [];

        // --- Query strategies berbeda per platform ---

        if ($sumber === SumberPelacakan::LINKEDIN) {
            // LinkedIn: nama + kampus sangat penting, tambahkan konteks tahun & fakultas
            $queries[] = "\"{$namaLengkap}\" {$namaKampus} {$siteFilter}";

            if ($prodi) {
                $queries[] = "\"{$namaLengkap}\" \"{$prodi}\" {$siteFilter}";
            }

            if ($fakultas) {
                $queries[] = "\"{$namaLengkap}\" \"{$fakultas}\" {$namaKampus} {$siteFilter}";
            }

            // Coba nama panggilan juga (banyak orang pakai nama panggilan di LinkedIn)
            if ($namaPanggilan && $namaPanggilan !== $namaLengkap) {
                $queries[] = "\"{$namaPanggilan}\" {$namaKampus} {$siteFilter}";
            }

        } elseif ($sumber === SumberPelacakan::GOOGLE_SCHOLAR) {
            // Google Scholar: butuh nama + prodi/kampus
            $queries[] = "\"{$namaLengkap}\" {$namaKampus} {$siteFilter}";

            if ($prodi) {
                $queries[] = "\"{$namaLengkap}\" \"{$prodi}\" {$namaKampus} {$siteFilter}";
            }

        } elseif (in_array($sumber, [SumberPelacakan::INSTAGRAM, SumberPelacakan::FACEBOOK, SumberPelacakan::TIKTOK])) {
            // Sosial media: sering pakai nama panggilan, coba keduanya
            $queries[] = "\"{$namaLengkap}\" {$siteFilter}";

            if ($namaPanggilan && $namaPanggilan !== $namaLengkap) {
                $queries[] = "\"{$namaPanggilan}\" {$siteFilter}";
            }

            // Tambahkan konteks kampus untuk mengurangi false positive
            $queries[] = "\"{$namaLengkap}\" {$namaKampus} {$siteFilter}";

        } else {
            // lainnya
            $queries[] = "\"{$namaLengkap}\" {$namaKampus} {$prodi} {$siteFilter}";

            if ($namaPanggilan && $namaPanggilan !== $namaLengkap) {
                $queries[] = "\"{$namaPanggilan}\" {$namaKampus} {$siteFilter}";
            }
        }

        // Deduplikasi query yang sama
        return array_values(array_unique($queries));
    }

    /**
     * Tentukan jumlah hasil pencarian berdasarkan platform (HARDCODED value = 3)
     */
    protected function getNumResults()
    {
        return 3; // Limit maksimal pencarian menjadi 3 per source sesuai permintaan terbaru
    }

    /**
     * Legacy buildQuery — tetap dipertahankan untuk kompatibilitas
     */
    public function buildQuery(Alumni $alumni, SumberPelacakan $sumber): string
    {
        $namaKampus = TrackingConfig::getValue('nama_kampus', 'UMM');
        $prodi = $alumni->prodi ?? '';
        $nama = $alumni->variasi_nama[0] ?? $alumni->nama_lengkap;

        $query = "\"{$nama}\" {$namaKampus} {$prodi} {$sumber->siteFilter()}";

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
        // Extended list of Indonesian cities
        $cities = [
            'Jakarta', 'Surabaya', 'Bandung', 'Malang', 'Yogyakarta', 'Semarang',
            'Medan', 'Makassar', 'Bali', 'Denpasar', 'Bekasi', 'Tangerang', 'Depok',
            'Bogor', 'Palembang', 'Balikpapan', 'Samarinda', 'Manado', 'Padang',
            'Batam', 'Pekanbaru', 'Banjarmasin', 'Pontianak', 'Mataram', 'Kupang',
            'Lampung', 'Solo', 'Surakarta', 'Cirebon', 'Mojokerto', 'Sidoarjo',
            'Gresik', 'Kediri', 'Jember', 'Pasuruan', 'Probolinggo', 'Batu',
        ];
        foreach ($cities as $city) {
            if (stripos($snippet, $city) !== false) {
                return $city;
            }
        }
        return null;
    }
}
