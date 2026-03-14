# Alumni Search System (AlumniFinder)

Alumni Search System adalah platform berbasis web yang dirancang untuk melacak dan memverifikasi data karir alumni secara otomatis menggunakan integrasi AI dan OSINT. Sistem ini menggabungkan kekuatan pencarian Google (via Serper.dev) dengan kecerdasan Natural Language Processing dari Google Gemini API untuk memastikan akurasi data yang tinggi.

## 🚀 Algoritma Inti: "Smart-Context Triangulation"

Sistem beroperasi melalui siklus pelacakan cerdas yang terbagi menjadi 5 tahap utama:

1.  **Kelola Profil Target**: Menarik data mentah (Nama, NIM, Prodi, Tahun) dan melakukan konfigurasi variasi penulisan nama serta afiliasi (UMM, Informatika, dll) untuk meningkatkan akurasi.
2.  **Konfigurasi Parameter**: Admin menetapkan *threshold* (ambang batas) skor:
    *   **Skor > 0.8**: VALID_OTOMATIS.
    *   **Skor 0.5 - 0.8**: BUTUH_VERIFIKASI_MANUAL.
    *   **Skor < 0.5**: ABAIKAN.
3.  **Eksekusi Pelacakan (Scheduler)**: Sistem membangun query pencarian unik per alumni dan melakukan request ke Google Search API (LinkedIn, Scholar, GitHub).
4.  **Analisis & Skoring AI (Gemini API)**: Gemini menilai kecocokan identitas secara menyeluruh (timeline lulus vs riwayat kerja) dan mengembalikan skor probabilitas (0 - 1.0).
5.  **Verifikasi & Laporan**: Hasil yang membutuhkan verifikasi manual akan ditinjau oleh Admin. Data akhir yang terverifikasi kemudian dapat diekspor ke format Excel/PDF.

## 🛠️ Technology Stack

*   **Framework**: [Laravel 12.x](https://laravel.com)
*   **Intelligence Engine**: [Google Gemini AI](https://deepmind.google/technologies/gemini/) (Model: `gemini-3.1-flash-lite-preview`)
*   **Search Infrastructure**: [Serper.dev](https://serper.dev/) (Google Search API)
*   **Database**: MySQL
*   **Frontend**: Blade + Tailwind CSS (Modern Aesthetics, Responsive Design)

## 📦 Komponen Kunci

*   **`AlumniService`**: Mengelola data master alumni dan status pelacakan.
*   **`TrackingService`**: Mengorkestrasi pencarian Serper dan analisis Gemini.
*   **`GeminiService`**: Menangani interaksi dengan API Google Gemini untuk skoring profil.
*   **`SerperService`**: Mengelola query dan hasil dari Google Custom Search API.

## ✅ Quality Testing Results

| Aspek Kualitas | Kriteria Uji | Hasil Evaluasi | Status |
| :--- | :--- | :--- | :--- |
| **Akurasi Identitas** | Penggunaan variasi nama dan konteks lulus (Tahun, Gelar). | Sistem mengolah array nama panggilan/inisial dan membandingkan data prodi/universitas saat skoring. | ✅ Pass |
| **Parameter Skoring** | Implementasi sistem threshold yang fleksibel (0.5 - 0.8). | Logic di `TrackingService` berhasil mengkategorikan data ke *Auto-Valid* atau *Manual Review* sesuai threshold. | ✅ Pass |
| **Integrasi Sumber** | Pelacakan melalui LinkedIn, GitHub, dan Google Scholar. | Request API dikonfigurasi untuk memprioritaskan domain profesional dan akademik sesuai spesifikasi. | ✅ Pass |
| **Analisis Gemini** | Evaluasi timeline dan relevansi instansi oleh AI. | Prompt Gemini dioptimasi untuk mendeteksi anomali timeline (misal: bekerja sebelum lulus tanpa korelasi yang jelas). | ✅ Pass |
| **Reliabilitas Data** | Mekanisme verifikasi manual untuk skor kandidat. | Dashboard Admin menyediakan antarmuka peninjauan yang memisahkan data pasti dengan data *probabilistic*. | ✅ Pass |
| **Output Sistem** | Ekspor data hasil pelacakan ke format laporan resmi. | Mendukung ekspor ke Excel/PDF untuk kebutuhan pelaporan IKU (Indikator Kinerja Utama). | ✅ Pass |

---
*Created for Alumni Career Tracking & Higher Education Quality Assurance.*
