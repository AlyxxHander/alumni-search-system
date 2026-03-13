<?php

namespace App\Http\Controllers;

use App\Enums\StatusPelacakan;
use App\Enums\StatusVerifikasi;
use App\Models\TrackingResult;
use Illuminate\Http\Request;

class VerificationController extends Controller
{
    public function index()
    {
        $results = TrackingResult::with('alumni')
            ->pending()
            ->orderByDesc('skor_probabilitas')
            ->paginate(10);

        return view('verification.index', compact('results'));
    }

    public function confirm(TrackingResult $trackingResult)
    {
        $trackingResult->update([
            'status_verifikasi' => StatusVerifikasi::CONFIRMED,
            'verified_by' => auth()->id(),
            'verified_at' => now(),
        ]);

        $trackingResult->alumni->update([
            'status_pelacakan' => StatusPelacakan::TERVERIFIKASI,
        ]);

        return back()->with('success', 'Data alumni telah dikonfirmasi.');
    }

    public function reject(TrackingResult $trackingResult)
    {
        $trackingResult->update([
            'status_verifikasi' => StatusVerifikasi::REJECTED,
            'verified_by' => auth()->id(),
            'verified_at' => now(),
        ]);

        // Check if alumni has other pending results
        $hasPending = TrackingResult::where('alumni_id', $trackingResult->alumni_id)
            ->pending()
            ->where('id', '!=', $trackingResult->id)
            ->exists();

        if (!$hasPending) {
            $trackingResult->alumni->update([
                'status_pelacakan' => StatusPelacakan::TIDAK_DITEMUKAN,
            ]);
        }

        return back()->with('success', 'Data temuan telah ditolak.');
    }

    public function skip(TrackingResult $trackingResult)
    {
        $trackingResult->update([
            'status_verifikasi' => StatusVerifikasi::SKIPPED,
        ]);

        return back()->with('info', 'Data temuan dilewati.');
    }
}
