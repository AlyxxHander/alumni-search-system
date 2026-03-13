@extends('layouts.app')
@section('title', 'Dashboard')

@section('content')
<!-- Stats Cards -->
<div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-6 gap-4 mb-6">
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

<!-- Quick Actions -->
<div class="flex gap-3 mb-6">
    <form action="{{ route('tracking.run') }}" method="POST">
        @csrf
        <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 text-sm">
            🚀 Jalankan Pelacakan
        </button>
    </form>
    <a href="{{ route('alumni.create') }}" class="bg-green-600 text-white px-4 py-2 rounded-md hover:bg-green-700 text-sm">
        ➕ Tambah Alumni
    </a>
</div>

<!-- Pending Verification -->
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
