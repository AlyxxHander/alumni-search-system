<?php

namespace App\Http\Controllers;

use App\Jobs\BulkTrackAlumniJob;
use App\Jobs\TrackAlumniJob;
use App\Models\Alumni;
use Illuminate\Http\Request;

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

    /**
     * Bulk tracking menggunakan Playwright scraper + Gemini batch verification.
     * Dispatch beberapa alumni sekaligus per job untuk efisiensi.
     */
    public function runBulk(Request $request)
    {
        $batchSize = (int) $request->input('batch_size', 5);
        $limit = (int) $request->input('limit', 0);

        $query = Alumni::perluDilacak()->orderBy('id');
        if ($limit > 0) {
            $query->take($limit);
        }

        $alumniList = $query->get();

        if ($alumniList->isEmpty()) {
            return back()->with('info', 'Tidak ada alumni yang perlu dilacak.');
        }

        // Dispatch 1 job per chunk
        $chunks = $alumniList->chunk($batchSize);
        foreach ($chunks as $chunk) {
            BulkTrackAlumniJob::dispatch(
                $chunk->pluck('id')->toArray()
            );
        }

        $totalBatches = $chunks->count();

        return back()->with('success',
            "Bulk tracking di-dispatch: {$alumniList->count()} alumni dalam {$totalBatches} batch (@ {$batchSize} alumni/batch)."
        );
    }
}
