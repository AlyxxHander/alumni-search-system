<?php

namespace App\Services;

use App\Models\Alumni;
use App\Models\TrackingConfig;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Process\Process;

/**
 * Service untuk memanggil Python Playwright scraper.
 * Menggantikan SerperService untuk mode bulk tracking.
 */
class PlaywrightScraperService
{
    /**
     * Jalankan scraper untuk satu batch/chunk alumni.
     *
     * @param Collection $alumniChunk — chunk alumni (5-10 records)
     * @param array $platforms — ['LINKEDIN', 'INSTAGRAM', 'FACEBOOK', 'TIKTOK']
     * @param int $maxResultsPerPlatform
     * @return array — keyed by alumni_id, value = dict keyed by platform
     */
    public function scrapeChunk(Collection $alumniChunk, array $platforms, int $maxResultsPerPlatform = 3): array
    {
        $namaKampus = TrackingConfig::getValue('nama_kampus', 'UMM');

        $input = json_encode([
            'alumni_list' => $alumniChunk->map(fn(Alumni $a) => [
                'id' => $a->id,
                'nama_lengkap' => $a->nama_lengkap,
                'nama_panggilan' => $a->nama_panggilan,
                'prodi' => $a->prodi,
                'fakultas' => $a->fakultas,
                'tahun_masuk' => $a->tahun_masuk,
                'tahun_lulus' => $a->tahun_lulus,
                'nama_kampus' => $namaKampus,
            ])->values()->toArray(),
            'platforms' => $platforms,
            'max_results_per_platform' => $maxResultsPerPlatform,
        ], JSON_UNESCAPED_UNICODE);

        $pythonPath = $this->getPythonPath();
        $scriptPath = base_path('scraper/scraper_service.py');

        $process = new Process([$pythonPath, $scriptPath], base_path('scraper'));
        $process->setInput($input);
        $process->setTimeout(600); // 10 menit per chunk
        $process->setIdleTimeout(120);

        Log::info("PlaywrightScraper: memulai scraping untuk {$alumniChunk->count()} alumni", [
            'alumni_ids' => $alumniChunk->pluck('id')->toArray(),
            'platforms' => $platforms,
        ]);

        try {
            $process->run();
        } catch (\Exception $e) {
            Log::error('PlaywrightScraper: Process exception', [
                'error' => $e->getMessage(),
            ]);
            return [];
        }

        // Log stderr (info/debug output dari Python)
        $stderr = $process->getErrorOutput();
        if ($stderr) {
            Log::info('PlaywrightScraper stderr', ['output' => $stderr]);
        }

        if (!$process->isSuccessful()) {
            Log::error('PlaywrightScraper: Process failed', [
                'exit_code' => $process->getExitCode(),
                'stderr' => $stderr,
            ]);
            return [];
        }

        $output = $process->getOutput();

        try {
            $decoded = json_decode($output, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                Log::error('PlaywrightScraper: Invalid JSON output', [
                    'error' => json_last_error_msg(),
                    'output_preview' => mb_substr($output, 0, 500),
                ]);
                return [];
            }

            // Log errors dari scraper
            if (!empty($decoded['errors'])) {
                Log::warning('PlaywrightScraper: Scraper errors', [
                    'errors' => $decoded['errors'],
                ]);
            }

            return $decoded['results'] ?? [];

        } catch (\Exception $e) {
            Log::error('PlaywrightScraper: Output parse error', [
                'error' => $e->getMessage(),
            ]);
            return [];
        }
    }

    /**
     * Deteksi path ke Python executable.
     */
    protected function getPythonPath(): string
    {
        // Cek dari env/config dulu
        $envPath = config('services.scraper.python_path', env('PYTHON_PATH', ''));
        if ($envPath && file_exists($envPath)) {
            return $envPath;
        }

        // Auto-detect
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            // Windows: coba beberapa lokasi umum
            $candidates = ['python', 'python3', 'py'];
            foreach ($candidates as $candidate) {
                $result = shell_exec("where {$candidate} 2>NUL");
                if ($result) {
                    return trim(explode("\n", $result)[0]);
                }
            }
            return 'python'; // fallback
        }

        // Linux/Mac
        return 'python3';
    }
}
