@php $a = $alumni ?? null; @endphp

{{-- Section 1: Data Akademik (8 field input saja) --}}
<div class="mb-6">
    <h3 class="text-sm font-semibold text-gray-700 uppercase tracking-wider mb-3 pb-2 border-b border-gray-200 flex items-center gap-2">
        <span class="bg-blue-100 text-blue-700 w-6 h-6 rounded-full flex items-center justify-center text-xs font-bold">📝</span>
        Data Akademik Alumni
    </h3>
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
            <label class="block text-sm font-medium text-gray-700 mb-1">Tahun Masuk</label>
            <input type="number" name="tahun_masuk" value="{{ old('tahun_masuk', $a?->tahun_masuk) }}"
                min="1970" max="{{ date('Y') }}"
                class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm">
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Tanggal Lulus <span class="text-red-500">*</span></label>
            <input type="text" name="tanggal_lulus" value="{{ old('tanggal_lulus', $a?->tanggal_lulus) }}"
                placeholder="Contoh: 1 Maret 2004"
                class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm @error('tanggal_lulus') border-red-500 @enderror" required>
            @error('tanggal_lulus') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            {{-- Hidden field untuk tahun_lulus (dikirim sebagai integer untuk filter) --}}
            <input type="hidden" name="tahun_lulus" id="tahun_lulus_hidden" value="{{ old('tahun_lulus', $a?->tahun_lulus ?? date('Y')) }}">
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Fakultas</label>
            <input type="text" name="fakultas" value="{{ old('fakultas', $a?->fakultas) }}"
                placeholder="Contoh: Teknik"
                class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm">
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Program Studi <span class="text-red-500">*</span></label>
            <input type="text" name="prodi" value="{{ old('prodi', $a?->prodi) }}"
                class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm @error('prodi') border-red-500 @enderror" required>
            @error('prodi') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Gelar Akademik</label>
            <input type="text" name="gelar_akademik" value="{{ old('gelar_akademik', $a?->gelar_akademik) }}"
                placeholder="Contoh: S.Kom"
                class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm">
        </div>
    </div>

    <script>
        // Otomatis isi tahun_lulus dari tanggal_lulus jika mengandung angka tahun
        document.querySelector('input[name="tanggal_lulus"]').addEventListener('input', function(e) {
            const val = e.target.value;
            const match = val.match(/\d{4}/);
            if (match) {
                document.getElementById('tahun_lulus_hidden').value = match[0];
            }
        });
    </script>
    </div>

    {{-- Info --}}
    <div class="mt-4 p-3 bg-blue-50 border border-blue-100 rounded-md">
        <p class="text-xs text-blue-700 flex items-center gap-1.5">
            <span class="text-sm">ℹ️</span>
            Data kontak, sosial media, dan pekerjaan alumni akan dicari otomatis oleh sistem menggunakan Serper API & Gemini AI setelah pelacakan dijalankan.
        </p>
    </div>
</div>
