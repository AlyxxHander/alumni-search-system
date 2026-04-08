<?php

namespace App\Http\Controllers;

use App\Enums\StatusPelacakan;
use App\Models\Alumni;
use App\Models\TrackingResult;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function index(Request $request)
    {
        $query = Alumni::whereIn('status_pelacakan', [
            StatusPelacakan::VALID_OTOMATIS,
            StatusPelacakan::TERVERIFIKASI,
        ]);

        if ($request->filled('prodi')) {
            $query->where('prodi', $request->prodi);
        }

        if ($request->filled('tahun_lulus')) {
            $query->where('tahun_lulus', $request->tahun_lulus);
        }

        $alumni = $query->with(['trackingResults' => function ($q) {
            $q->where('status_verifikasi', 'CONFIRMED')
              ->orWhereHas('alumni', function ($aq) {
                  $aq->where('status_pelacakan', StatusPelacakan::VALID_OTOMATIS);
              });
            $q->orderByDesc('skor_probabilitas');
        }])->latest()->paginate(15)->withQueryString();

        $prodiList = Alumni::select('prodi')->distinct()->pluck('prodi');
        $tahunList = Alumni::select('tahun_lulus')->distinct()->orderByDesc('tahun_lulus')->pluck('tahun_lulus');

        $totalValid = Alumni::where('status_pelacakan', StatusPelacakan::VALID_OTOMATIS)->count();
        $totalVerified = Alumni::where('status_pelacakan', StatusPelacakan::TERVERIFIKASI)->count();

        return view('reports.index', compact('alumni', 'prodiList', 'tahunList', 'totalValid', 'totalVerified'));
    }

    private function getExportData(Request $request)
    {
        $query = Alumni::whereIn('status_pelacakan', [
            StatusPelacakan::VALID_OTOMATIS,
            StatusPelacakan::TERVERIFIKASI,
        ]);

        if ($request->filled('prodi')) {
            $query->where('prodi', $request->prodi);
        }

        if ($request->filled('tahun_lulus')) {
            $query->where('tahun_lulus', $request->tahun_lulus);
        }

        return $query->with(['trackingResults' => function ($q) {
            $q->where('status_verifikasi', 'CONFIRMED')
              ->orWhereHas('alumni', function ($aq) {
                  $aq->where('status_pelacakan', StatusPelacakan::VALID_OTOMATIS);
              });
            $q->orderByDesc('skor_probabilitas');
        }])->latest()->get();
    }

    public function exportCsv(Request $request)
    {
        $alumniData = $this->getExportData($request);

        $filename = "laporan_alumni_valid_" . date('Y-m-d_H-i-s') . ".csv";

        $headers = [
            "Content-type"        => "text/csv",
            "Content-Disposition" => "attachment; filename=$filename",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0"
        ];

        $columns = [
            'No.', 'Nama', 'NIM', 'Jurusan', 'Tahun Lulus', 'Email', 'Nomor Hp',
            'Tempat Bekerja', 'Alamat Bekerja', 'Posisi / Jabatan', 'Kategori Pekerjaan',
            'Linkedin (Alumni)', 'Instagram (Alumni)', 'Facebook (Alumni)', 'Tiktok (Alumni)',
            'Linkedin (Instansi)', 'Instagram (Instansi)', 'Facebook (Instansi)', 'Tiktok (Instansi)'
        ];

        $callback = function() use($alumniData, $columns) {
            $file = fopen('php://output', 'w');
            // UTF-8 BOM for Excel compatibility
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));
            fputcsv($file, $columns);

            $no = 1;
            foreach ($alumniData as $alumni) {
                fputcsv($file, [
                    $no++,
                    $alumni->nama_lengkap,
                    $alumni->nim,
                    $alumni->prodi,
                    $alumni->tahun_lulus,
                    $alumni->email ?? '-',
                    $alumni->no_hp ?? '-',
                    $alumni->tempat_bekerja ?? '-',
                    $alumni->alamat_bekerja ?? '-',
                    $alumni->posisi ?? '-',
                    $alumni->jenis_pekerjaan ?? '-',
                    $alumni->linkedin ?? '-',
                    $alumni->instagram ?? '-',
                    $alumni->facebook ?? '-',
                    $alumni->tiktok ?? '-',
                    $alumni->instansi_linkedin ?? '-',
                    $alumni->instansi_instagram ?? '-',
                    $alumni->instansi_facebook ?? '-',
                    $alumni->instansi_tiktok ?? '-',
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function exportPdf(Request $request)
    {
        $alumniData = $this->getExportData($request);
        
        // Return a dedicated view designed for printing to PDF
        return view('reports.print', compact('alumniData'));
    }
}
