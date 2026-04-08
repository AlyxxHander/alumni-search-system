<?php

namespace App\Http\Controllers;

use App\Models\TrackingConfig;
use Illuminate\Http\Request;

class ConfigController extends Controller
{
    public function index()
    {
        $config = [
            'afiliasi_utama' => TrackingConfig::getValue('afiliasi_utama', ['UMM', 'Universitas Muhammadiyah Malang', 'Informatika']),
            'threshold_valid_otomatis' => TrackingConfig::getValue('threshold_valid_otomatis', 0.8),
            'threshold_verifikasi_manual' => TrackingConfig::getValue('threshold_verifikasi_manual', 0.5),
            'sumber_aktif' => TrackingConfig::getValue('sumber_aktif', ['GOOGLE_SCHOLAR', 'LINKEDIN', 'GITHUB']),
        ];

        return view('config.index', compact('config'));
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'afiliasi_utama' => 'required|string',
            'threshold_valid_otomatis' => 'required|numeric|min:0|max:1',
            'threshold_verifikasi_manual' => 'required|numeric|min:0|max:1',
            'sumber_aktif' => 'required|array|min:1',
            'sumber_aktif.*' => 'in:GOOGLE_SCHOLAR,LINKEDIN,GITHUB,INSTAGRAM,FACEBOOK,TIKTOK',
        ]);

        // Convert comma-separated string to array
        $afiliasi = array_map('trim', explode(',', $validated['afiliasi_utama']));

        TrackingConfig::setValue('afiliasi_utama', $afiliasi);
        TrackingConfig::setValue('threshold_valid_otomatis', (float) $validated['threshold_valid_otomatis']);
        TrackingConfig::setValue('threshold_verifikasi_manual', (float) $validated['threshold_verifikasi_manual']);
        TrackingConfig::setValue('sumber_aktif', $validated['sumber_aktif']);

        return back()->with('success', 'Konfigurasi berhasil disimpan.');
    }
}
