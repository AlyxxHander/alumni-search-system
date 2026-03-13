@extends('layouts.app')
@section('title', 'Laporan Sebaran Alumni')

@section('content')
<!-- Summary -->
<div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
    <div class="bg-white rounded-lg shadow p-4">
        <p class="text-xs text-gray-500 uppercase">Valid Otomatis</p>
        <p class="text-2xl font-bold text-green-600">{{ $totalValid }}</p>
    </div>
    <div class="bg-white rounded-lg shadow p-4">
        <p class="text-xs text-gray-500 uppercase">Terverifikasi</p>
        <p class="text-2xl font-bold text-emerald-600">{{ $totalVerified }}</p>
    </div>
</div>

<!-- Filters -->
<div class="bg-white rounded-lg shadow p-4 mb-4">
    <form method="GET" action="{{ route('reports.index') }}" class="flex flex-wrap gap-3 items-end">
        <div>
            <label class="block text-xs text-gray-500 mb-1">Prodi</label>
            <select name="prodi" class="border border-gray-300 rounded-md px-3 py-2 text-sm">
                <option value="">Semua</option>
                @foreach($prodiList as $prodi)
                    <option value="{{ $prodi }}" {{ request('prodi') == $prodi ? 'selected' : '' }}>{{ $prodi }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-xs text-gray-500 mb-1">Tahun Lulus</label>
            <select name="tahun_lulus" class="border border-gray-300 rounded-md px-3 py-2 text-sm">
                <option value="">Semua</option>
                @foreach($tahunList as $tahun)
                    <option value="{{ $tahun }}" {{ request('tahun_lulus') == $tahun ? 'selected' : '' }}>{{ $tahun }}</option>
                @endforeach
            </select>
        </div>
        <div class="flex items-center gap-2 w-full mt-2 sm:mt-0 sm:w-auto sm:ml-auto border-t sm:border-t-0 pt-3 sm:pt-0">
            <button type="submit" class="bg-gray-800 text-white px-4 py-2 rounded-md text-sm">Filter</button>
            <a href="{{ route('reports.index') }}" class="text-gray-500 hover:text-gray-700 text-sm py-2">Reset</a>
            
            <a href="{{ route('reports.export.csv', request()->all()) }}" class="bg-green-600 text-white px-3 py-2 rounded-md text-sm ml-auto sm:ml-2 flex items-center gap-1 hover:bg-green-700">
                📊 EXCEL
            </a>
            <a href="{{ route('reports.export.pdf', request()->all()) }}" target="_blank" class="bg-red-600 text-white px-3 py-2 rounded-md text-sm flex items-center gap-1 hover:bg-red-700">
                📄 PDF
            </a>
        </div>
    </form>
</div>

<!-- Table -->
<div class="bg-white rounded-lg shadow overflow-hidden">
    <table class="w-full text-sm">
        <thead class="bg-gray-50">
            <tr>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Nama</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">NIM</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Prodi</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tahun Lulus</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Instansi Terkini</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Lokasi</th>
            </tr>
        </thead>
        <tbody class="divide-y">
            @forelse($alumni as $a)
            @php
                $bestResult = $a->trackingResults->first();
            @endphp
            <tr class="hover:bg-gray-50">
                <td class="px-4 py-3 font-medium text-gray-800">{{ $a->nama_lengkap }}</td>
                <td class="px-4 py-3 text-gray-600 font-mono text-xs">{{ $a->nim }}</td>
                <td class="px-4 py-3 text-gray-600">{{ $a->prodi }}</td>
                <td class="px-4 py-3 text-gray-600">{{ $a->tahun_lulus }}</td>
                <td class="px-4 py-3">
                    <span class="inline-block px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-700">
                        {{ $a->status_pelacakan->label() }}
                    </span>
                </td>
                <td class="px-4 py-3 text-gray-600">{{ $bestResult?->instansi ?? '-' }}</td>
                <td class="px-4 py-3 text-gray-600">{{ $bestResult?->lokasi ?? '-' }}</td>
            </tr>
            @empty
            <tr><td colspan="7" class="px-4 py-8 text-center text-gray-500">Belum ada data alumni yang terverifikasi.</td></tr>
            @endforelse
        </tbody>
    </table>
    <div class="px-4 py-3 border-t">
        {{ $alumni->links() }}
    </div>
</div>
@endsection
