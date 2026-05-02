@extends('layouts.app')
@section('title', 'Detail Alumni')

@section('content')
<a href="{{ route('alumni.index') }}" class="text-blue-600 hover:text-blue-800 text-sm mb-4 inline-block">← Kembali ke Daftar</a>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    {{-- Alumni Info --}}
    <div class="space-y-4">
        {{-- Card 1: Data Akademik --}}
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex flex-col mb-4">
                <div class="flex justify-between items-start">
                    <h3 class="text-lg font-semibold text-gray-800">{{ $alumni->nama_lengkap }}</h3>
                    <span class="inline-block px-2 py-1 rounded-full text-xs font-medium
                        @switch($alumni->status_pelacakan?->color() ?? 'gray')
                            @case('gray') bg-gray-100 text-gray-700 @break
                            @case('blue') bg-blue-100 text-blue-700 @break
                            @case('green') bg-green-100 text-green-700 @break
                            @case('yellow') bg-yellow-100 text-yellow-700 @break
                            @case('emerald') bg-emerald-100 text-emerald-700 @break
                            @case('red') bg-red-100 text-red-700 @break
                            @case('orange') bg-orange-100 text-orange-700 @break
                        @endswitch
                    ">{{ $alumni->status_pelacakan?->label() ?? 'Belum Dilacak' }}</span>
                </div>
                @if($alumni->status_pelacakan !== \App\Enums\StatusPelacakan::BELUM_DILACAK)
                <div class="flex justify-end mt-2">
                    <span class="text-xs bg-indigo-50 text-indigo-700 px-2 py-1 rounded font-semibold border border-indigo-100">
                        Total Skor Keyakinan: {{ number_format($alumni->skor_keseluruhan * 100, 0) }}% ({{ number_format($alumni->skor_keseluruhan, 2) }})
                    </span>
                </div>
                @endif
            </div>

            <dl class="space-y-2 text-sm">
                <div><dt class="text-gray-500">Nama Panggilan</dt><dd>{{ $alumni->nama_panggilan ?? '-' }}</dd></div>
                <div><dt class="text-gray-500">NIM</dt><dd class="font-mono">{{ $alumni->nim }}</dd></div>
                <div><dt class="text-gray-500">Program Studi</dt><dd>{{ $alumni->prodi }}</dd></div>
                <div><dt class="text-gray-500">Fakultas</dt><dd>{{ $alumni->fakultas ?? '-' }}</dd></div>
                <div><dt class="text-gray-500">Tahun Masuk</dt><dd>{{ $alumni->tahun_masuk ?? '-' }}</dd></div>
                <div><dt class="text-gray-500">Tanggal Lulus</dt><dd>{{ $alumni->tanggal_lulus ?? ($alumni->tahun_lulus ? 'Tahun ' . $alumni->tahun_lulus : '-') }}</dd></div>
                <div><dt class="text-gray-500">Gelar</dt><dd>{{ $alumni->gelar_akademik ?? '-' }}</dd></div>
            </dl>
            
            <div class="mt-4 flex gap-2">
                <a href="{{ route('alumni.edit', $alumni) }}" class="bg-yellow-500 text-white px-3 py-1.5 rounded text-xs hover:bg-yellow-600">Edit</a>
                <button type="button" onclick="lacakUlang({{ $alumni->id }})" class="bg-blue-600 text-white px-3 py-1.5 rounded text-xs hover:bg-blue-700">🔍 Lacak Ulang</button>
            </div>
            
            @if($alumni->status_pelacakan->value === 'VALID_OTOMATIS' || $alumni->status_pelacakan->value === 'TERVERIFIKASI')
            <div class="mt-2 text-right">
                <form action="{{ route('alumni.unvalidate', $alumni) }}" method="POST" class="inline" 
                      onsubmit="return confirm('Apakah Anda yakin ingin membatalkan validasi alumni ini? Data pencarian asli akan tetap disimpan untuk verifikasi manual.')">
                    @csrf
                    <button type="submit" class="text-[10px] text-orange-600 hover:underline flex items-center gap-1.5 ml-auto">
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
                        Batalkan Validasi
                    </button>
                </form>
            </div>
            @endif
        </div>

        {{-- Card 2: Kontak & Sosial Media (DATA OTOMATIS) --}}
        <div class="bg-white rounded-lg shadow p-6">
            <h4 class="text-sm font-semibold text-gray-700 uppercase tracking-wider mb-7 flex items-center gap-2">
                <span class="text-green-600">📱</span> Kontak & Sosial Media
                <span class="ml-auto text-[10px] bg-indigo-100 text-indigo-700 px-2 py-0.5 rounded-full font-medium">🤖 Data Otomatis</span>
            </h4>
            <dl class="space-y-2 text-sm">
                <div class="flex flex-col items-start gap-2">
                    <dt class="text-gray-500 w-20">📧 Email</dt>
                    <dd>
                        @if($alumni->email)
                            <a href="mailto:{{ $alumni->email }}" class="text-blue-600 hover:underline">{{ $alumni->email }}</a>
                        @else
                            <span class="text-gray-400 italic text-xs">Belum ditemukan</span>
                        @endif
                    </dd>
                </div>
                <div class="flex flex-col items-start gap-2">
                    <dt class="text-gray-500 w-20">📱 No HP</dt>
                    <dd>{{ $alumni->no_hp ?? '' }}{!! !$alumni->no_hp ? '<span class="text-gray-400 italic text-xs">Belum ditemukan</span>' : '' !!}</dd>
                </div>
                <div class="flex flex-col items-start gap-2">
                    <dt class="text-gray-500 w-20">💼 LinkedIn</dt>
                    <dd>
                        @if($alumni->linkedin)
                            <a href="{{ str_starts_with($alumni->linkedin, 'http') ? $alumni->linkedin : 'https://' . $alumni->linkedin }}" target="_blank" class="text-blue-600 hover:underline text-xs break-all">{{ $alumni->linkedin }}</a>
                        @else
                            <span class="text-gray-400 italic text-xs">Belum ditemukan</span>
                        @endif
                    </dd>
                </div>
                <div class="flex flex-col items-start gap-2">
                    <dt class="text-gray-500 w-20">📸 Instagram</dt>
                    <dd>
                        @if($alumni->instagram)
                            <a href="{{ str_starts_with($alumni->instagram, 'http') ? $alumni->instagram : 'https://instagram.com/' . ltrim($alumni->instagram, '@') }}" target="_blank" class="text-blue-600 hover:underline text-xs">{{ $alumni->instagram }}</a>
                        @else
                            <span class="text-gray-400 italic text-xs">Belum ditemukan</span>
                        @endif
                    </dd>
                </div>
                <div class="flex flex-col items-start gap-2">
                    <dt class="text-gray-500 w-20">👤 Facebook</dt>
                    <dd>
                        @if($alumni->facebook)
                            <a href="{{ str_starts_with($alumni->facebook, 'http') ? $alumni->facebook : 'https://facebook.com/' . $alumni->facebook }}" target="_blank" class="text-blue-700 hover:underline text-xs">{{ $alumni->facebook }}</a>
                        @else
                            <span class="text-gray-400 italic text-xs">Belum ditemukan</span>
                        @endif
                    </dd>
                </div>
                <div class="flex flex-col items-start gap-2">
                    <dt class="text-gray-500 w-20">🎵 TikTok</dt>
                    <dd>
                        @if($alumni->tiktok)
                            <a href="{{ str_starts_with($alumni->tiktok, 'http') ? $alumni->tiktok : 'https://tiktok.com/@' . ltrim($alumni->tiktok, '@') }}" target="_blank" class="text-blue-700 hover:underline text-xs">{{ $alumni->tiktok }}</a>
                        @else
                            <span class="text-gray-400 italic text-xs">Belum ditemukan</span>
                        @endif
                    </dd>
                </div>
            </dl>
            @if(!$alumni->has_social_media && !$alumni->has_contact_data)
                <div class="mt-3 p-2 bg-gray-50 rounded text-center">
                    <p class="text-xs text-gray-400">Jalankan pelacakan untuk mencari data kontak & sosial media alumni secara otomatis.</p>
                </div>
            @endif
        </div>

        {{-- Card 3: Data Pekerjaan (DATA OTOMATIS) --}}
        <div class="bg-white rounded-lg shadow p-6">
            <h4 class="text-sm font-semibold text-gray-700 uppercase tracking-wider mb-7 flex items-center gap-2">
                <span class="text-purple-600">🏢</span> Data Pekerjaan
                <span class="ml-auto text-[10px] bg-indigo-100 text-indigo-700 px-2 py-0.5 rounded-full font-medium">🤖 Data Otomatis</span>
            </h4>
            <dl class="space-y-2 text-sm">
                <div><dt class="text-gray-500">Tempat Bekerja</dt><dd>{{ $alumni->tempat_bekerja ?? '' }}{!! !$alumni->tempat_bekerja ? '<span class="text-gray-400 italic text-xs">Belum ditemukan</span>' : '' !!}</dd></div>
                <div><dt class="text-gray-500">Posisi / Jabatan</dt><dd>{{ $alumni->posisi ?? '' }}{!! !$alumni->posisi ? '<span class="text-gray-400 italic text-xs">Belum ditemukan</span>' : '' !!}</dd></div>
                <div><dt class="text-gray-500">Jenis Pekerjaan</dt>
                    <dd>
                        @if($alumni->jenis_pekerjaan)
                            <span class="inline-block px-2 py-0.5 rounded text-xs font-medium
                                {{ $alumni->jenis_pekerjaan == 'PNS' ? 'bg-blue-100 text-blue-700' : '' }}
                                {{ $alumni->jenis_pekerjaan == 'Swasta' ? 'bg-green-100 text-green-700' : '' }}
                                {{ $alumni->jenis_pekerjaan == 'Wirausaha' ? 'bg-orange-100 text-orange-700' : '' }}
                                {{ $alumni->jenis_pekerjaan == 'Lainnya' ? 'bg-gray-100 text-gray-700' : '' }}
                            ">{{ $alumni->jenis_pekerjaan }}</span>
                        @else
                            <span class="text-gray-400 italic text-xs">Belum ditemukan</span>
                        @endif
                    </dd>
                </div>
                <div><dt class="text-gray-500">Alamat Bekerja</dt><dd class="text-xs">{{ $alumni->alamat_bekerja ?? '' }}{!! !$alumni->alamat_bekerja ? '<span class="text-gray-400 italic text-xs">Belum ditemukan</span>' : '' !!}</dd></div>
                <div><dt class="text-gray-500">Sosmed Tempat Bekerja</dt>
                    <dd>
                        @if($alumni->sosmed_tempat_bekerja)
                            <span class="text-xs text-blue-600 break-all">{{ $alumni->sosmed_tempat_bekerja }}</span>
                        @else
                            <span class="text-gray-400 italic text-xs">Belum ditemukan</span>
                        @endif
                    </dd>
                </div>
            </dl>
            @if(!$alumni->has_employment_data)
                <div class="mt-3 p-2 bg-gray-50 rounded text-center">
                    <p class="text-xs text-gray-400">Jalankan pelacakan untuk mencari data pekerjaan alumni secara otomatis.</p>
                </div>
            @endif
        </div>
    </div>

    {{-- Tracking Results --}}
    <div class="lg:col-span-2">
        <h3 class="text-lg font-semibold text-gray-800 mb-4">Hasil Pelacakan ({{ $alumni->trackingResults->count() }})</h3>

        @forelse($alumni->trackingResults as $result)
        <div class="bg-white rounded-lg shadow p-4 mb-3">
            <div class="flex justify-between items-start">
                <div class="flex-1">
                    <div class="flex items-center gap-2 mb-1">
                        <span class="text-xs bg-gray-100 text-gray-600 px-2 py-0.5 rounded">{{ $result->sumber->icon() }} {{ $result->sumber->label() }}</span>
                        <span class="text-xs font-medium
                            {{ $result->skor_probabilitas >= 0.8 ? 'text-green-600' : ($result->skor_probabilitas >= 0.5 ? 'text-yellow-600' : 'text-red-600') }}">
                            Skor: {{ number_format($result->skor_probabilitas, 2) }}
                        </span>
                        <span class="text-xs text-gray-400">{{ $result->status_verifikasi->label() }}</span>
                    </div>
                    <p class="font-medium text-gray-800 text-sm">{{ $result->judul_profil ?? 'Tanpa Judul' }}</p>
                    <p class="text-xs text-gray-500 mt-1">{{ $result->instansi ?? '-' }} · {{ $result->lokasi ?? '-' }}</p>
                    <p class="text-xs text-gray-400 mt-1 line-clamp-2 md:mb-2">{{ $result->snippet }}</p>

                    {{-- Gemini Reasoning --}}
                    <div class="bg-indigo-50 border border-indigo-100 rounded p-2 text-xs text-indigo-700 mt-2">
                        <span class="font-semibold mb-1 block">🤖 Alasan Tim Penilai AI (Gemini):</span>
                        {{ $result->alasan_gemini }}
                    </div>

                    {{-- Data Ekstraksi Per Platform --}}
                    <div class="mt-3 grid grid-cols-1 md:grid-cols-2 gap-x-4 gap-y-2 border-t border-gray-100 pt-3">
                        @if($result->email || $result->no_hp)
                        <div class="space-y-1">
                            <span class="text-[10px] font-bold text-gray-400 uppercase tracking-tight">Kontak & Sosmed</span>
                            @if($result->email)
                            <div class="flex items-center gap-1.5 text-[11px] text-gray-600">
                                <span class="w-3.5 text-center">📧</span>
                                <span class="truncate">{{ $result->email }}</span>
                            </div>
                            @endif
                            @if($result->no_hp)
                            <div class="flex items-center gap-1.5 text-[11px] text-gray-600">
                                <span class="w-3.5 text-center">📱</span>
                                <span>{{ $result->no_hp }}</span>
                            </div>
                            @endif
                            <div class="flex flex-wrap gap-2 mt-1">
                                @if($result->linkedin) <span class="text-[10px] bg-blue-50 text-blue-600 px-1.5 py-0.5 rounded font-medium">LinkedIn</span> @endif
                                @if($result->instagram) <span class="text-[10px] bg-pink-50 text-pink-600 px-1.5 py-0.5 rounded font-medium">Instagram</span> @endif
                                @if($result->facebook) <span class="text-[10px] bg-blue-50 text-blue-700 px-1.5 py-0.5 rounded font-medium">Facebook</span> @endif
                                @if($result->tiktok) <span class="text-[10px] bg-gray-100 text-gray-800 px-1.5 py-0.5 rounded font-medium">TikTok</span> @endif
                            </div>
                        </div>
                        @else
                        <div class="space-y-1">
                             <span class="text-[10px] font-bold text-gray-400 uppercase tracking-tight">Sosial Media</span>
                             <div class="flex flex-wrap gap-2 mt-1">
                                @if($result->linkedin) <span class="text-[10px] bg-gray-200 text-blue-600 px-1.5 py-0.5 rounded font-medium">LinkedIn</span> @endif
                                @if($result->instagram) <span class="text-[10px] bg-gray-200 text-blue-600 px-1.5 py-0.5 rounded font-medium">Instagram</span> @endif
                                @if($result->facebook) <span class="text-[10px] bg-gray-200 text-blue-600 px-1.5 py-0.5 rounded font-medium">Facebook</span> @endif
                                @if($result->tiktok) <span class="text-[10px] bg-gray-200 text-blue-600 px-1.5 py-0.5 rounded font-medium">TikTok</span> @endif
                                @if(!$result->linkedin && !$result->instagram && !$result->facebook && !$result->tiktok)
                                <span class="text-[10px] text-gray-400 italic">No links found</span>
                                @endif
                             </div>
                        </div>
                        @endif

                        @if($result->tempat_bekerja || $result->posisi)
                        <div class="space-y-1">
                            <span class="text-[10px] font-bold text-gray-400 uppercase tracking-tight">Pekerjaan</span>
                            @if($result->posisi)
                            <div class="flex items-center gap-1.5 text-[11px] text-gray-700 font-medium">
                                <span class="w-3.5 text-center">👔</span>
                                <span class="truncate">{{ $result->posisi }}</span>
                            </div>
                            @endif
                            @if($result->tempat_bekerja)
                            <div class="flex items-center gap-1.5 text-[11px] text-gray-600">
                                <span class="w-3.5 text-center">🏢</span>
                                <span class="truncate">{{ $result->tempat_bekerja }}</span>
                                @if($result->jenis_pekerjaan)
                                <span class="text-[10px] bg-gray-100 px-1 py-0.5 rounded text-gray-500">{{ $result->jenis_pekerjaan }}</span>
                                @endif
                            </div>
                            @endif
                            @if($result->alamat_bekerja)
                            <div class="flex items-center gap-1.5 text-[10px] text-gray-400">
                                <span class="w-3.5 text-center">📍</span>
                                <span class="truncate">{{ $result->alamat_bekerja }}</span>
                            </div>
                            @endif
                        </div>
                        @else
                        <div class="flex items-center justify-center bg-gray-50 rounded border border-dashed border-gray-200">
                            <span class="text-[10px] text-gray-400 italic">No job data found on this platform</span>
                        </div>
                        @endif
                    </div>

                    @if($result->url_profil)
                        <a href="{{ $result->url_profil }}" target="_blank" class="text-xs text-blue-600 hover:underline mt-2 inline-block">🔗 Buka Profil</a>
                    @endif
                </div>
            </div>
        </div>
        @empty
        <div class="bg-white rounded-lg shadow p-6 text-center text-gray-500">
            @if($alumni->status_pelacakan->value === 'TIDAK_DITEMUKAN')
                Tidak ada profil yang cocok ditemukan di mesin pencari. Klik "Lacak Ulang" untuk mencoba lagi.
            @elseif($alumni->status_pelacakan->value === 'SEDANG_DILACAK')
                Sedang mengumpulkan data dari mesin pencari dan memproses profil. Harap tunggu...
            @else
                Belum ada hasil pelacakan. Klik "Lacak Ulang" untuk memulai.
            @endif
        </div>
        @endforelse
    </div>

    {{-- All Search Results (Serper) --}}
    <div class="lg:col-span-3 mt-4">
        <div class="bg-white rounded-lg shadow overflow-hidden">
            <div class="bg-gray-50 px-6 py-4 border-b">
                <h3 class="text-sm font-bold text-gray-700 flex items-center gap-2">
                    <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                    Seluruh Link Hasil Penelusuran (Serper API)
                </h3>
            </div>
            <div class="p-6">
                @php $hasResults = false; @endphp
                @foreach($alumni->trackingResults as $result)
                    @if($result->raw_search_response && is_array($result->raw_search_response))
                        @php $hasResults = true; @endphp
                        <div class="space-y-8">
                            @foreach($result->raw_search_response as $source => $response)
                                <div>
                                    <div class="flex items-center gap-2 mb-3">
                                        @php
                                            $sumberEnum = \App\Enums\SumberPelacakan::tryFrom($source);
                                        @endphp
                                        <span class="px-2 py-0.5 bg-indigo-100 text-indigo-700 text-[10px] font-bold rounded uppercase tracking-wider">
                                            {{ $sumberEnum ? $sumberEnum->icon() . ' ' . $sumberEnum->label() : $source }}
                                        </span>
                                        <div class="h-px bg-gray-100 flex-1"></div>
                                    </div>
                                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                                        @forelse($response['organic'] ?? [] as $item)
                                            <div class="group border border-gray-100 rounded-lg p-3 hover:border-indigo-200 hover:shadow-sm transition-all bg-white relative">
                                                <div class="flex flex-col h-full">
                                                    <h4 class="text-xs font-semibold text-gray-800 line-clamp-1 mb-1 group-hover:text-indigo-600 transition-colors">{{ $item['title'] ?? 'N/A' }}</h4>
                                                    <p class="text-[10px] text-gray-500 line-clamp-2 mb-2 flex-grow">{{ $item['snippet'] ?? 'Tidak ada deskripsi.' }}</p>
                                                    <div class="flex items-center justify-between mt-auto pt-2 border-t border-gray-50">
                                                        <span class="text-[9px] text-gray-400">Posisi #{{ $loop->iteration }}</span>
                                                        <a href="{{ $item['link'] ?? '#' }}" target="_blank" class="text-[10px] font-bold text-blue-600 hover:text-blue-800 flex items-center gap-1">
                                                            Kunjungi 
                                                            <svg class="w-2.5 h-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path></svg>
                                                        </a>
                                                    </div>
                                                </div>
                                            </div>
                                        @empty
                                            <p class="text-xs text-gray-400 italic">Tidak ada profil organik ditemukan untuk sumber ini.</p>
                                        @endforelse
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                @endforeach

                @if(!$hasResults)
                    <div class="text-center py-12">
                        <div class="bg-gray-50 w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-4">
                            <svg class="w-8 h-8 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0114 0z"></path></svg>
                        </div>
                        <p class="text-sm text-gray-500">Belum ada data riwayat pencarian mentah. Silakan jalankan **Lacak Ulang** terlebih dahulu.</p>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

<script>
function lacakUlang(alumniId) {
    Swal.fire({
        title: 'Memproses...',
        text: 'Mengirim permintaan ke sistem antrean.',
        allowOutsideClick: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });

    fetch(`/tracking/run/${alumniId}`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Accept': 'application/json'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            Swal.fire({
                icon: 'success',
                title: 'Berhasil',
                text: 'Permintaan pelacakan dimasukkan ke antrean. Silakan periksa kembali halaman ini beberapa saat lagi.',
                timer: 3000,
                showConfirmButton: true
            });
        } else {
            Swal.fire('Error', 'Terjadi kesalahan sistem', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        Swal.fire('Error', 'Gagal terhubung ke server', 'error');
    });
}
</script>
@endsection
