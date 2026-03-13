@extends('layouts.app')
@section('title', 'Konfigurasi Pelacakan')

@section('content')
<div class="max-w-2xl">
    <div class="bg-white rounded-lg shadow p-6">
        <form method="POST" action="{{ route('config.update') }}">
            @csrf @method('PUT')

            <!-- Afiliasi Utama -->
            <div class="mb-5">
                <label class="block text-sm font-medium text-gray-700 mb-1">Afiliasi Utama</label>
                <input type="text" name="afiliasi_utama"
                    value="{{ implode(', ', $config['afiliasi_utama']) }}"
                    class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm @error('afiliasi_utama') border-red-500 @enderror">
                <p class="text-xs text-gray-400 mt-1">Pisahkan dengan koma. Contoh: UMM, Universitas Muhammadiyah Malang, Informatika</p>
                @error('afiliasi_utama') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            <!-- Threshold Valid Otomatis -->
            <div class="mb-5">
                <label class="block text-sm font-medium text-gray-700 mb-1">Threshold Valid Otomatis</label>
                <input type="number" name="threshold_valid_otomatis"
                    value="{{ $config['threshold_valid_otomatis'] }}"
                    step="0.05" min="0" max="1"
                    class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm @error('threshold_valid_otomatis') border-red-500 @enderror">
                <p class="text-xs text-gray-400 mt-1">Skor di atas nilai ini akan otomatis dianggap valid (0.0 - 1.0)</p>
                @error('threshold_valid_otomatis') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            <!-- Threshold Verifikasi Manual -->
            <div class="mb-5">
                <label class="block text-sm font-medium text-gray-700 mb-1">Threshold Verifikasi Manual</label>
                <input type="number" name="threshold_verifikasi_manual"
                    value="{{ $config['threshold_verifikasi_manual'] }}"
                    step="0.05" min="0" max="1"
                    class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm @error('threshold_verifikasi_manual') border-red-500 @enderror">
                <p class="text-xs text-gray-400 mt-1">Skor antara nilai ini dan threshold otomatis akan butuh verifikasi manual (0.0 - 1.0)</p>
                @error('threshold_verifikasi_manual') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            <!-- Sumber Aktif -->
            <div class="mb-5">
                <label class="block text-sm font-medium text-gray-700 mb-2">Sumber Pelacakan Aktif</label>
                <div class="space-y-2">
                    @foreach(App\Enums\SumberPelacakan::cases() as $sumber)
                    <label class="flex items-center gap-2">
                        <input type="checkbox" name="sumber_aktif[]" value="{{ $sumber->value }}"
                            {{ in_array($sumber->value, $config['sumber_aktif']) ? 'checked' : '' }}
                            class="rounded border-gray-300">
                        <span class="text-sm text-gray-700">{{ $sumber->label() }}</span>
                    </label>
                    @endforeach
                </div>
                @error('sumber_aktif') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded-md hover:bg-blue-700 text-sm">
                Simpan Konfigurasi
            </button>
        </form>
    </div>
</div>
@endsection
