@extends('layouts.app')
@section('title', 'Dashboard')

@section('content')
{{-- Stats Cards - Pelacakan --}}
<div class="mb-6">
    <h3 class="text-sm font-semibold text-gray-600 uppercase tracking-wider mb-3">📊 Status Pelacakan</h3>
    <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-6 gap-4">
        <div class="bg-white rounded-lg shadow p-4">
            <p class="text-xs text-gray-500 uppercase">Total Alumni</p>
            <p class="text-2xl font-bold text-gray-800">{{ $totalAlumni }}</p>
        </div>
        <div class="bg-white rounded-lg shadow p-4">
            <p class="text-xs text-gray-500 uppercase">Belum Dilacak</p>
            <p class="text-2xl font-bold text-gray-500">{{ $stats['belum_dilacak'] }}</p>
        </div>
        <div class="bg-white rounded-lg shadow p-4">
            <p class="text-xs text-gray-500 uppercase">Sedang Dilacak</p>
            <p class="text-2xl font-bold text-blue-600">{{ $stats['sedang_dilacak'] }}</p>
        </div>
        <div class="bg-white rounded-lg shadow p-4">
            <p class="text-xs text-gray-500 uppercase">Valid Otomatis</p>
            <p class="text-2xl font-bold text-green-600">{{ $stats['valid_otomatis'] }}</p>
        </div>
        <div class="bg-white rounded-lg shadow p-4">
            <p class="text-xs text-gray-500 uppercase">Butuh Verifikasi</p>
            <p class="text-2xl font-bold text-yellow-600">{{ $stats['butuh_verifikasi'] }}</p>
        </div>
        <div class="bg-white rounded-lg shadow p-4">
            <p class="text-xs text-gray-500 uppercase">Terverifikasi</p>
            <p class="text-2xl font-bold text-emerald-600">{{ $stats['terverifikasi'] }}</p>
        </div>
    </div>
</div>

{{-- Stats Cards - Jenis Pekerjaan & Kelengkapan --}}
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
    <div>
        <h3 class="text-sm font-semibold text-gray-600 uppercase tracking-wider mb-3">🏢 Jenis Pekerjaan</h3>
        <div class="grid grid-cols-2 md:grid-cols-5 gap-3">
            <div class="bg-white rounded-lg shadow p-4">
                <p class="text-xs text-gray-500 uppercase">PNS</p>
                <p class="text-xl font-bold text-blue-600">{{ $statsJenisPekerjaan['pns'] }}</p>
            </div>
            <div class="bg-white rounded-lg shadow p-4">
                <p class="text-xs text-gray-500 uppercase">Swasta</p>
                <p class="text-xl font-bold text-green-600">{{ $statsJenisPekerjaan['swasta'] }}</p>
            </div>
            <div class="bg-white rounded-lg shadow p-4">
                <p class="text-xs text-gray-500 uppercase">Wirausaha</p>
                <p class="text-xl font-bold text-orange-600">{{ $statsJenisPekerjaan['wirausaha'] }}</p>
            </div>
            <div class="bg-white rounded-lg shadow p-4">
                <p class="text-xs text-gray-500 uppercase">Lainnya</p>
                <p class="text-xl font-bold text-gray-600">{{ $statsJenisPekerjaan['lainnya'] }}</p>
            </div>
            <div class="bg-white rounded-lg shadow p-4">
                <p class="text-xs text-gray-500 uppercase">Belum Diisi</p>
                <p class="text-xl font-bold text-gray-400">{{ $statsJenisPekerjaan['belum_diisi'] }}</p>
            </div>
        </div>
    </div>
    <div>
        <h3 class="text-sm font-semibold text-gray-600 uppercase tracking-wider mb-3">📋 Kelengkapan Data</h3>
        <div class="grid grid-cols-3 gap-3">
            <div class="bg-white rounded-lg shadow p-4">
                <p class="text-xs text-gray-500 uppercase">Punya Sosmed</p>
                <p class="text-xl font-bold text-pink-600">{{ $statsKelengkapan['punya_sosmed'] }}</p>
            </div>
            <div class="bg-white rounded-lg shadow p-4">
                <p class="text-xs text-gray-500 uppercase">Punya Kontak</p>
                <p class="text-xl font-bold text-indigo-600">{{ $statsKelengkapan['punya_kontak'] }}</p>
            </div>
            <div class="bg-white rounded-lg shadow p-4">
                <p class="text-xs text-gray-500 uppercase">Punya Pekerjaan</p>
                <p class="text-xl font-bold text-purple-600">{{ $statsKelengkapan['punya_pekerjaan'] }}</p>
            </div>
        </div>
    </div>
</div>

{{-- Bulk Tracking Progress --}}
<div class="mb-6">
    <h3 class="text-sm font-semibold text-gray-600 uppercase tracking-wider mb-3">🚀 Bulk Tracking Progress</h3>
    <div class="bg-white rounded-lg shadow p-6">
        {{-- Progress Bar --}}
        <div class="mb-4">
            <div class="flex justify-between items-center mb-2">
                <span class="text-sm font-medium text-gray-700">Progress Pelacakan</span>
                <span class="text-sm font-bold text-blue-600">{{ $bulkStats['percent'] }}%</span>
            </div>
            <div class="w-full bg-gray-200 rounded-full h-3">
                <div class="bg-gradient-to-r from-blue-500 to-blue-600 h-3 rounded-full transition-all duration-500"
                     style="width: {{ min($bulkStats['percent'], 100) }}%"></div>
            </div>
            <div class="flex justify-between text-xs text-gray-500 mt-1">
                <span>{{ number_format($bulkStats['processed_alumni']) }} terproses</span>
                <span>{{ number_format($bulkStats['total_alumni']) }} total</span>
            </div>
        </div>

        {{-- Batch Stats --}}
        <div class="grid grid-cols-2 md:grid-cols-5 gap-3 mb-4">
            <div class="bg-gray-50 rounded-lg p-3 text-center">
                <p class="text-xs text-gray-500">Batch Total</p>
                <p class="text-lg font-bold text-gray-700">{{ $bulkStats['total_batches'] }}</p>
            </div>
            <div class="bg-yellow-50 rounded-lg p-3 text-center">
                <p class="text-xs text-yellow-600">Antrian</p>
                <p class="text-lg font-bold text-yellow-700">{{ $bulkStats['queued'] }}</p>
            </div>
            <div class="bg-blue-50 rounded-lg p-3 text-center">
                <p class="text-xs text-blue-600">Diproses</p>
                <p class="text-lg font-bold text-blue-700">{{ $bulkStats['processing'] }}</p>
            </div>
            <div class="bg-green-50 rounded-lg p-3 text-center">
                <p class="text-xs text-green-600">Selesai</p>
                <p class="text-lg font-bold text-green-700">{{ $bulkStats['completed'] }}</p>
            </div>
            <div class="bg-red-50 rounded-lg p-3 text-center">
                <p class="text-xs text-red-600">Gagal</p>
                <p class="text-lg font-bold text-red-700">{{ $bulkStats['failed'] }}</p>
            </div>
        </div>

        {{-- Actions --}}
        <div class="flex flex-wrap gap-3 items-end">
            {{-- Bulk Track Form --}}
            <form action="{{ route('tracking.run-bulk') }}" method="POST" class="flex flex-wrap gap-2 items-end">
                @csrf
                <div>
                    <label class="block text-xs text-gray-500 mb-1">Batch Size</label>
                    <input type="number" name="batch_size" value="5" min="1" max="20"
                           class="w-20 px-2 py-1.5 border rounded-md text-sm">
                </div>
                <div>
                    <label class="block text-xs text-gray-500 mb-1">Limit (0=semua)</label>
                    <input type="number" name="limit" value="0" min="0"
                           class="w-24 px-2 py-1.5 border rounded-md text-sm">
                </div>
                <button type="submit" class="bg-purple-600 text-white px-4 py-1.5 rounded-md hover:bg-purple-700 text-sm font-medium">
                    ⚡ Bulk Track (Playwright)
                </button>
            </form>

            {{-- Divider --}}
            <div class="border-l h-8 mx-1"></div>

            {{-- Single Track (legacy Serper) --}}
            <form action="{{ route('tracking.run') }}" method="POST">
                @csrf
                <button type="submit" class="bg-blue-600 text-white px-4 py-1.5 rounded-md hover:bg-blue-700 text-sm">
                    🔍 Track (Serper API)
                </button>
            </form>

            <a href="{{ route('alumni.create') }}" class="bg-green-600 text-white px-4 py-1.5 rounded-md hover:bg-green-700 text-sm">
                ➕ Tambah Alumni
            </a>
        </div>
    </div>
</div>

{{-- Pending Verification --}}
@if($pendingVerification->count() > 0)
<div class="bg-white rounded-lg shadow">
    <div class="px-6 py-4 border-b">
        <h3 class="font-semibold text-gray-800">Menunggu Verifikasi</h3>
    </div>
    <div class="divide-y">
        @foreach($pendingVerification as $result)
        <div class="px-6 py-4 flex justify-between items-center">
            <div>
                <p class="font-medium text-gray-800">{{ $result->alumni->nama_lengkap }}</p>
                <p class="text-sm text-gray-500">{{ $result->judul_profil ?? 'N/A' }} — Skor: {{ number_format($result->skor_probabilitas, 2) }}</p>
            </div>
            <a href="{{ route('verification.index') }}" class="text-blue-600 hover:text-blue-800 text-sm">Lihat →</a>
        </div>
        @endforeach
    </div>
</div>
@else
<div class="bg-white rounded-lg shadow p-6 text-center text-gray-500">
    Tidak ada data yang menunggu verifikasi.
</div>
@endif
@endsection
