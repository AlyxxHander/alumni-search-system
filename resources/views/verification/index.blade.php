@extends('layouts.app')
@section('title', 'Verifikasi Manual')

@section('content')
@forelse($results as $result)
<div class="bg-white rounded-lg shadow p-5 mb-4">
    <div class="flex justify-between items-start">
        <div class="flex-1">
            <!-- Alumni Info -->
            <div class="flex items-center gap-3 mb-3">
                <div>
                    <p class="font-semibold text-gray-800">{{ $result->alumni->nama_lengkap }}</p>
                    <p class="text-xs text-gray-500">{{ $result->alumni->nim }} · {{ $result->alumni->prodi }} · Lulus {{ $result->alumni->tahun_lulus }}</p>
                </div>
            </div>

            <!-- Found Profile -->
            <div class="bg-gray-50 rounded-md p-3 mb-3">
                <div class="flex items-center gap-2 mb-1">
                    <span class="text-xs bg-blue-100 text-blue-600 px-2 py-0.5 rounded">{{ $result->sumber->label() }}</span>
                    <span class="text-sm font-bold
                        {{ $result->skor_probabilitas >= 0.8 ? 'text-green-600' : ($result->skor_probabilitas >= 0.5 ? 'text-yellow-600' : 'text-red-600') }}">
                        Skor: {{ number_format($result->skor_probabilitas, 2) }}
                    </span>
                </div>
                <p class="font-medium text-gray-800 text-sm">{{ $result->judul_profil ?? 'Tanpa Judul' }}</p>
                <p class="text-xs text-gray-600 mt-1">🏢 {{ $result->instansi ?? '-' }} · 📍 {{ $result->lokasi ?? '-' }}</p>
                <p class="text-xs text-gray-500 mt-1 mb-2">{{ $result->snippet }}</p>

                <!-- Gemini Reasoning -->
                <div class="bg-indigo-50 border-l-2 border-indigo-400 p-2 rounded text-xs text-indigo-800">
                    <span class="font-semibold block mb-1">🤖 Alasan Tim Penilai AI (Gemini):</span>
                    {{ $result->alasan_gemini }}
                </div>

                @if($result->url_profil)
                    <a href="{{ $result->url_profil }}" target="_blank" class="text-xs text-blue-600 hover:underline mt-2 inline-block">🔗 Buka Profil Asli</a>
                @endif
            </div>
        </div>

        <!-- Actions -->
        <div class="flex flex-col gap-2 ml-4">
            <form action="{{ route('verification.confirm', $result) }}" method="POST">
                @csrf
                <button class="w-full bg-green-600 text-white px-4 py-2 rounded text-xs hover:bg-green-700">✅ Confirm</button>
            </form>
            <form action="{{ route('verification.reject', $result) }}" method="POST">
                @csrf
                <button class="w-full bg-red-600 text-white px-4 py-2 rounded text-xs hover:bg-red-700">❌ Reject</button>
            </form>
            <form action="{{ route('verification.skip', $result) }}" method="POST">
                @csrf
                <button class="w-full bg-gray-400 text-white px-4 py-2 rounded text-xs hover:bg-gray-500">⏭ Skip</button>
            </form>
        </div>
    </div>
</div>
@empty
<div class="bg-white rounded-lg shadow p-8 text-center text-gray-500">
    🎉 Tidak ada data yang perlu diverifikasi saat ini.
</div>
@endforelse

<div class="mt-4">
    {{ $results->links() }}
</div>
@endsection
