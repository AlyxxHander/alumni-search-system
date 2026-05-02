<?php

namespace App\Http\Controllers;

use App\Enums\StatusPelacakan;
use App\Models\Alumni;
use App\Models\BulkTrackingLog;
use App\Models\TrackingResult;

class DashboardController extends Controller
{
    public function index()
    {
        $totalAlumni = Alumni::count();
        $stats = [
            'belum_dilacak' => Alumni::belumDilacak()->count(),
            'valid_otomatis' => Alumni::where('status_pelacakan', StatusPelacakan::VALID_OTOMATIS)->count(),
            'butuh_verifikasi' => Alumni::butuhVerifikasi()->count(),
            'terverifikasi' => Alumni::where('status_pelacakan', StatusPelacakan::TERVERIFIKASI)->count(),
            'tidak_ditemukan' => Alumni::where('status_pelacakan', StatusPelacakan::TIDAK_DITEMUKAN)->count(),
            'sedang_dilacak' => Alumni::where('status_pelacakan', StatusPelacakan::SEDANG_DILACAK)->count(),
        ];

        // Statistik jenis pekerjaan
        $statsJenisPekerjaan = [
            'pns' => Alumni::where('jenis_pekerjaan', 'PNS')->count(),
            'swasta' => Alumni::where('jenis_pekerjaan', 'Swasta')->count(),
            'wirausaha' => Alumni::where('jenis_pekerjaan', 'Wirausaha')->count(),
            'lainnya' => Alumni::where('jenis_pekerjaan', 'Lainnya')->count(),
            'belum_diisi' => Alumni::whereNull('jenis_pekerjaan')->count(),
        ];

        // Statistik kelengkapan data
        $statsKelengkapan = [
            'punya_sosmed' => Alumni::where(function ($q) {
                $q->whereNotNull('linkedin')->orWhereNotNull('instagram')
                  ->orWhereNotNull('facebook')->orWhereNotNull('tiktok');
            })->count(),
            'punya_kontak' => Alumni::where(function ($q) {
                $q->whereNotNull('email')->orWhereNotNull('no_hp');
            })->count(),
            'punya_pekerjaan' => Alumni::whereNotNull('tempat_bekerja')->count(),
        ];

        $pendingVerification = TrackingResult::with('alumni')
            ->pending()
            ->latest()
            ->take(5)
            ->get();

        // Bulk tracking progress
        $bulkStats = BulkTrackingLog::getOverallStats();
        $bulkStats['total_alumni'] = $totalAlumni;
        $bulkStats['processed_alumni'] = $totalAlumni - ($stats['belum_dilacak'] + $stats['sedang_dilacak']);
        $bulkStats['percent'] = $totalAlumni > 0
            ? round(($bulkStats['processed_alumni'] / $totalAlumni) * 100, 1)
            : 0;

        return view('dashboard', compact(
            'totalAlumni', 'stats', 'statsJenisPekerjaan', 'statsKelengkapan',
            'pendingVerification', 'bulkStats'
        ));
    }
}
