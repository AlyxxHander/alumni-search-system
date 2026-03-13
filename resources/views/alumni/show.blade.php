@extends('layouts.app')
@section('title', 'Detail Alumni')

@section('content')
<a href="{{ route('alumni.index') }}" class="text-blue-600 hover:text-blue-800 text-sm mb-4 inline-block">← Kembali ke Daftar</a>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <!-- Alumni Info -->
    <div class="bg-white rounded-lg shadow p-6">
        <div class="flex justify-between items-start mb-4">
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

        <dl class="space-y-2 text-sm">
            <div><dt class="text-gray-500">NIM</dt><dd class="font-mono">{{ $alumni->nim }}</dd></div>
            <div><dt class="text-gray-500">Nama Panggilan</dt><dd>{{ $alumni->nama_panggilan ?? '-' }}</dd></div>
            <div><dt class="text-gray-500">Inisial</dt><dd>{{ $alumni->inisial_belakang ?? '-' }}</dd></div>
            <div><dt class="text-gray-500">Program Studi</dt><dd>{{ $alumni->prodi }}</dd></div>
            <div><dt class="text-gray-500">Tahun Lulus</dt><dd>{{ $alumni->tahun_lulus }}</dd></div>
            <div><dt class="text-gray-500">Gelar</dt><dd>{{ $alumni->gelar_akademik ?? '-' }}</dd></div>
        </dl>

        <div class="mt-4 flex gap-2">
            <a href="{{ route('alumni.edit', $alumni) }}" class="bg-yellow-500 text-white px-3 py-1.5 rounded text-xs hover:bg-yellow-600">Edit</a>
            <button type="button" onclick="lacakUlang({{ $alumni->id }})" class="bg-blue-600 text-white px-3 py-1.5 rounded text-xs hover:bg-blue-700">🔍 Lacak Ulang</button>
        </div>
    </div>

    <!-- Tracking Results -->
    <div class="lg:col-span-2">
        <h3 class="text-lg font-semibold text-gray-800 mb-4">Hasil Pelacakan ({{ $alumni->trackingResults->count() }})</h3>

        @forelse($alumni->trackingResults as $result)
        <div class="bg-white rounded-lg shadow p-4 mb-3">
            <div class="flex justify-between items-start">
                <div class="flex-1">
                    <div class="flex items-center gap-2 mb-1">
                        <span class="text-xs bg-gray-100 text-gray-600 px-2 py-0.5 rounded">{{ $result->sumber->label() }}</span>
                        <span class="text-xs font-medium
                            {{ $result->skor_probabilitas >= 0.8 ? 'text-green-600' : ($result->skor_probabilitas >= 0.5 ? 'text-yellow-600' : 'text-red-600') }}">
                            Skor: {{ number_format($result->skor_probabilitas, 2) }}
                        </span>
                        <span class="text-xs text-gray-400">{{ $result->status_verifikasi->label() }}</span>
                    </div>
                    <p class="font-medium text-gray-800 text-sm">{{ $result->judul_profil ?? 'Tanpa Judul' }}</p>
                    <p class="text-xs text-gray-500 mt-1">{{ $result->instansi ?? '-' }} · {{ $result->lokasi ?? '-' }}</p>
                    <p class="text-xs text-gray-400 mt-1 line-clamp-2 md:mb-2">{{ $result->snippet }}</p>

                    <!-- Gemini Reasoning -->
                    <div class="bg-indigo-50 border border-indigo-100 rounded p-2 text-xs text-indigo-700 mt-2">
                        <span class="font-semibold mb-1 block">🤖 Alasan Tim Penilai AI (Gemini):</span>
                        {{ $result->alasan_gemini }}
                    </div>

                    @if($result->url_profil)
                        <a href="{{ $result->url_profil }}" target="_blank" class="text-xs text-blue-600 hover:underline mt-2 inline-block">🔗 Buka Profil</a>
                    @endif
                </div>
            </div>
        </div>
        @empty
        <div class="bg-white rounded-lg shadow p-6 text-center text-gray-500">
            Belum ada hasil pelacakan. Klik "Lacak Ulang" untuk memulai.
        </div>
        @endforelse
    </div>

    <!-- All Search Results (Serper) -->
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
                                        <span class="px-2 py-0.5 bg-indigo-100 text-indigo-700 text-[10px] font-bold rounded uppercase tracking-wider">{{ $source }}</span>
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
