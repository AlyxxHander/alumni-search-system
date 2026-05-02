@extends('layouts.app')
@section('title', 'Verifikasi Manual')

@section('content')

@if($results->total() > 0)
<div class="mb-6 flex justify-end">
    <form action="{{ route('verification.confirmAll') }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menyetujui SEMUA data yang sedang menunggu verifikasi? Data ini akan dimasukkan ke database utama alumni.');">
        @csrf
        <button type="submit" class="bg-indigo-600 text-white px-6 py-2.5 rounded-lg text-sm font-bold hover:bg-indigo-700 shadow-md transition-all active:scale-95 flex items-center justify-center gap-2">
            <span>✅</span> Verifikasi Semua Data Sekaligus
        </button>
    </form>
</div>
@endif

@forelse($results as $result)
<div class="bg-white rounded-lg shadow-sm border border-gray-100 p-6 mb-6">
    <div class="flex flex-col lg:flex-row justify-between gap-6">
        <div class="flex-1">
            <!-- Header: Alumni Identity -->
            <div class="flex items-center gap-4 mb-6 pb-4 border-b border-gray-50">
                <div class="bg-indigo-600 text-white w-12 h-12 rounded-full flex items-center justify-center text-xl font-bold shadow-sm">
                    {{ substr($result->alumni->nama_lengkap, 0, 1) }}
                </div>
                <div>
                    <h3 class="text-lg font-bold text-gray-900 leading-tight">{{ $result->alumni->nama_lengkap }}</h3>
                    <p class="text-sm text-gray-500 font-medium">NIM: {{ $result->alumni->nim }} · {{ $result->alumni->prodi }} · Lulus {{ $result->alumni->tanggal_lulus }}</p>
                </div>
                <div class="ml-auto text-right">
                    <span class="text-[10px] font-bold text-gray-400 uppercase tracking-widest block mb-1">Status Pencarian</span>
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold {{ $result->skor_probabilitas >= 0.7 ? 'bg-green-100 text-green-700' : 'bg-yellow-100 text-yellow-700' }}">
                        {{ number_format($result->skor_probabilitas * 100, 0) }}% Match
                    </span>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                <!-- Data Sosial Media & Kontak -->
                <div class="space-y-4">
                    <h4 class="text-xs font-bold text-indigo-600 uppercase tracking-widest flex items-center gap-2">
                        <span>📱</span> Sosial Media & Kontak
                    </h4>
                    <div class="grid grid-cols-1 gap-3 bg-gray-50/50 p-4 rounded-xl border border-gray-100">
                        @php
                            $socials = [
                                ['label' => 'LinkedIn', 'val' => $result->linkedin, 'icon' => '💼', 'color' => 'text-blue-700'],
                                ['label' => 'Instagram', 'val' => $result->instagram, 'icon' => '📸', 'color' => 'text-pink-600'],
                                ['label' => 'Facebook', 'val' => $result->facebook, 'icon' => '👤', 'color' => 'text-blue-800'],
                                ['label' => 'TikTok', 'val' => $result->tiktok, 'icon' => '🎵', 'color' => 'text-gray-900'],
                            ];
                        @endphp
                        
                        @foreach($socials as $soc)
                            @if($soc['val'])
                            <div class="flex items-center justify-between pb-2 border-b border-gray-100 last:border-0 last:pb-0">
                                <span class="text-xs text-gray-500 font-medium flex items-center gap-2">
                                    {{ $soc['icon'] }} {{ $soc['label'] }}
                                </span>
                                <a href="{{ str_starts_with($soc['val'], 'http') ? $soc['val'] : 'https://' . $soc['val'] }}" target="_blank" class="text-xs font-bold {{ $soc['color'] }} hover:underline truncate max-w-[200px]">
                                    {{ $soc['val'] }}
                                </a>
                            </div>
                            @endif
                        @endforeach

                        <div class="pt-2 flex flex-col gap-2">
                            <div class="flex items-center justify-between">
                                <span class="text-xs text-gray-500 font-medium">📧 Email</span>
                                <span class="text-xs font-bold text-gray-800">{{ $result->email ?: '-' }}</span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-xs text-gray-500 font-medium">📱 No HP</span>
                                <span class="text-xs font-bold text-gray-800">{{ $result->no_hp ?: '-' }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Data Pekerjaan -->
                <div class="space-y-4">
                    <h4 class="text-xs font-bold text-indigo-600 uppercase tracking-widest flex items-center gap-2">
                        <span>🏢</span> Data Pekerjaan Terbaru
                    </h4>
                    <div class="flex flex-col gap-3 bg-gray-50/50 p-4 rounded-xl border border-gray-100">
                        <div>
                            <p class="text-[10px] font-bold text-gray-400 uppercase tracking-tighter">Posisi / Jabatan</p>
                            <p class="text-sm font-bold text-gray-900">{{ $result->posisi ?: 'Belum terdeteksi' }}</p>
                        </div>
                        <div>
                            <p class="text-[10px] font-bold text-gray-400 uppercase tracking-tighter">Instansi</p>
                            <div class="flex items-center gap-2">
                                <p class="text-sm font-bold text-gray-800">{{ $result->tempat_bekerja ?: 'Belum terdeteksi' }}</p>
                                @if($result->jenis_pekerjaan)
                                <span class="text-[9px] bg-indigo-100 text-indigo-700 px-1.5 py-0.5 rounded font-bold">{{ $result->jenis_pekerjaan }}</span>
                                @endif
                            </div>
                        </div>
                        <div>
                            <p class="text-[10px] font-bold text-gray-400 uppercase tracking-tighter">Lokasi / Alamat</p>
                            <p class="text-xs text-gray-600">{{ $result->alamat_bekerja ?: '-' }}</p>
                        </div>
                        @if($result->sosmed_tempat_bekerja)
                        <div class="pt-2 border-t border-gray-100">
                            <p class="text-[10px] font-bold text-gray-400 uppercase tracking-tighter">Sosmed/Link Instansi</p>
                            <p class="text-xs text-blue-600 truncate underline">{{ $result->sosmed_tempat_bekerja }}</p>
                        </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- AI Reasoning Summary -->
            <div class="mt-8 bg-indigo-50/50 border-l-4 border-indigo-400 p-4 rounded-r-lg">
                <div class="flex gap-3">
                    <span class="text-xl">🤖</span>
                    <div>
                        <p class="text-xs font-bold text-indigo-800 mb-1 uppercase tracking-tight">Alasan Rekomendasi Gabungan (AI):</p>
                        <p class="text-sm text-indigo-900/80 italic leading-relaxed">"{{ $result->alasan_gemini }}"</p>
                    </div>
                </div>
            </div>

            <!-- Supporting Evidence (Bukti) -->
            <div class="mt-8">
                <details class="group border border-gray-100 rounded-lg overflow-hidden">
                    <summary class="flex items-center justify-between p-4 bg-gray-50 cursor-pointer hover:bg-gray-100 transition-colors">
                        <span class="text-xs font-bold text-gray-600 uppercase tracking-wide">🔍 Lihat Bukti Pendukung ({{ count($result->alumni->trackingResults) - 1 }} Temuan Individu)</span>
                        <span class="text-gray-400 group-open:rotate-180 transition-transform">▼</span>
                    </summary>
                    <div class="p-4 bg-white grid grid-cols-1 md:grid-cols-2 gap-4">
                        @foreach($result->alumni->trackingResults as $evidence)
                            @if($evidence->sumber->value !== 'GABUNGAN')
                            <div class="p-3 border border-gray-100 rounded-lg bg-white shadow-sm flex flex-col h-full">
                                <div class="flex justify-between items-start mb-2">
                                    <span class="text-[10px] font-bold {{ $evidence->skor_probabilitas >= 0.7 ? 'bg-green-50 text-green-600' : 'bg-gray-100 text-gray-500' }} px-1.5 py-0.5 rounded">
                                        {{ $evidence->sumber->label() }} · {{ number_format($evidence->skor_probabilitas, 1) }}
                                    </span>
                                    @if($evidence->url_profil)
                                    <a href="{{ $evidence->url_profil }}" target="_blank" class="text-[10px] text-blue-600 font-bold hover:underline">Link Original</a>
                                    @endif
                                </div>
                                <p class="text-xs font-bold text-gray-800 mb-1 truncate">{{ $evidence->judul_profil }}</p>
                                <p class="text-[11px] text-gray-600 flex-1 line-clamp-3 leading-relaxed">{{ $evidence->snippet }}</p>
                                <div class="mt-2 pt-2 border-t border-gray-50 text-[9px] text-gray-400 italic">
                                    AI: "{{ Str::limit($evidence->alasan_gemini, 80) }}"
                                </div>
                            </div>
                            @endif
                        @endforeach
                    </div>
                </details>
            </div>
        </div>

        <!-- Sticky Actions Panel -->
        <div class="lg:w-48 flex flex-col gap-3 lg:border-l lg:border-gray-100 lg:pl-6 pt-4 lg:pt-0">
            <p class="text-[10px] font-bold text-gray-400 uppercase text-center mb-1">Verifikasi Data Ini</p>
            
            <form action="{{ route('verification.confirm', $result) }}" method="POST">
                @csrf
                <button class="w-full bg-green-600 text-white px-4 py-3 rounded-xl text-sm font-bold hover:bg-green-700 shadow-md transition-all active:scale-95 flex items-center justify-center gap-2">
                    <span>✅</span> Confirm Profile
                </button>
            </form>
            
            <form action="{{ route('verification.reject', $result) }}" method="POST">
                @csrf
                <button class="w-full bg-red-100 text-red-700 px-4 py-3 rounded-xl text-sm font-bold hover:bg-red-200 transition-all active:scale-95 flex items-center justify-center gap-2 border border-red-200">
                    <span>❌</span> Reject Findings
                </button>
            </form>

            <form action="{{ route('verification.skip', $result) }}" method="POST">
                @csrf
                <button class="w-full bg-white text-gray-500 px-4 py-3 rounded-xl text-xs font-semibold hover:bg-gray-50 transition-all flex items-center justify-center gap-2 border border-gray-200">
                    <span>⏭</span> Skip for Now
                </button>
            </form>

            <div class="mt-auto pt-6 text-center">
                <p class="text-[9px] text-gray-400 leading-tight">Konfirmasi akan memperbarui profil utama alumni dengan data di atas.</p>
            </div>
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
