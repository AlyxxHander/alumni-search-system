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
    public int $timeout = 120;

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
        $sumberAktif = TrackingConfig::getValue('sumber_aktif', ['LINKEDIN', 'GOOGLE_SCHOLAR']);

        $allResults = [];
        $rawResponses = [];

        foreach ($sumberAktif as $sumberStr) {
            $sumber = SumberPelacakan::tryFrom($sumberStr);
            if (!$sumber) continue;

            $query = $serper->buildQuery($this->alumni, $sumber);
            $searchResponse = $serper->search($query);

            if (empty($searchResponse)) continue;

            // Store each source response
            $rawResponses[$sumber->value] = $searchResponse;

            $results = $serper->extractResults($searchResponse);
            foreach ($results as $res) {
                $res['sumber_enum'] = $sumber;
                $res['query_digunakan'] = $query;
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
            'prodi' => $this->alumni->prodi ?? 'Tidak diketahui',
            'tahun_lulus' => $this->alumni->tahun_lulus ?? 'Tidak diketahui',
            'gelar_akademik' => $this->alumni->gelar_akademik ?? 'Tidak diketahui',
            'status_pelacakan_saat_ini' => $this->alumni->status_pelacakan->label() ?? 'Belum Dilacak'
        ];

        // Minta Gemini menganalisa semua hasil di dalam 1 batch
        $analysis = $gemini->analyzeIdentityBatch($dataAsli, $allResults);
        
        $skor = $analysis['skor'] ?? 0.0;
        $bestIndex = $analysis['best_index'] ?? 0;
        
        if (!isset($allResults[$bestIndex])) {
            $bestIndex = 0;
        }
        
        $bestResult = $allResults[$bestIndex];

        // Simpan feedback alasan Gemini ke dictionary
        $rawGemini = $analysis['raw_response'] ?? [];
        if (!isset($rawGemini['alasan'])) {
            $rawGemini['alasan'] = $analysis['alasan'];
        }

        // Delete old history before creating a new one
        TrackingResult::where('alumni_id', $this->alumni->id)->delete();

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
            'skor_probabilitas' => $skor,
            'status_verifikasi' => StatusVerifikasi::PENDING,
            'raw_search_response' => $rawResponses,
            'raw_gemini_response' => $rawGemini,
        ]);

        if ($skor > $thresholdAuto) {
            $this->alumni->update(['status_pelacakan' => StatusPelacakan::VALID_OTOMATIS]);
            Log::info("Alumni {$this->alumni->nim}: VALID_OTOMATIS (skor: {$skor})");
        } elseif ($skor >= $thresholdManual) {
            $this->alumni->update(['status_pelacakan' => StatusPelacakan::BUTUH_VERIFIKASI_MANUAL]);
            Log::info("Alumni {$this->alumni->nim}: BUTUH_VERIFIKASI_MANUAL (skor: {$skor})");
        } else {
            $this->alumni->update(['status_pelacakan' => StatusPelacakan::TIDAK_DITEMUKAN]);
            Log::info("Alumni {$this->alumni->nim}: TIDAK_DITEMUKAN (skor terlalu rendah: {$skor})");
        }
    }
}
