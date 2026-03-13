@php $a = $alumni ?? null; @endphp

<div class="grid grid-cols-1 md:grid-cols-2 gap-4">
    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">NIM <span class="text-red-500">*</span></label>
        <input type="text" name="nim" value="{{ old('nim', $a?->nim) }}"
            class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm @error('nim') border-red-500 @enderror" required>
        @error('nim') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Nama Lengkap <span class="text-red-500">*</span></label>
        <input type="text" name="nama_lengkap" value="{{ old('nama_lengkap', $a?->nama_lengkap) }}"
            class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm @error('nama_lengkap') border-red-500 @enderror" required>
        @error('nama_lengkap') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Nama Panggilan</label>
        <input type="text" name="nama_panggilan" value="{{ old('nama_panggilan', $a?->nama_panggilan) }}"
            class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm">
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Inisial + Nama Belakang</label>
        <input type="text" name="inisial_belakang" value="{{ old('inisial_belakang', $a?->inisial_belakang) }}"
            placeholder="Contoh: A. Pratama"
            class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm">
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Program Studi <span class="text-red-500">*</span></label>
        <input type="text" name="prodi" value="{{ old('prodi', $a?->prodi) }}"
            class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm @error('prodi') border-red-500 @enderror" required>
        @error('prodi') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Tahun Lulus <span class="text-red-500">*</span></label>
        <input type="number" name="tahun_lulus" value="{{ old('tahun_lulus', $a?->tahun_lulus) }}"
            min="1990" max="{{ date('Y') + 1 }}"
            class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm @error('tahun_lulus') border-red-500 @enderror" required>
        @error('tahun_lulus') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Gelar Akademik</label>
        <input type="text" name="gelar_akademik" value="{{ old('gelar_akademik', $a?->gelar_akademik) }}"
            placeholder="Contoh: S.Kom"
            class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm">
    </div>
</div>
