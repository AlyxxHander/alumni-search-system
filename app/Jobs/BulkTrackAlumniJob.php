<?php

namespace App\Jobs;

use App\Enums\StatusPelacakan;
use App\Enums\StatusVerifikasi;
use App\Enums\SumberPelacakan;
use App\Models\Alumni;
use App\Models\BulkTrackingLog;
use App\Models\TrackingConfig;
use App\Models\TrackingResult;
use App\Services\GeminiBatchService;
use App\Services\PlaywrightScraperService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Job untuk bulk tracking alumni menggunakan Playwright scraper + Gemini batch verification.
 * Memproses chunk alumni (default 5) dalam satu job.
 */
class BulkTrackAlumniJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;
    public int $timeout = 900; // 15 menit per chunk

    public function __construct(
        public array $alumniIds,
    ) {}

    public function handle(
        PlaywrightScraperService $scraper,
        GeminiBatchService $geminiBatch,
    ): void {
        $batchLog = BulkTrackingLog::create([
            'batch_alumni_ids' => $this->alumniIds,
            'status' => 'processing',
            'total_alumni' => count($this->alumniIds),
        ]);

        try {
            $alumniList = Alumni::whereIn('id', $this->alumniIds)->get();

            if ($alumniList->isEmpty()) {
                $batchLog->update(['status' => 'completed', 'processed_at' => now()]);
                return;
            }

            Log::info("BulkTrack: memulai batch {$alumniList->count()} alumni", [
                'ids' => $this->alumniIds,
            ]);

            // Mark sebagai SEDANG_DILACAK
            Alumni::whereIn('id', $this->alumniIds)
                ->update(['status_pelacakan' => StatusPelacakan::SEDANG_DILACAK]);

            // Refresh data
            $alumniList->each(fn(Alumni $a) => $a->refresh());

            // Ambil konfigurasi
            $thresholdAuto = TrackingConfig::getValue('threshold_valid_otomatis', 0.8);
            $sumberAktif = TrackingConfig::getValue('sumber_aktif', ['LINKEDIN', 'INSTAGRAM', 'FACEBOOK', 'TIKTOK']);

            // Exclude GITHUB dan GOOGLE_SCHOLAR
            $sumberAktif = array_values(array_filter($sumberAktif, function ($s) {
                return !in_array($s, ['GITHUB', 'GOOGLE_SCHOLAR']);
            }));

            // ===== STEP 1: Scrape Google via Playwright =====
            Log::info("BulkTrack: mulai scraping Google...");
            $searchResults = $scraper->scrapeChunk($alumniList, $sumberAktif);

            // ===== STEP 2: Kirim batch ke Gemini =====
            Log::info("BulkTrack: mengirim batch ke Gemini...");
            $verifications = $geminiBatch->verifyBatch($alumniList, $searchResults);

            // ===== STEP 3: Simpan hasil per alumni =====
            $successCount = 0;
            $failedCount = 0;

            foreach ($alumniList as $alumni) {
                try {
                    $alumniId = (string) $alumni->id;
                    $alumniSearchResults = $searchResults[$alumniId] ?? [];
                    $verification = $verifications[$alumniId] ?? null;

                    $this->saveAlumniResults($alumni, $alumniSearchResults, $verification, $thresholdAuto);
                    $successCount++;

                } catch (\Exception $e) {
                    Log::error("BulkTrack: gagal simpan alumni {$alumni->id}: {$e->getMessage()}");
                    $alumni->update(['status_pelacakan' => StatusPelacakan::BELUM_DILACAK]);
                    $failedCount++;
                }
            }

            $batchLog->update([
                'status' => 'completed',
                'success_count' => $successCount,
                'failed_count' => $failedCount,
                'processed_at' => now(),
            ]);

            Log::info("BulkTrack: batch selesai. Success: {$successCount}, Failed: {$failedCount}");

        } catch (\Exception $e) {
            Log::error("BulkTrack: batch gagal total: {$e->getMessage()}");

            // Reset alumni ke BELUM_DILACAK agar bisa di-retry
            Alumni::whereIn('id', $this->alumniIds)
                ->where('status_pelacakan', StatusPelacakan::SEDANG_DILACAK)
                ->update(['status_pelacakan' => StatusPelacakan::BELUM_DILACAK]);

            $batchLog->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
                'processed_at' => now(),
            ]);

            throw $e; // Re-throw agar queue bisa retry
        }
    }

    /**
     * Simpan hasil tracking untuk satu alumni.
     * Logic ini mengikuti pola yang sama dengan TrackAlumniJob::handle().
     */
    protected function saveAlumniResults(
        Alumni $alumni,
        array $platformResults,
        ?array $verification,
        float $thresholdAuto,
    ): void {
        $skorKeseluruhan = $verification['skor_keseluruhan'] ?? 0.0;
        $bestMatches = $verification['best_matches'] ?? [];
        $unifiedData = $verification['unified_data'] ?? [];
        $alasanKeseluruhan = $verification['alasan_keseluruhan'] ?? 'Tidak ada alasan keseluruhan.';

        // Cek apakah ada hasil pencarian sama sekali
        $hasAnyResults = false;
        foreach ($platformResults as $results) {
            if (!empty($results)) {
                $hasAnyResults = true;
                break;
            }
        }

        if (!$hasAnyResults) {
            $alumni->update(['status_pelacakan' => StatusPelacakan::TIDAK_DITEMUKAN]);
            Log::info("BulkTrack: Alumni {$alumni->nim}: TIDAK_DITEMUKAN (tidak ada hasil pencarian)");
            return;
        }

        // Hapus history lama
        TrackingResult::where('alumni_id', $alumni->id)->delete();

        $updateData = [];
        $platformLinksFound = [];

        // Simpan hasil per platform
        foreach ($platformResults as $platform => $results) {
            if (empty($results)) continue;

            $sumber = SumberPelacakan::tryFrom($platform);
            if (!$sumber) continue;

            // Cari best match dari Gemini untuk platform ini
            $matchedObject = null;
            foreach ($bestMatches as $m) {
                if (isset($m['platform']) && strtoupper($m['platform']) === strtoupper($platform)) {
                    $matchedObject = $m;
                    break;
                }
            }

            $bestResult = $results[0]; // Default: hasil pertama
            $matchScore = 0.0;
            $rawGemini = ['alasan' => 'Analisis tidak diekstraksi oleh AI.'];

            if ($matchedObject !== null) {
                $matchScore = $matchedObject['skor'] ?? 0.0;
                $rawGemini['alasan'] = $matchedObject['alasan'] ?? 'Tidak ada alasan.';

                // Gunakan best_index jika valid
                $gIdx = $matchedObject['best_index'] ?? 0;
                if (isset($results[$gIdx])) {
                    $bestResult = $results[$gIdx];
                }
            }

            $extractedData = $matchedObject['extracted_data'] ?? [];
            $queryUsed = $bestResult['query_digunakan'] ?? 'N/A';

            // PASTIKAN LINK PLATFORM TETAP DIMASUKKAN
            $platformKey = strtolower($platform);
            if (in_array($platformKey, ['linkedin', 'instagram', 'facebook', 'tiktok'])) {
                if (empty($extractedData[$platformKey]) && !empty($bestResult['url_profil'])) {
                    $extractedData[$platformKey] = $bestResult['url_profil'];
                }
                if (!empty($bestResult['url_profil'])) {
                    $platformLinksFound[$platformKey] = $bestResult['url_profil'];
                }
            }

            TrackingResult::create([
                'alumni_id' => $alumni->id,
                'sumber' => $sumber,
                'query_digunakan' => $queryUsed,
                'judul_profil' => $bestResult['judul_profil'] ?? null,
                'instansi' => $bestResult['instansi'] ?? null,
                'lokasi' => $bestResult['lokasi'] ?? null,
                'url_profil' => $bestResult['url_profil'] ?? null,
                'foto_url' => $bestResult['foto_url'] ?? null,
                'snippet' => $bestResult['snippet'] ?? null,
                'skor_probabilitas' => $matchScore,
                'status_verifikasi' => StatusVerifikasi::PENDING,
                'raw_search_response' => [$platform => $results],
                'raw_gemini_response' => $rawGemini,
                'email' => $extractedData['email'] ?? null,
                'no_hp' => $extractedData['no_hp'] ?? null,
                'linkedin' => $extractedData['linkedin'] ?? null,
                'instagram' => $extractedData['instagram'] ?? null,
                'facebook' => $extractedData['facebook'] ?? null,
                'tiktok' => $extractedData['tiktok'] ?? null,
                'tempat_bekerja' => $extractedData['tempat_bekerja'] ?? null,
                'alamat_bekerja' => $extractedData['alamat_bekerja'] ?? null,
                'posisi' => $extractedData['posisi'] ?? null,
                'jenis_pekerjaan' => $extractedData['jenis_pekerjaan'] ?? null,
                'sosmed_tempat_bekerja' => $extractedData['sosmed_tempat_bekerja'] ?? null,
                'instansi_linkedin' => $extractedData['instansi_linkedin'] ?? null,
                'instansi_instagram' => $extractedData['instansi_instagram'] ?? null,
                'instansi_facebook' => $extractedData['instansi_facebook'] ?? null,
                'instansi_tiktok' => $extractedData['instansi_tiktok'] ?? null,
            ]);

            // Kumpulkan data untuk update alumni
            if (!empty($extractedData)) {
                $fieldsToExtract = [
                    'email', 'no_hp', 'linkedin', 'instagram', 'facebook', 'tiktok',
                    'tempat_bekerja', 'alamat_bekerja', 'posisi', 'jenis_pekerjaan',
                    'sosmed_tempat_bekerja', 'instansi_linkedin', 'instansi_instagram',
                    'instansi_facebook', 'instansi_tiktok',
                ];

                foreach ($fieldsToExtract as $field) {
                    $value = $extractedData[$field] ?? null;
                    if (!empty($value) && empty($alumni->$field) && empty($updateData[$field])) {
                        if ($field === 'jenis_pekerjaan' && !in_array($value, ['PNS', 'Swasta', 'Wirausaha', 'Lainnya'])) {
                            continue;
                        }
                        $updateData[$field] = $value;
                    }
                }
            }
        }

        // Inject platform links into unified_data if empty
        foreach ($platformLinksFound as $platKey => $platUrl) {
            if (empty($unifiedData[$platKey])) {
                $unifiedData[$platKey] = $platUrl;
            }
        }

        // Simpan hasil GABUNGAN
        TrackingResult::create([
            'alumni_id' => $alumni->id,
            'sumber' => SumberPelacakan::GABUNGAN,
            'query_digunakan' => 'KONSOLIDASI_MULTI_SOURCE_BULK',
            'judul_profil' => 'Profil Terkonsolidasi AI (' . $alumni->nama_lengkap . ')',
            'skor_probabilitas' => $skorKeseluruhan,
            'status_verifikasi' => StatusVerifikasi::PENDING,
            'raw_gemini_response' => [
                'alasan' => $alasanKeseluruhan,
                'unified_data' => $unifiedData,
                'best_matches' => $bestMatches,
            ],
            'email' => $unifiedData['email'] ?? null,
            'no_hp' => $unifiedData['no_hp'] ?? null,
            'linkedin' => $unifiedData['linkedin'] ?? null,
            'instagram' => $unifiedData['instagram'] ?? null,
            'facebook' => $unifiedData['facebook'] ?? null,
            'tiktok' => $unifiedData['tiktok'] ?? null,
            'tempat_bekerja' => $unifiedData['tempat_bekerja'] ?? null,
            'alamat_bekerja' => $unifiedData['alamat_bekerja'] ?? null,
            'posisi' => $unifiedData['posisi'] ?? null,
            'jenis_pekerjaan' => $unifiedData['jenis_pekerjaan'] ?? null,
            'sosmed_tempat_bekerja' => $unifiedData['sosmed_tempat_bekerja'] ?? null,
            'instansi_linkedin' => $unifiedData['instansi_linkedin'] ?? null,
            'instansi_instagram' => $unifiedData['instansi_instagram'] ?? null,
            'instansi_facebook' => $unifiedData['instansi_facebook'] ?? null,
            'instansi_tiktok' => $unifiedData['instansi_tiktok'] ?? null,
        ]);

        // Tentukan status alumni
        if ($skorKeseluruhan > $thresholdAuto) {
            $updateData['status_pelacakan'] = StatusPelacakan::VALID_OTOMATIS;

            // Populate dari unified_data
            foreach ($unifiedData as $field => $val) {
                if (!empty($val) && in_array($field, $alumni->getFillable())) {
                    if ($field === 'jenis_pekerjaan' && !in_array($val, ['PNS', 'Swasta', 'Wirausaha', 'Lainnya'])) continue;
                    $updateData[$field] = $val;
                }
            }

            Log::info("BulkTrack: Alumni {$alumni->nim}: VALID_OTOMATIS (skor: {$skorKeseluruhan})");
        } else {
            $updateData['status_pelacakan'] = StatusPelacakan::BUTUH_VERIFIKASI_MANUAL;
            Log::info("BulkTrack: Alumni {$alumni->nim}: BUTUH_VERIFIKASI_MANUAL (skor: {$skorKeseluruhan})");
        }

        $updateData['skor_keseluruhan'] = $skorKeseluruhan;
        $alumni->update($updateData);
    }
}
