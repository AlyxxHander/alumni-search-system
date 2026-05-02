<?php

namespace App\Jobs;

use App\Models\Alumni;
use App\Enums\StatusPelacakan;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class ImportAlumniCsvJob implements ShouldQueue
{
    use Queueable;

    public $timeout = 3600; // 1 hour max

    protected $filePath;
    protected $startLine;
    protected $endLine;

    public function __construct(string $filePath, int $startLine = 2, ?int $endLine = null)
    {
        $this->filePath = $filePath;
        $this->startLine = $startLine;
        $this->endLine = $endLine;
    }

    public function handle(): void
    {
        if (!file_exists($this->filePath)) {
            Log::error("CSV file not found: {$this->filePath}");
            return;
        }

        $handle = fopen($this->filePath, 'r');
        if ($handle === false) {
            Log::error("Cannot open CSV file: {$this->filePath}");
            return;
        }

        // Detect delimiter
        $firstLine = fgets($handle);
        $delimiter = (strpos($firstLine, ';') !== false) ? ';' : ',';
        rewind($handle);

        $currentLine = 1;
        $batch = [];

        while (($data = fgetcsv($handle, 10000, $delimiter)) !== false) {
            if ($currentLine < $this->startLine) {
                $currentLine++;
                continue;
            }

            if ($this->endLine !== null && $currentLine > $this->endLine) {
                break;
            }

            // Ensure we have at least 6 columns: Nama, NIM, Tahun Masuk, Tanggal Lulus, Fakultas, Prodi
            if (count($data) >= 6) {
                $batch[] = $data;
            }

            if (count($batch) >= 500) {
                $this->processBatch($batch);
                $batch = [];
            }

            $currentLine++;
        }

        if (count($batch) > 0) {
            $this->processBatch($batch);
        }

        fclose($handle);

        // Remove the temporary file after processing
        @unlink($this->filePath);
    }

    private function processBatch(array $batch)
    {
        // Extract NIMs to bulk query existing records
        $nims = array_filter(array_map(function($row) { 
            return mb_substr(trim($row[1]), 0, 20); 
        }, $batch));
        
        $existing = Alumni::whereIn('nim', $nims)->get()->keyBy('nim');

        foreach ($batch as $row) {
            $namaLulusan = mb_substr(trim($row[0]), 0, 255);
            $nim = mb_substr(trim($row[1]), 0, 20);
            $tahunMasuk = intval(trim($row[2])) ?: null;
            $tanggalLulus = trim($row[3]);
            $fakultas = mb_substr(trim($row[4]), 0, 150) ?: null;
            $prodi = mb_substr(trim($row[5]), 0, 100) ?: '-';

            if (empty($nim) || empty($namaLulusan)) {
                continue;
            }

            // Parse year from "Tanggal Lulus". Fallback to current year if not match.
            $tahunLulus = date('Y');
            if (preg_match('/\b(19|20)\d{2}\b/', $tanggalLulus, $matches)) {
                $tahunLulus = $matches[0];
            }

            $alumni = $existing->get($nim);

            if ($alumni) {
                // If verified, skip the update
                if ($alumni->status_pelacakan === StatusPelacakan::TERVERIFIKASI) {
                    continue;
                }
                
                // Update
                $alumni->update([
                    'nama_lengkap' => $namaLulusan,
                    'tahun_masuk' => $tahunMasuk,
                    'tanggal_lulus' => $tanggalLulus,
                    'tahun_lulus' => (int) $tahunLulus,
                    'fakultas' => $fakultas,
                    'prodi' => $prodi,
                ]);
            } else {
                // Create new
                Alumni::create([
                    'nim' => $nim,
                    'nama_lengkap' => $namaLulusan,
                    'tahun_masuk' => $tahunMasuk,
                    'tanggal_lulus' => $tanggalLulus,
                    'tahun_lulus' => (int) $tahunLulus,
                    'fakultas' => $fakultas,
                    'prodi' => $prodi,
                    'status_pelacakan' => StatusPelacakan::BELUM_DILACAK,
                ]);
            }
        }
    }
}
