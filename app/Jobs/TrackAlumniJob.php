<?php

namespace App\Jobs;

use App\Enums\StatusPelacakan;
use App\Enums\StatusVerifikasi;
use App\Enums\SumberPelacakan;
use App\Models\Alumni;
use App\Models\TrackingConfig;
use App\Models\TrackingResult;
use App\Services\GeminiService;
use App\Services\SerperService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class TrackAlumniJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 180; // Naikkan timeout karena multi-query

    public function __construct(
        public Alumni $alumni
    ) {}

    public function handle(SerperService $serper, GeminiService $gemini): void
    {
        Log::info("Mulai pelacakan alumni: {$this->alumni->nama_lengkap} ({$this->alumni->nim})");

        // Update status
        $this->alumni->update(['status_pelacakan' => StatusPelacakan::SEDANG_DILACAK]);

        // Refresh data to prevent using old profile data from before editing
        $this->alumni->refresh();

        // Get config
        $thresholdAuto = TrackingConfig::getValue('threshold_valid_otomatis', 0.8);
        $thresholdManual = TrackingConfig::getValue('threshold_verifikasi_manual', 0.5);
        $sumberAktif = TrackingConfig::getValue('sumber_aktif', ['LINKEDIN', 'INSTAGRAM']);

        // Explicitly exclude GITHUB and GOOGLE_SCHOLAR as requested
        $sumberAktif = array_filter($sumberAktif, function($s) {
            return !in_array($s, ['GITHUB', 'GOOGLE_SCHOLAR']);
        });

        $allResults = [];
        $rawResponses = [];

        foreach ($sumberAktif as $sumberStr) {
            $sumber = SumberPelacakan::tryFrom($sumberStr);
            if (!$sumber) continue;

            // Gunakan multi-query search untuk akurasi lebih baik
            $searchResponse = $serper->searchMultiQuery($this->alumni, $sumber);

            if (empty($searchResponse)) continue;

            // Store each source response
            $rawResponses[$sumber->value] = $searchResponse;

            // Ambil maksimal 3 hasil saja per source sesuai permintaan
            $results = array_slice($serper->extractResults($searchResponse), 0, 3);
            foreach ($results as $res) {
                $res['sumber_enum'] = $sumber;
                // Simpan query yang digunakan (gabungan semua variasi)
                $queries = $serper->buildMultiQueries($this->alumni, $sumber);
                $res['query_digunakan'] = implode(' | ', $queries);
                $allResults[] = $res;
            }
        }

        if (empty($allResults)) {
            $this->alumni->update(['status_pelacakan' => StatusPelacakan::TIDAK_DITEMUKAN]);
            Log::info("Alumni {$this->alumni->nim}: TIDAK_DITEMUKAN (Tidak ada hasil dari mesin pencari)");
            return;
        }

        $dataAsli = [
            'nim' => $this->alumni->nim ?? 'Tidak diketahui',
            'nama_lengkap' => $this->alumni->nama_lengkap ?? 'Tidak diketahui',
            'nama_panggilan' => $this->alumni->nama_panggilan ?? 'Tidak diketahui',
            'fakultas' => $this->alumni->fakultas ?? 'Tidak diketahui',
            'prodi' => $this->alumni->prodi ?? 'Tidak diketahui',
            'tahun_masuk' => $this->alumni->tahun_masuk ?? 'Tidak diketahui',
            'tanggal_lulus' => $this->alumni->tanggal_lulus ?? 'Tidak diketahui',
            'tahun_lulus' => $this->alumni->tahun_lulus ?? 'Tidak diketahui',
            'gelar_akademik' => $this->alumni->gelar_akademik ?? 'Tidak diketahui',
            'status_pelacakan_saat_ini' => $this->alumni->status_pelacakan->label() ?? 'Belum Dilacak'
        ];

        // Minta Gemini menganalisa semua hasil di dalam 1 batch
        $analysis = $gemini->analyzeIdentityBatch($dataAsli, $allResults);
        
        $skorKeseluruhan = $analysis['skor_keseluruhan'] ?? 0.0;
        $bestMatches = $analysis['best_matches'] ?? [];
        $alasanKeseluruhan = $analysis['alasan_keseluruhan'] ?? 'Tidak ada alasan keseluruhan yang diberikan';

        // Delete old history before creating a new one
        TrackingResult::where('alumni_id', $this->alumni->id)->delete();

        $updateData = [];

        // Kumpulkan rentang indeks per platform
        $platformIndices = [];
        foreach ($allResults as $idx => $res) {
            $platformIndices[$res['sumber_enum']->value][] = $idx;
        }

        // Loop melalui platform yang benar-benar ada hasil pencariannya
        foreach ($platformIndices as $platformStr => $indices) {
            // Cari match dari gemini untuk platform ini
            $matchedObject = null;
            foreach ($bestMatches as $m) {
                if (isset($m['platform']) && $m['platform'] === $platformStr) {
                    $matchedObject = $m;
                    break;
                }
            }

            $bestIndex = $indices[0]; // fallback ke index pertama jika gemini bingung
            $matchScore = 0.0;
            $rawGemini = ['alasan' => 'Analisis tidak diekstraksi/terlewat oleh AI.'];

            if ($matchedObject !== null) {
                $matchScore = $matchedObject['skor'] ?? 0.0;
                $rawGemini['alasan'] = $matchedObject['alasan'] ?? 'Tidak ada alasan profil.';
                
                // Pastikan indeks gemini valid dan benar-benar milik platform ini
                $gIdx = $matchedObject['best_index'] ?? -1;
                if (in_array($gIdx, $indices)) {
                    $bestIndex = $gIdx;
                }
            }
            
            $bestResult = $allResults[$bestIndex];

            $extractedData = $matchedObject['extracted_data'] ?? [];

            TrackingResult::create([
                'alumni_id' => $this->alumni->id,
                'sumber' => $bestResult['sumber_enum'],
                'query_digunakan' => $bestResult['query_digunakan'],
                'judul_profil' => $bestResult['judul_profil'],
                'instansi' => $bestResult['instansi'],
                'lokasi' => $bestResult['lokasi'],
                'url_profil' => $bestResult['url_profil'],
                'foto_url' => $bestResult['foto_url'],
                'snippet' => $bestResult['snippet'],
                'skor_probabilitas' => $matchScore,
                'status_verifikasi' => StatusVerifikasi::PENDING,
                // Hanya simpan raw_search_response milik sumber ini agar tidak redundan besarnya
                'raw_search_response' => [$bestResult['sumber_enum']->value => $rawResponses[$bestResult['sumber_enum']->value] ?? []],
                'raw_gemini_response' => $rawGemini,
                
                // Data ekstraksi per platform
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

            // Ekstrak data dari semua profil, valid atau tidak valid, sesuai permintaan pengguna
            if ($matchedObject !== null) {
                // $extractedData sudah didefinisikan di atas
                if (!empty($extractedData)) {
                    $fieldsToExtract = [
                        'email', 'no_hp', 'linkedin', 'instagram', 'facebook', 'tiktok',
                        'tempat_bekerja', 'alamat_bekerja', 'posisi', 'jenis_pekerjaan',
                        'sosmed_tempat_bekerja', 'instansi_linkedin', 'instansi_instagram',
                        'instansi_facebook', 'instansi_tiktok',
                    ];

                    foreach ($fieldsToExtract as $field) {
                        $value = $extractedData[$field] ?? null;
                        if (!empty($value) && empty($this->alumni->$field) && empty($updateData[$field])) {
                            if ($field === 'jenis_pekerjaan' && !in_array($value, ['PNS', 'Swasta', 'Wirausaha', 'Lainnya'])) {
                                continue;
                            }
                            $updateData[$field] = $value;
                        }
                    }
                }
            }
        }

        // 2. Simpan hasil GABUNGAN (sebagai data utama yang diverifikasi)
        if (!empty($allResults)) {
            $unified = $analysis['unified_data'] ?? [];
            TrackingResult::create([
                'alumni_id' => $this->alumni->id,
                'sumber' => \App\Enums\SumberPelacakan::GABUNGAN,
                'query_digunakan' => 'KONSOLIDASI_MULTI_SOURCE',
                'judul_profil' => 'Profil Terkonsolidasi AI (' . $this->alumni->nama_lengkap . ')',
                'skor_probabilitas' => $skorKeseluruhan,
                'status_verifikasi' => StatusVerifikasi::PENDING,
                'raw_gemini_response' => [
                    'alasan' => $analysis['alasan_keseluruhan'],
                    'unified_data' => $unified,
                    'best_matches' => $analysis['best_matches'], // Simpan referensi ke bukti
                ],
                // Data gabungan
                'email' => $unified['email'] ?? null,
                'no_hp' => $unified['no_hp'] ?? null,
                'linkedin' => $unified['linkedin'] ?? null,
                'instagram' => $unified['instagram'] ?? null,
                'facebook' => $unified['facebook'] ?? null,
                'tiktok' => $unified['tiktok'] ?? null,
                'tempat_bekerja' => $unified['tempat_bekerja'] ?? null,
                'alamat_bekerja' => $unified['alamat_bekerja'] ?? null,
                'posisi' => $unified['posisi'] ?? null,
                'jenis_pekerjaan' => $unified['jenis_pekerjaan'] ?? null,
                'sosmed_tempat_bekerja' => $unified['sosmed_tempat_bekerja'] ?? null,
                'instansi_linkedin' => $unified['instansi_linkedin'] ?? null,
                'instansi_instagram' => $unified['instansi_instagram'] ?? null,
                'instansi_facebook' => $unified['instansi_facebook'] ?? null,
                'instansi_tiktok' => $unified['instansi_tiktok'] ?? null,
            ]);
        }

        if ($skorKeseluruhan > $thresholdAuto) {
            $updateData['status_pelacakan'] = StatusPelacakan::VALID_OTOMATIS;
            // Jika valid otomatis, langsung populate alumni dari unified_data
            $unified = $analysis['unified_data'] ?? [];
            foreach ($unified as $field => $val) {
                if (!empty($val) && in_array($field, $this->alumni->getFillable())) {
                    if ($field === 'jenis_pekerjaan' && !in_array($val, ['PNS', 'Swasta', 'Wirausaha', 'Lainnya'])) continue;
                    $updateData[$field] = $val;
                }
            }
            Log::info("Alumni {$this->alumni->nim}: VALID_OTOMATIS (skor keseluruhan: {$skorKeseluruhan})");
        } else {
            // Karena kita sudah cek di atas bahwa !empty($allResults), maka sisanya masuk verifikasi manual
            $updateData['status_pelacakan'] = StatusPelacakan::BUTUH_VERIFIKASI_MANUAL;
            Log::info("Alumni {$this->alumni->nim}: BUTUH_VERIFIKASI_MANUAL (skor keseluruhan: {$skorKeseluruhan})");
        }
        
        $updateData['skor_keseluruhan'] = $skorKeseluruhan;
        $this->alumni->update($updateData);
    }
}
