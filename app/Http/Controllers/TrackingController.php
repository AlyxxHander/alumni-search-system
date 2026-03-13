<?php

namespace App\Http\Controllers;

use App\Jobs\TrackAlumniJob;
use App\Models\Alumni;

class TrackingController extends Controller
{
    public function runAll()
    {
        $alumniList = Alumni::perluDilacak()->get();

        if ($alumniList->isEmpty()) {
            return back()->with('info', 'Tidak ada alumni yang perlu dilacak.');
        }

        foreach ($alumniList as $alumni) {
            TrackAlumniJob::dispatch($alumni);
        }

        return back()->with('success', "Pelacakan di-dispatch untuk {$alumniList->count()} alumni.");
    }

    public function runSingle(Alumni $alumni)
    {
        TrackAlumniJob::dispatch($alumni);

        // For AJAX requests, return JSON
        if (request()->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => "Pelacakan berhasil untuk: {$alumni->nama_lengkap}"
            ]);
        }

        return back()->with('success', "Pelacakan di-dispatch untuk: {$alumni->nama_lengkap}");
    }
}
