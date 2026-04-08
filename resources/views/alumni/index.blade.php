@extends('layouts.app')
@section('title', 'Data Alumni')

@section('content')
    {{-- Actions --}}
    <div class="flex justify-between items-center mb-4">
        <a href="{{ route('alumni.create') }}"
            class="bg-green-600 text-white px-4 py-2 rounded-md hover:bg-green-700 text-sm">
            ➕ Tambah Alumni
        </a>
        <form action="{{ route('tracking.run') }}" method="POST">
            @csrf
            <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 text-sm">
                🚀 Jalankan Pelacakan Semua
            </button>
        </form>
    </div>

    {{-- Filters --}}
    <div class="bg-white rounded-lg shadow p-4 mb-4">
        <form method="GET" action="{{ route('alumni.index') }}" class="flex flex-wrap gap-3 items-end">
            <div class="flex-1 min-w-[200px]">
                <label class="block text-xs text-gray-500 mb-1">Cari</label>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Nama, NIM, Prodi, Tempat Kerja, Posisi..."
                    class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm">
            </div>
            <div>
                <label class="block text-xs text-gray-500 mb-1">Status</label>
                <select name="status" class="border border-gray-300 rounded-md px-3 py-2 text-sm">
                    <option value="">Semua</option>
                    @foreach (App\Enums\StatusPelacakan::cases() as $status)
                        <option value="{{ $status->value }}" {{ request('status') == $status->value ? 'selected' : '' }}>
                            {{ $status->label() }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs text-gray-500 mb-1">Fakultas</label>
                <select name="fakultas" class="border border-gray-300 rounded-md px-3 py-2 text-sm">
                    <option value="">Semua</option>
                    @foreach ($fakultasList as $fak)
                        <option value="{{ $fak }}" {{ request('fakultas') == $fak ? 'selected' : '' }}>
                            {{ $fak }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs text-gray-500 mb-1">Prodi</label>
                <select name="prodi" class="border border-gray-300 rounded-md px-3 py-2 text-sm">
                    <option value="">Semua</option>
                    @foreach ($prodiList as $prodi)
                        <option value="{{ $prodi }}" {{ request('prodi') == $prodi ? 'selected' : '' }}>
                            {{ $prodi }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs text-gray-500 mb-1">Tahun Lulus</label>
                <select name="tahun_lulus" class="border border-gray-300 rounded-md px-3 py-2 text-sm">
                    <option value="">Semua</option>
                    @foreach ($tahunList as $tahun)
                        <option value="{{ $tahun }}" {{ request('tahun_lulus') == $tahun ? 'selected' : '' }}>
                            {{ $tahun }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs text-gray-500 mb-1">Jenis Pekerjaan</label>
                <select name="jenis_pekerjaan" class="border border-gray-300 rounded-md px-3 py-2 text-sm">
                    <option value="">Semua</option>
                    <option value="PNS" {{ request('jenis_pekerjaan') == 'PNS' ? 'selected' : '' }}>PNS</option>
                    <option value="Swasta" {{ request('jenis_pekerjaan') == 'Swasta' ? 'selected' : '' }}>Swasta</option>
                    <option value="Wirausaha" {{ request('jenis_pekerjaan') == 'Wirausaha' ? 'selected' : '' }}>Wirausaha</option>
                    <option value="Lainnya" {{ request('jenis_pekerjaan') == 'Lainnya' ? 'selected' : '' }}>Lainnya</option>
                </select>
            </div>
            <button type="submit" class="bg-gray-800 text-white px-4 py-2 rounded-md text-sm">Filter</button>
            <a href="{{ route('alumni.index') }}" class="text-gray-500 hover:text-gray-700 text-sm py-2">Reset</a>
        </form>
    </div>

    {{-- Table --}}
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">NIM</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Nama</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Fakultas</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Prodi</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Lulus</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tempat Kerja</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tipe</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    @forelse ($alumni as $a)
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-3 text-gray-600 font-mono text-xs">{{ $a->nim }}</td>
                            <td class="px-4 py-3 font-medium text-gray-800">
                                {{ $a->nama_lengkap }}
                                @if($a->has_social_media)
                                    <span class="text-xs text-green-500 ml-1" title="Punya data sosmed">📱</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-gray-600 text-xs">{{ $a->fakultas ?? '-' }}</td>
                            <td class="px-4 py-3 text-gray-600 text-xs">{{ $a->prodi }}</td>
                            <td class="px-4 py-3 text-gray-600">{{ $a->tahun_lulus }}</td>
                            <td class="px-4 py-3 text-gray-600 text-xs">{{ Str::limit($a->tempat_bekerja ?? '-', 25) }}</td>
                            <td class="px-4 py-3">
                                @if($a->jenis_pekerjaan)
                                    <span class="inline-block px-2 py-0.5 rounded text-[10px] font-medium
                                        {{ $a->jenis_pekerjaan == 'PNS' ? 'bg-blue-100 text-blue-700' : '' }}
                                        {{ $a->jenis_pekerjaan == 'Swasta' ? 'bg-green-100 text-green-700' : '' }}
                                        {{ $a->jenis_pekerjaan == 'Wirausaha' ? 'bg-orange-100 text-orange-700' : '' }}
                                        {{ $a->jenis_pekerjaan == 'Lainnya' ? 'bg-gray-100 text-gray-700' : '' }}
                                    ">{{ $a->jenis_pekerjaan }}</span>
                                @else
                                    <span class="text-gray-400 text-xs">-</span>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                @php
                                    $colors = [
                                        'gray' => 'bg-gray-100 text-gray-700',
                                        'blue' => 'bg-blue-100 text-blue-700',
                                        'green' => 'bg-green-100 text-green-700',
                                        'yellow' => 'bg-yellow-100 text-yellow-700',
                                        'emerald' => 'bg-emerald-100 text-emerald-700',
                                        'red' => 'bg-red-100 text-red-700',
                                        'orange' => 'bg-orange-100 text-orange-700',
                                    ];
                                    $colorClass = $colors[$a->status_pelacakan->color()] ?? 'bg-gray-100 text-gray-700';
                                @endphp
                                <span class="inline-block px-2 py-1 rounded-full text-xs font-medium {{ $colorClass }}">
                                    {{ $a->status_pelacakan->label() }}
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex gap-2 items-center flex-wrap">
                                    <a href="{{ route('alumni.show', $a) }}"
                                        class="text-blue-600 hover:text-blue-800 text-xs">Detail</a>
                                    <a href="{{ route('alumni.edit', $a) }}"
                                        class="text-yellow-600 hover:text-yellow-800 text-xs">Edit</a>
                                    <form action="{{ route('alumni.destroy', $a) }}" method="POST" class="inline"
                                        onsubmit="return confirm('Yakin hapus?')">
                                        @csrf @method('DELETE')
                                        <button class="text-red-600 hover:text-red-800 text-xs">Hapus</button>
                                    </form>
                                    <button type="button" onclick="lacakUlang({{ $a->id }}, this)" class="text-indigo-600 hover:text-indigo-800 font-medium text-xs ml-1 pl-2 border-l">🔍 Lacak</button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="px-4 py-8 text-center text-gray-500">Belum ada data alumni.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="px-4 py-3 border-t">
            {{ $alumni->links() }}
        </div>
    </div>

<script>
function lacakUlang(alumniId, buttonEl) {
    Swal.fire({
        title: 'Memasukkan ke Antrean...',
        text: 'Mohon tunggu...',
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
                title: 'Berhasil Masuk Antrean',
                text: 'Pelacakan sedang diproses di latar belakang (Queue Worker).',
                timer: 3000,
                showConfirmButton: false
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
