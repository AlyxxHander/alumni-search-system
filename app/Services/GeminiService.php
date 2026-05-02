<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GeminiService
{
    protected string $apiKey;
    protected string $model;
    protected string $baseUrl;

    public function __construct()
    {
        $this->apiKey = config('services.gemini.api_key', env('GEMINI_API_KEY', ''));
        $this->model = config('services.gemini.model', env('GEMINI_MODEL', 'gemini-3.1-flash-lite-preview'));

        $this->baseUrl = "https://generativelanguage.googleapis.com/v1beta/models/{$this->model}:generateContent";
    }

    public function analyzeIdentityBatch(array $dataAsli, array $potonganInfoList): array
    {
        $prompt = $this->buildBatchPrompt($dataAsli, $potonganInfoList);

        $maxRetries = 3;
        $retryDelay = 5;

        for ($attempt = 1; $attempt <= $maxRetries; $attempt++) {
            try {
                $response = Http::withHeaders([
                    'Content-Type' => 'application/json',
                ])->post("{$this->baseUrl}?key={$this->apiKey}", [
                    'contents' => [
                        [
                            'parts' => [
                                ['text' => $prompt],
                            ],
                        ],
                    ],
                    'generationConfig' => [
                        'responseMimeType' => 'application/json',
                        'responseSchema' => [
                            'type' => 'object',
                            'properties' => [
                                'best_matches' => [
                                    'type' => 'array',
                                    'description' => 'Daftar satu profil terbaik untuk SETIAP platform. Hanya masukkan platform yang memiliki setidaknya satu kecocokan wajar.',
                                    'items' => [
                                        'type' => 'object',
                                        'properties' => [
                                            'platform' => ['type' => 'string'],
                                            'best_index' => ['type' => 'integer'],
                                            'skor' => ['type' => 'number'],
                                            'alasan' => ['type' => 'string'],
                                            'extracted_data' => [
                                                'type' => 'object',
                                                'properties' => [
                                                    'email' => ['type' => 'string'],
                                                    'no_hp' => ['type' => 'string'],
                                                    'linkedin' => ['type' => 'string'],
                                                    'instagram' => ['type' => 'string'],
                                                    'facebook' => ['type' => 'string'],
                                                    'tiktok' => ['type' => 'string'],
                                                    'tempat_bekerja' => ['type' => 'string'],
                                                    'alamat_bekerja' => ['type' => 'string'],
                                                    'posisi' => ['type' => 'string'],
                                                    'jenis_pekerjaan' => ['type' => 'string'],
                                                    'sosmed_tempat_bekerja' => ['type' => 'string'],
                                                    'instansi_linkedin' => ['type' => 'string'],
                                                    'instansi_instagram' => ['type' => 'string'],
                                                    'instansi_facebook' => ['type' => 'string'],
                                                    'instansi_tiktok' => ['type' => 'string'],
                                                ]
                                            ]
                                        ],
                                        'required' => ['platform', 'best_index', 'skor', 'alasan', 'extracted_data'],
                                    ],
                                ],
                                'unified_data' => [
                                    'type' => 'object',
                                    'description' => 'GABUNGAN data terbaik yang dikonsolidasikan dari seluruh platform hasil pencarian untuk profil alumni ini.',
                                    'properties' => [
                                        'email' => ['type' => 'string'],
                                        'no_hp' => ['type' => 'string'],
                                        'linkedin' => ['type' => 'string'],
                                        'instagram' => ['type' => 'string'],
                                        'facebook' => ['type' => 'string'],
                                        'tiktok' => ['type' => 'string'],
                                        'tempat_bekerja' => ['type' => 'string'],
                                        'alamat_bekerja' => ['type' => 'string'],
                                        'posisi' => ['type' => 'string'],
                                        'jenis_pekerjaan' => ['type' => 'string', 'description' => 'PNS, Swasta, Wirausaha, atau Lainnya'],
                                        'sosmed_tempat_bekerja' => ['type' => 'string'],
                                        'instansi_linkedin' => ['type' => 'string'],
                                        'instansi_instagram' => ['type' => 'string'],
                                        'instansi_facebook' => ['type' => 'string'],
                                        'instansi_tiktok' => ['type' => 'string'],
                                    ]
                                ],
                                'skor_keseluruhan' => [
                                    'type' => 'number',
                                    'description' => 'Skor total (0.0 - 1.0) tingkat keyakinan bahwa profil gabungan ini merujuk alumni yang benar.',
                                ],
                                'alasan_keseluruhan' => [
                                    'type' => 'string',
                                    'description' => 'Ringkasan bukti gabungan dari berbagai platform.',
                                ],
                            ],
                            'required' => ['best_matches', 'unified_data', 'skor_keseluruhan', 'alasan_keseluruhan'],
                        ],
                    ],
                ]);

                if ($response->successful()) {
                    $data = $response->json();
                    $text = $data['candidates'][0]['content']['parts'][0]['text'] ?? null;

                    if ($text) {
                        if (preg_match('/```(?:json)?(.*?)```/s', $text, $matches)) {
                            $text = trim($matches[1]);
                        }

                        $result = json_decode($text, true);
                        if ($result && isset($result['best_matches']) && isset($result['skor_keseluruhan'])) {
                            return [
                                'best_matches' => $result['best_matches'],
                                'unified_data' => $result['unified_data'] ?? [],
                                'skor_keseluruhan' => (float) min(1.0, max(0.0, $result['skor_keseluruhan'])),
                                'alasan_keseluruhan' => $result['alasan_keseluruhan'] ?? 'Tidak ada alasan yang diberikan',
                                'raw_response' => $data,
                            ];
                        } else {
                            Log::error("Gemini Parse Error. Text: " . $text);
                        }
                    }
                }

                if ($response->status() === 503 || $response->status() === 429) {
                    if ($attempt < $maxRetries) {
                        Log::warning("Gemini API error {$response->status()}. Retrying in {$retryDelay}s (Attempt {$attempt}/{$maxRetries})");
                        sleep($retryDelay);
                        $retryDelay *= 2; // exponential backoff
                        continue;
                    }
                }

                Log::error('Gemini API error', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return ['best_matches' => [], 'skor_keseluruhan' => 0.0, 'alasan_keseluruhan' => "Gagal menganalisis. HTTP Status: " . $response->status(), 'raw_response' => ["alasan_keseluruhan" => "API Error: " . $response->status()]];
            } catch (\Exception $e) {
                if ($attempt < $maxRetries) {
                    Log::warning("Gemini API exception: {$e->getMessage()}. Retrying in {$retryDelay}s");
                    sleep($retryDelay);
                    $retryDelay *= 2;
                    continue;
                }
                Log::error('Gemini API exception: ' . $e->getMessage());
                return ['best_matches' => [], 'skor_keseluruhan' => 0.0, 'alasan_keseluruhan' => 'Error: ' . $e->getMessage(), 'raw_response' => ["alasan_keseluruhan" => 'Exception: ' . $e->getMessage()]];
            }
        }
        
        return ['best_matches' => [], 'skor_keseluruhan' => 0.0, 'alasan_keseluruhan' => "Gagal menganalisis setelah {$maxRetries} percobaan.", 'raw_response' => ["alasan_keseluruhan" => "Failed after max retries."]];
    }

    protected function buildBatchPrompt(array $dataAsli, array $potonganInfoList): string
    {
        $nim = $dataAsli['nim'];
        $namaLengkap = $dataAsli['nama_lengkap'];
        $namaPanggilan = $dataAsli['nama_panggilan'];
        $prodi = $dataAsli['prodi'];
        $fakultas = $dataAsli['fakultas'] ?? 'Tidak diketahui';
        $tahunMasuk = $dataAsli['tahun_masuk'] ?? 'Tidak diketahui';
        $tanggalLulus = $dataAsli['tanggal_lulus'] ?? 'Tidak diketahui';
        $tahunLulus = $dataAsli['tahun_lulus'] ?? 'Tidak diketahui';
        $gelar = $dataAsli['gelar_akademik'];

        $listProfilText = "";
        if (empty($potonganInfoList)) {
             $listProfilText = "Tidak ada hasil pencarian internet.";
        } else {
            foreach ($potonganInfoList as $index => $info) {
                $sumber = $info['sumber_enum']?->value ?? 'Tidak diketahui';
                $judul = $info['judul_profil'] ?? 'Tidak tersedia';
                $instansi = $info['instansi'] ?? 'Tidak tersedia';
                $lokasi = $info['lokasi'] ?? 'Tidak tersedia';
                $snippet = $info['snippet'] ?? 'Tidak tersedia';
                $url = $info['url_profil'] ?? 'Tidak tersedia';

                $listProfilText .= "\n[INDEX {$index}] (Sumber: {$sumber})\n";
                $listProfilText .= "- Judul: {$judul}\n";
                $listProfilText .= "- Instansi: {$instansi}\n";
                $listProfilText .= "- Lokasi: {$lokasi}\n";
                $listProfilText .= "- Snippet: {$snippet}\n";
                $listProfilText .= "- URL: {$url}\n";
            }
        }

        // Hitung estimasi tahun aktif setelah lulus
        $estimasiKarir = '';
        if ($tahunLulus && $tahunLulus !== 'Tidak diketahui') {
            $tahunSekarang = date('Y');
            $lamaKerja = $tahunSekarang - (int) $tahunLulus;
            if ($lamaKerja > 0) {
                $estimasiKarir = "Alumni ini sudah lulus sekitar {$lamaKerja} tahun yang lalu, sehingga kemungkinan sudah memiliki pengalaman kerja yang cukup.";
            }
        }

        return <<<PROMPT
Anda adalah "Verifikator Alumni", sebuah sistem AI pintar untuk mendeteksi kecocokan identitas dan mengekstrak informasi kontak/pekerjaan.
Tugas utama Anda adalah membandingkan "Data Referensi Berkuliah" milik seorang alumni, dengan rentetan "Hasil Pencarian Internet" (Google Scholar, LinkedIn, Instagram, Facebook, TikTok, dsb).

[DATA ALUMNI REFERENSI]
- NIM: {$nim}
- Nama Lengkap: {$namaLengkap}
- Nama Panggilan: {$namaPanggilan}
- Fakultas: {$fakultas}
- Program Studi: {$prodi}
- Tahun Masuk: {$tahunMasuk}
- Tanggal Lulus: {$tanggalLulus}
- Tahun Lulus (Ekstraksi): {$tahunLulus}
- Gelar Akademik (terakhir): {$gelar}
{$estimasiKarir}

[HASIL PENCARIAN INTERNET]
{$listProfilText}

[INSTRUKSI WAJIB UNTUK PENILAIAN]
1. Baca dengan teliti setiap [INDEX] hasil pencarian di atas.
2. Cari keselarasan kuat. Profil yang valid biasanya menyebutkan Nama yang mirip DAN/ATAU Program Studi ("Singkatan dari Prodi", atau "Fakultas") DAN kampus afiliasi terkait alumni ini. Jika ada penyebutan tahun yang masuk akal dengan masa pasca-kelulusan, beri poin plus.
3. KONTEKS PENTING UNTUK PENCOCOKAN:
   - Fakultas "{$fakultas}" dan Program Studi "{$prodi}" adalah kunci utama identifikasi. Jika profil menyebutkan fakultas/prodi yang SAMA atau sinonimnya, itu sangat meningkatkan kecocokan.
   - Tahun Masuk "{$tahunMasuk}" dan Tahun Lulus "{$tahunLulus}" membantu menentukan timeline.
   - Nama panggilan "{$namaPanggilan}" sering digunakan di sosial media.
4. PEMILIHAN PER PLATFORM: Dari hasil pencarian di atas, kamu WAJIB memilih SATU hasil 'terbaik' untuk **SETIAP** platform yang muncul. Meskipun semua hasil pada suatu platform tidak ada yang mirip sama sekali, kamu tetap HARUS memilih salah satu representasi (pilih SATU indeks yang memang berasal/milik dari platform tersebut) dan memberinya skor 0.0. Jangan lewatkan satu platform pun yang ada di daftar. Hasilkan mapping semua platform ini ke dalam `best_matches`.
5. SKOR INDIVIDUAL: Untuk setiap profil pilihan (0.0 hingga 1.0):
   - Skor Tinggi (0.8 - 1.0): Nama, kampus, akademik, pekerjaan jelas cocok.
   - Skor Menengah (0.5 - 0.7): Kemiripan nama tapi konteks parsial/ragu.
   - Skor Rendah (0.1 - 0.4): Nama agak mirip, beda pekerjaan jauh.
   - Skor 0.0: Tidak ada irisan kecocokan sama sekali (Irrelevan mutlak).
6. SKOR KESELURUHAN & ALASAN KESELURUHAN: Gabungkan tingkat keyakinan dari seluruh profil yang ditemukan. Jika ada satu profil ber-skor sangat tinggi (misal LinkedIn 0.9), `skor_keseluruhan` harus tinggi (di atas 0.8) karena kita yakin orang tersebut ditemukan. Jelaskan secara ringkas di `alasan_keseluruhan`.

[INSTRUKSI TAMBAHAN: EKSTRAKSI DATA INDIVIDUAL & GABUNGAN]
7. Untuk SETIAP profil yang Anda pilih dalam `best_matches`, Anda WAJIB melakukan ekstraksi informasi (kontak, pekerjaan, URL sosmed) yang tersedia di profil tersebut ke dalam field `extracted_data`.
8. EKSTRAKSI GABUNGAN (`unified_data`): Ini adalah bagian terpenting. Anda harus membuat satu profil "paling akurat" dengan menggabungkan semua temuan dari semua platform.
   - Jika platform A punya No HP dan platform B punya Email, gabungkan keduanya di `unified_data`.
   - Jika ada konflik (misal dua tempat kerja berbeda), pilih yang menurut Anda paling baru/update berdasarkan konteks (misal LinkedIn biasanya lebih update daripada Facebook lama).
   - Pastikan semua link sosial media yang valid (LinkedIn, IG, FB, TikTok) masuk ke field yang sesuai di `unified_data`.
9. Lakukan ekstraksi ini bahkan jika skor profil tersebut rendah (0.0 - 0.4), karena pengguna tetap ingin mendapatkan data tersebut jika memang ada di hasil pencarian.
10. Jika suatu informasi tidak ditemukan, biarkan string kosong "". Jangan mengarang data.
PROMPT;
    }
}
