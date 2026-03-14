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
                            'best_index' => [
                                'type' => 'integer',
                                'description' => 'Index (mulai dari 0) dari profil hasil penelusuran yang paling menggambarkan alumni ini',
                            ],
                            'skor' => [
                                'type' => 'number',
                                'description' => 'Skor probabilitas kecocokan identitas untuk profil terbaik tersebut antara 0.0 sampai 1.0',
                            ],
                            'alasan' => [
                                'type' => 'string',
                                'description' => 'Alasan logis dan komprehensif mengapa profil tersebut cocok/tidak cocok dan dibandingkan ke indeks lainnya',
                            ],
                        ],
                        'required' => ['best_index', 'skor', 'alasan'],
                    ],
                ],
            ]);

            if ($response->successful()) {
                $data = $response->json();
                $text = $data['candidates'][0]['content']['parts'][0]['text'] ?? null;

                if ($text) {
                    // Extract JSON if it's wrapped in markdown ```json ... ```
                    if (preg_match('/```(?:json)?(.*?)```/s', $text, $matches)) {
                        $text = trim($matches[1]);
                    }

                    $result = json_decode($text, true);
                    if ($result && isset($result['skor']) && isset($result['best_index'])) {
                        return [
                            'best_index' => (int) $result['best_index'],
                            'skor' => (float) min(1.0, max(0.0, $result['skor'])),
                            'alasan' => $result['alasan'] ?? 'Tidak ada alasan yang diberikan',
                            'raw_response' => $data,
                        ];
                    } else {
                        Log::error("Gemini Parse Error. Text: " . $text);
                    }
                }
            }

            Log::error('Gemini API error', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return ['best_index' => 0, 'skor' => 0.0, 'alasan' => "Gagal menganalisis. HTTP Status: " . $response->status(), 'raw_response' => ["alasan" => "API Error: " . $response->status()]];
        } catch (\Exception $e) {
            Log::error('Gemini API exception: ' . $e->getMessage());
            return ['best_index' => 0, 'skor' => 0.0, 'alasan' => 'Error: ' . $e->getMessage(), 'raw_response' => ["alasan" => 'Exception: ' . $e->getMessage()]];
        }
    }

    protected function buildBatchPrompt(array $dataAsli, array $potonganInfoList): string
    {
        $nim = $dataAsli['nim'];
        $namaLengkap = $dataAsli['nama_lengkap'];
        $namaPanggilan = $dataAsli['nama_panggilan'];
        $prodi = $dataAsli['prodi'];
        $tahunLulus = $dataAsli['tahun_lulus'];
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

        return <<<PROMPT
Anda adalah "Verifikator Alumni", sebuah sistem AI pintar untuk mendeteksi kecocokan identitas.
Tugas utama Anda adalah membandingkan "Data Referensi Berkuliah" milik seorang alumni, dengan rentetan "Hasil Pencarian Internet" (Google Scholar, LinkedIn, dsb).

[DATA ALUMNI REFERENSI]
- NIM: {$nim}
- Nama Lengkap: {$namaLengkap}
- Nama Panggilan: {$namaPanggilan}
- Program Studi: {$prodi}
- Tahun Lulus: {$tahunLulus}
- Gelar Akademik (terakhir): {$gelar}

[HASIL PENCARIAN INTERNET]
{$listProfilText}

[INSTRUKSI WAJIB UNTUK PENILAIAN]
1. Baca dengan teliti setiap [INDEX] hasil pencarian di atas.
2. Cari keselarasan kuat. Profil yang valid biasanya menyebutkan Nama yang mirip DAN/ATAU Program Studi ("Singkatan dari Prodi", atau "Fakultas") DAN kampus afiliasi terkait alumni ini. Jika ada penyebutan tahun yang masuk akal dengan masa pasca-kelulusan, beri poin plus.
3. Tentukan 1 buah "best_index" (Paling Mewakili). Jika tidak ada satupun yang mewakili atau semuanya membahas orang/topik lain sama sekali, tetapkan "best_index" ke 0.
4. Tentukan "skor" secara objektif (angka desimal 0.0 hingga 1.0):
   - Skor Tinggi (0.8 s/d 1.0): Tersedia kecocokan Nama yang akurat, dan secara eksplisit menyinggung identitas kampus, jurusan akademik, nama pekerjaan/title/gelar yang jelas relevan.
   - Skor Menengah (0.5 s/d 0.7): Ada kemiripan nama kuat, tetapi informasi kampus/prodi/industri meragukan atau tidak secara eksplisit tertera di deskripsi snippet, namun ada kemungkinan itu adalah orang yang sama (misal terdeteksi lokasi yang sama dll).
   - Skor Rendah (0.1 s/d 0.4): Nama agak mirip tapi universitas/bidang kerjanya salah kaprah (seperti jurusan Informatika tapi hasil snippet adalah Dokter). Orang yang berbeda.
   - Skor 0.0: Tidak ada irisan kecocokan sama sekali (Irrelevan mutlak), atau hasil pencarian kosong.
5. Pada kolom "alasan", tulis narasi penjelasan berbahasa Indonesia yang terstruktur, rinci, analitis, dan langsung pada intinya (misal: "Profil Index 1 adalah yang paling akurat dengan Skor 0.9 karena secara langsung mencantumkan profil LinkedIn dengan gelar Sarjana Komputer beserta universitas yang sesuai, sementara indeks lainnya adalah profil untuk entitas dengan nama serupa namun berbeda bidang..."). JANGAN menyebut diri Anda sebagai AI.
PROMPT;
    }
}
