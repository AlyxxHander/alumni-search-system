<?php

namespace App\Http\Controllers;

use App\Enums\StatusPelacakan;
use App\Models\Alumni;
use Illuminate\Http\Request;

class AlumniController extends Controller
{
    public function index(Request $request)
    {
        $query = Alumni::query();

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('nama_lengkap', 'like', "%{$search}%")
                  ->orWhere('nim', 'like', "%{$search}%")
                  ->orWhere('prodi', 'like', "%{$search}%")
                  ->orWhere('tempat_bekerja', 'like', "%{$search}%")
                  ->orWhere('posisi', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status')) {
            $query->where('status_pelacakan', $request->status);
        }

        if ($request->filled('prodi')) {
            $query->where('prodi', $request->prodi);
        }

        if ($request->filled('fakultas')) {
            $query->where('fakultas', $request->fakultas);
        }

        if ($request->filled('tahun_lulus')) {
            $query->where('tahun_lulus', $request->tahun_lulus);
        }

        if ($request->filled('jenis_pekerjaan')) {
            $query->where('jenis_pekerjaan', $request->jenis_pekerjaan);
        }

        $alumni = $query->latest()->paginate(15)->withQueryString();
        $prodiList = Alumni::select('prodi')->distinct()->pluck('prodi');
        $tahunList = Alumni::select('tahun_lulus')->distinct()->orderByDesc('tahun_lulus')->pluck('tahun_lulus');
        $fakultasList = Alumni::select('fakultas')->whereNotNull('fakultas')->distinct()->pluck('fakultas');

        return view('alumni.index', compact('alumni', 'prodiList', 'tahunList', 'fakultasList'));
    }

    public function create()
    {
        return view('alumni.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nim' => 'required|string|max:20|unique:alumni,nim',
            'nama_lengkap' => 'required|string|max:255',
            'nama_panggilan' => 'nullable|string|max:100',
            'fakultas' => 'nullable|string|max:150',
            'prodi' => 'required|string|max:100',
            'tahun_masuk' => 'nullable|integer|min:1970|max:' . date('Y'),
            'tanggal_lulus' => 'nullable|string|max:100',
            'tahun_lulus' => 'required|integer|min:1990|max:' . (date('Y') + 1),
            'gelar_akademik' => 'nullable|string|max:50',
        ]);

        $validated['status_pelacakan'] = StatusPelacakan::BELUM_DILACAK;

        Alumni::create($validated);

        return redirect()->route('alumni.index')
            ->with('success', 'Data alumni berhasil ditambahkan.');
    }

    public function show(Alumni $alumni)
    {
        $alumni->load(['trackingResults' => function ($query) {
            $query->orderByDesc('skor_probabilitas');
        }]);

        return view('alumni.show', compact('alumni'));
    }

    public function edit(Alumni $alumni)
    {
        return view('alumni.edit', compact('alumni'));
    }

    public function update(Request $request, Alumni $alumni)
    {
        $validated = $request->validate([
            'nim' => 'required|string|max:20|unique:alumni,nim,' . $alumni->id,
            'nama_lengkap' => 'required|string|max:255',
            'nama_panggilan' => 'nullable|string|max:100',
            'fakultas' => 'nullable|string|max:150',
            'prodi' => 'required|string|max:100',
            'tahun_masuk' => 'nullable|integer|min:1970|max:' . date('Y'),
            'tanggal_lulus' => 'nullable|string|max:100',
            'tahun_lulus' => 'required|integer|min:1990|max:' . (date('Y') + 1),
            'gelar_akademik' => 'nullable|string|max:50',
        ]);

        $alumni->update($validated);

        return redirect()->route('alumni.show', $alumni)
            ->with('success', 'Data alumni berhasil diperbarui.');
    }

    public function destroy(Alumni $alumni)
    {
        $alumni->delete();
        return redirect()->route('alumni.index')
            ->with('success', 'Data alumni berhasil dihapus.');
    }

    public function unvalidate(Alumni $alumni)
    {
        // 1. Set semua hasil pelacakan alumni ini kembali ke PENDING
        $alumni->trackingResults()->update([
            'status_verifikasi' => \App\Enums\StatusVerifikasi::PENDING,
            'verified_by' => null,
            'verified_at' => null,
        ]);

        // 2. Riset data otomatis pada record alumni
        $alumni->update([
            'status_pelacakan' => StatusPelacakan::BUTUH_VERIFIKASI_MANUAL,
            'skor_keseluruhan' => 0,
            // Kosongkan field hasil pelacakan otomatis
            'email' => null,
            'no_hp' => null,
            'linkedin' => null,
            'instagram' => null,
            'facebook' => null,
            'tiktok' => null,
            'tempat_bekerja' => null,
            'alamat_bekerja' => null,
            'posisi' => null,
            'jenis_pekerjaan' => null,
            'sosmed_tempat_bekerja' => null,
        ]);

        return back()->with('success', 'Validasi alumni berhasil dibatalkan. Data pencarian tetap tersimpan untuk verifikasi manual.');
    }
}
