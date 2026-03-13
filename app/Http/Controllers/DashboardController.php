<?php

namespace App\Http\Controllers;

use App\Enums\StatusPelacakan;
use App\Models\Alumni;
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

        $pendingVerification = TrackingResult::with('alumni')
            ->pending()
            ->latest()
            ->take(5)
            ->get();

        return view('dashboard', compact('totalAlumni', 'stats', 'pendingVerification'));
    }
}
