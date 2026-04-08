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
        // 1. Ambil alumni yang butuh verifikasi tapi TIDAK punya record GABUNGAN (Legacy)
        $legacyAlumni = \App\Models\Alumni::where('status_pelacakan', StatusPelacakan::BUTUH_VERIFIKASI_MANUAL)
            ->whereDoesntHave('trackingResults', function($q) {
                $q->where('sumber', \App\Enums\SumberPelacakan::GABUNGAN);
            })
            ->with(['trackingResults' => function($q) {
                $q->where('status_verifikasi', StatusVerifikasi::PENDING)
                  ->orderByDesc('skor_probabilitas');
            }])
            ->get();

        // 2. Untuk setiap legacy alumni, buat "Virtual GABUNGAN" record agar muncul di UI
        foreach ($legacyAlumni as $alumni) {
            $bestResults = $alumni->trackingResults->groupBy('sumber');
            
            $unifiedData = [
                'alumni_id' => $alumni->id,
                'sumber' => \App\Enums\SumberPelacakan::GABUNGAN,
                'judul_profil' => 'Profil Terkonsolidasi (Legacy Data)',
                'skor_probabilitas' => $alumni->skor_keseluruhan ?? 0.5,
                'status_verifikasi' => StatusVerifikasi::PENDING,
                'raw_gemini_response' => ['alasan' => 'Konsolidasi otomatis dari data pelacakan lama (per-platform).'],
            ];

            // Ambil field terbaik dari berbagai sumber yang tersedia
            $fields = ['email', 'no_hp', 'linkedin', 'instagram', 'facebook', 'tiktok', 'tempat_bekerja', 'alamat_bekerja', 'posisi', 'jenis_pekerjaan', 'sosmed_tempat_bekerja'];
            foreach ($fields as $field) {
                $val = $alumni->trackingResults->whereNotNull($field)->first()?->$field;
                if ($val) $unifiedData[$field] = $val;
            }

            // Tambahkan field wajib yang kurang
            $unifiedData['query_digunakan'] = 'Konsolidasi Data Lama';

            // Simpan secara permanen agar di hit berikutnya sudah ada
            TrackingResult::create($unifiedData);
            
            // Tandai semua hasil individual lama sebagai CONFIRMED agar tidak double di "Bukti Pendukung" pendukung jika diinginkan, 
            // tapi biarkan PENDING agar tetap muncul di "Bukti Pendukung" view yang saya buat sebelumnya.
        }

        // 3. Sekarang query seperti biasa (karena semua sudah punya GABUNGAN)
        $results = TrackingResult::with(['alumni', 'alumni.trackingResults' => function($q) {
                $q->where('sumber', '!=', \App\Enums\SumberPelacakan::GABUNGAN)
                  ->orderByDesc('skor_probabilitas');
            }])
            ->where('sumber', \App\Enums\SumberPelacakan::GABUNGAN)
            ->pending()
            ->orderByDesc('skor_probabilitas')
            ->paginate(10);

        return view('verification.index', compact('results'));
    }

    public function confirm(TrackingResult $trackingResult)
    {
        // 1. Update status verifikasi pada record GABUNGAN ini
        $trackingResult->update([
            'status_verifikasi' => StatusVerifikasi::CONFIRMED,
            'verified_by' => auth()->id(),
            'verified_at' => now(),
        ]);

        // 2. Set semua hasil pelacakan lain milik alumni ini ke CONFIRMED (biar hilang dari antrean jika ada yang nyangkut)
        TrackingResult::where('alumni_id', $trackingResult->alumni_id)
            ->where('id', '!=', $trackingResult->id)
            ->pending()
            ->update(['status_verifikasi' => StatusVerifikasi::CONFIRMED]);

        // 3. Update data utama alumni dari hasil GABUNGAN ini
        $fields = [
            'email', 'no_hp', 'linkedin', 'instagram', 'facebook', 'tiktok',
            'tempat_bekerja', 'alamat_bekerja', 'posisi', 'jenis_pekerjaan', 'sosmed_tempat_bekerja'
        ];

        $updateData = ['status_pelacakan' => StatusPelacakan::TERVERIFIKASI];
        foreach ($fields as $field) {
            if (!empty($trackingResult->$field)) {
                $updateData[$field] = $trackingResult->$field;
            }
        }

        $trackingResult->alumni->update($updateData);

        return back()->with('success', 'Data profil alumni telah diverifikasi dan diperbarui.');
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
