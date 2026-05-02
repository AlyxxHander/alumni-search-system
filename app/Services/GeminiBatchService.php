<?php

namespace App\Services;

use App\Models\Alumni;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Service untuk batch verification menggunakan Gemini API.
 * Mengirim beberapa alumni sekaligus dalam satu request untuk menghemat API quota.
 */
class GeminiBatchService
{
    protected string $apiKey;
    protected string $model;
    protected string $baseUrl;

    public function __construct()
    {
        $this->apiKey = config('services.gemini.api_key', env('GEMINI_API_KEY', ''));
        $this->model = config('services.gemini.model', env('GEMINI_MODEL', 'gemini-3.1-flash-lite-preview'));
        $this->baseUrl = "https://generativelanguage.googleapis.com/v1beta/models/{$this->model}:generateContent";
    }

    /**
     * Verifikasi batch alumni beserta search results mereka.
     *
     * @param Collection $alumniList — Collection of Alumni models
     * @param array $searchResults — keyed by alumni_id, value = dict of platform results
     * @return array — keyed by alumni_id, value = verification result
     */
    public function verifyBatch(Collection $alumniList, array $searchResults): array
    {
        $prompt = $this->buildBatchMultiAlumniPrompt($alumniList, $searchResults);

        $maxRetries = 3;
        $retryDelay = 5;

        for ($attempt = 1; $attempt <= $maxRetries; $attempt++) {
            try {
                $response = Http::timeout(120)->withHeaders([
                    'Content-Type' => 'application/json',
                ])->post("{$this->baseUrl}?key={$this->apiKey}", [
                    'contents' => [
                        [
                            'parts' => [
                                ['text' => $prompt],
                            ],
                        ],
                    ],
                    'generationConfig' => [
                        'responseMimeType' => 'application/json',
                        'responseSchema' => $this->buildBatchResponseSchema(),
                    ],
                ]);

                if ($response->successful()) {
                    $data = $response->json();
                    $text = $data['candidates'][0]['content']['parts'][0]['text'] ?? null;

                    if ($text) {
                        // Strip markdown code fences if present
                        if (preg_match('/```(?:json)?(.*?)```/s', $text, $matches)) {
                            $text = trim($matches[1]);
                        }

                        $result = json_decode($text, true);

                        if ($result && isset($result['verifications'])) {
                            return $this->mapVerificationsToAlumniIds($result['verifications'], $alumniList);
                        }

                        Log::error('GeminiBatch: Parse error, missing verifications key', [
                            'text_preview' => mb_substr($text, 0, 500),
                        ]);
                        return $this->buildEmptyResults($alumniList);
                    }
                }

                if ($response->status() === 503 || $response->status() === 429) {
                    if ($attempt < $maxRetries) {
                        Log::warning("GeminiBatch: API error {$response->status()}. Retrying in {$retryDelay}s (Attempt {$attempt}/{$maxRetries})");
                        sleep($retryDelay);
                        $retryDelay *= 2; // exponential backoff
                        continue;
                    }
                }

                Log::error('GeminiBatch: API error', [
                    'status' => $response->status(),
                    'body_preview' => mb_substr($response->body(), 0, 500),
                ]);

                return $this->buildEmptyResults($alumniList);

            } catch (\Exception $e) {
                if ($attempt < $maxRetries) {
                    Log::warning("GeminiBatch: Exception {$e->getMessage()}. Retrying in {$retryDelay}s");
                    sleep($retryDelay);
                    $retryDelay *= 2;
                    continue;
                }
                Log::error('GeminiBatch: Exception', ['error' => $e->getMessage()]);
                return $this->buildEmptyResults($alumniList);
            }
        }
        
        return $this->buildEmptyResults($alumniList);

    }

    /**
     * Build prompt yang menggabungkan beberapa alumni sekaligus.
     */
    protected function buildBatchMultiAlumniPrompt(Collection $alumniList, array $searchResults): string
    {
        $alumniBatchText = "";

        foreach ($alumniList as $batchIndex => $alumni) {
            $alumniId = (string) $alumni->id;
            $alumniBatchText .= $this->buildSingleAlumniSection($batchIndex, $alumni, $searchResults[$alumniId] ?? []);
        }

        $tahunSekarang = date('Y');

        return <<<PROMPT
Anda adalah "Verifikator Alumni Batch", sebuah sistem AI pintar untuk mendeteksi kecocokan identitas dan mengekstrak informasi kontak/pekerjaan secara massal.

Anda akan menerima DATA REFERENSI dan HASIL PENCARIAN INTERNET untuk BEBERAPA alumni sekaligus. Tugas Anda adalah menganalisis SETIAP alumni secara independen.

Tahun saat ini: {$tahunSekarang}

===== DATA BATCH ALUMNI =====
{$alumniBatchText}
===== AKHIR DATA BATCH =====

[INSTRUKSI WAJIB UNTUK PENILAIAN]
1. Proses SETIAP alumni dalam batch ini secara TERPISAH dan INDEPENDEN.
2. Untuk SETIAP alumni, cari keselarasan kuat antara Data Referensi dan Hasil Pencarian. Profil yang valid biasanya menyebutkan Nama yang mirip DAN/ATAU Program Studi DAN kampus terkait.
3. PEMILIHAN PER PLATFORM: Untuk setiap alumni, pilih SATU hasil terbaik untuk SETIAP platform yang ada. Meski semua hasil tidak cocok, tetap pilih satu dan beri skor 0.0.
4. SKOR INDIVIDUAL per profil (0.0 - 1.0):
   - 0.8-1.0: Nama, kampus, akademik, pekerjaan jelas cocok.
   - 0.5-0.7: Kemiripan nama tapi konteks parsial.
   - 0.1-0.4: Nama agak mirip, beda konteks.
   - 0.0: Tidak ada kecocokan (irrelevan).
5. SKOR KESELURUHAN per alumni: Gabungkan keyakinan dari semua platform.

[INSTRUKSI EKSTRAKSI DATA]
6. Untuk SETIAP profil dalam best_matches, ekstrak informasi kontak/pekerjaan ke extracted_data.
7. UNIFIED_DATA: Buat profil gabungan paling akurat per alumni dari semua platform.
8. Jika ada konflik data, prioritaskan LinkedIn (paling update).
9. Lakukan ekstraksi bahkan jika skor rendah.
10. Jika informasi tidak ditemukan, biarkan string kosong "". Jangan mengarang data.

[FORMAT OUTPUT]
Output HARUS berisi array "verifications" dengan satu objek per alumni, sesuai batch_index (0, 1, 2, ...).
PROMPT;
    }

    /**
     * Build section per alumni dalam prompt.
     */
    protected function buildSingleAlumniSection(int $batchIndex, Alumni $alumni, array $platformResults): string
    {
        $estimasiKarir = '';
        if ($alumni->tahun_lulus && $alumni->tahun_lulus !== 'Tidak diketahui') {
            $lamaKerja = date('Y') - (int) $alumni->tahun_lulus;
            if ($lamaKerja > 0) {
                $estimasiKarir = "Alumni ini sudah lulus sekitar {$lamaKerja} tahun yang lalu.";
            }
        }

        $section = "\n--- ALUMNI batch_index={$batchIndex} ---\n";
        $section .= "[DATA REFERENSI]\n";
        $section .= "- NIM: " . ($alumni->nim ?? 'Tidak diketahui') . "\n";
        $section .= "- Nama Lengkap: " . ($alumni->nama_lengkap ?? 'Tidak diketahui') . "\n";
        $section .= "- Nama Panggilan: " . ($alumni->nama_panggilan ?? 'Tidak diketahui') . "\n";
        $section .= "- Fakultas: " . ($alumni->fakultas ?? 'Tidak diketahui') . "\n";
        $section .= "- Program Studi: " . ($alumni->prodi ?? 'Tidak diketahui') . "\n";
        $section .= "- Tahun Masuk: " . ($alumni->tahun_masuk ?? 'Tidak diketahui') . "\n";
        $section .= "- Tanggal Lulus: " . ($alumni->tanggal_lulus ?? 'Tidak diketahui') . "\n";
        $section .= "- Tahun Lulus: " . ($alumni->tahun_lulus ?? 'Tidak diketahui') . "\n";
        $section .= "- Gelar Akademik: " . ($alumni->gelar_akademik ?? 'Tidak diketahui') . "\n";
        if ($estimasiKarir) {
            $section .= "- {$estimasiKarir}\n";
        }

        $section .= "\n[HASIL PENCARIAN INTERNET]\n";

        $globalIndex = 0;
        if (empty($platformResults)) {
            $section .= "Tidak ada hasil pencarian internet.\n";
        } else {
            foreach ($platformResults as $platform => $results) {
                if (empty($results)) continue;
                foreach ($results as $result) {
                    $judul = $result['judul_profil'] ?? 'Tidak tersedia';
                    $instansi = $result['instansi'] ?? 'Tidak tersedia';
                    $lokasi = $result['lokasi'] ?? 'Tidak tersedia';
                    $snippet = $result['snippet'] ?? 'Tidak tersedia';
                    $url = $result['url_profil'] ?? 'Tidak tersedia';

                    $section .= "\n[INDEX {$globalIndex}] (Sumber: {$platform})\n";
                    $section .= "- Judul: {$judul}\n";
                    $section .= "- Instansi: {$instansi}\n";
                    $section .= "- Lokasi: {$lokasi}\n";
                    $section .= "- Snippet: {$snippet}\n";
                    $section .= "- URL: {$url}\n";

                    $globalIndex++;
                }
            }
        }

        return $section;
    }

    /**
     * Build response schema untuk batch verification.
     */
    protected function buildBatchResponseSchema(): array
    {
        $extractedDataSchema = [
            'type' => 'object',
            'properties' => [
                'email' => ['type' => 'string'],
                'no_hp' => ['type' => 'string'],
                'linkedin' => ['type' => 'string'],
                'instagram' => ['type' => 'string'],
                'facebook' => ['type' => 'string'],
                'tiktok' => ['type' => 'string'],
                'tempat_bekerja' => ['type' => 'string'],
                'alamat_bekerja' => ['type' => 'string'],
                'posisi' => ['type' => 'string'],
                'jenis_pekerjaan' => ['type' => 'string', 'description' => 'PNS, Swasta, Wirausaha, atau Lainnya'],
                'sosmed_tempat_bekerja' => ['type' => 'string'],
                'instansi_linkedin' => ['type' => 'string'],
                'instansi_instagram' => ['type' => 'string'],
                'instansi_facebook' => ['type' => 'string'],
                'instansi_tiktok' => ['type' => 'string'],
            ],
        ];

        return [
            'type' => 'object',
            'properties' => [
                'verifications' => [
                    'type' => 'array',
                    'description' => 'Satu objek verifikasi per alumni dalam batch, sesuai urutan batch_index.',
                    'items' => [
                        'type' => 'object',
                        'properties' => [
                            'batch_index' => ['type' => 'integer', 'description' => 'Index alumni dalam batch (0-based)'],
                            'best_matches' => [
                                'type' => 'array',
                                'items' => [
                                    'type' => 'object',
                                    'properties' => [
                                        'platform' => ['type' => 'string'],
                                        'best_index' => ['type' => 'integer'],
                                        'skor' => ['type' => 'number'],
                                        'alasan' => ['type' => 'string'],
                                        'extracted_data' => $extractedDataSchema,
                                    ],
                                    'required' => ['platform', 'best_index', 'skor', 'alasan', 'extracted_data'],
                                ],
                            ],
                            'unified_data' => $extractedDataSchema,
                            'skor_keseluruhan' => [
                                'type' => 'number',
                                'description' => 'Skor total (0.0-1.0) keyakinan bahwa profil gabungan merujuk alumni yang benar.',
                            ],
                            'alasan_keseluruhan' => [
                                'type' => 'string',
                                'description' => 'Ringkasan bukti gabungan dari berbagai platform.',
                            ],
                        ],
                        'required' => ['batch_index', 'best_matches', 'unified_data', 'skor_keseluruhan', 'alasan_keseluruhan'],
                    ],
                ],
            ],
            'required' => ['verifications'],
        ];
    }

    /**
     * Map verifications dari Gemini response ke alumni IDs.
     */
    protected function mapVerificationsToAlumniIds(array $verifications, Collection $alumniList): array
    {
        $mapped = [];
        $alumniArray = $alumniList->values();

        foreach ($verifications as $v) {
            $batchIndex = $v['batch_index'] ?? -1;

            if ($batchIndex >= 0 && $batchIndex < $alumniArray->count()) {
                $alumniId = (string) $alumniArray[$batchIndex]->id;
                $mapped[$alumniId] = [
                    'best_matches' => $v['best_matches'] ?? [],
                    'unified_data' => $v['unified_data'] ?? [],
                    'skor_keseluruhan' => (float) min(1.0, max(0.0, $v['skor_keseluruhan'] ?? 0.0)),
                    'alasan_keseluruhan' => $v['alasan_keseluruhan'] ?? 'Tidak ada alasan yang diberikan',
                ];
            }
        }

        // Fill empty results for missing alumni
        foreach ($alumniArray as $alumni) {
            $id = (string) $alumni->id;
            if (!isset($mapped[$id])) {
                $mapped[$id] = [
                    'best_matches' => [],
                    'unified_data' => [],
                    'skor_keseluruhan' => 0.0,
                    'alasan_keseluruhan' => 'Alumni ini tidak diproses oleh AI.',
                ];
            }
        }

        return $mapped;
    }

    /**
     * Build empty results untuk semua alumni jika API gagal.
     */
    protected function buildEmptyResults(Collection $alumniList): array
    {
        $results = [];
        foreach ($alumniList as $alumni) {
            $results[(string) $alumni->id] = [
                'best_matches' => [],
                'unified_data' => [],
                'skor_keseluruhan' => 0.0,
                'alasan_keseluruhan' => 'Gagal menganalisis. API error.',
            ];
        }
        return $results;
    }
}
