<?php

namespace Database\Seeders;

use App\Enums\StatusPelacakan;
use App\Models\Alumni;
use Illuminate\Database\Seeder;

class DummyAlumniSeeder extends Seeder
{
    public function run(): void
    {
        $alumniData = [
            ['nim' => '201910370311001', 'nama_lengkap' => 'Ahmad Rizky Pratama', 'nama_panggilan' => 'Rizky', 'inisial_belakang' => 'A. Pratama', 'prodi' => 'Informatika', 'tahun_lulus' => 2023, 'gelar_akademik' => 'S.Kom'],
            ['nim' => '201910370311002', 'nama_lengkap' => 'Siti Nurhaliza Putri', 'nama_panggilan' => 'Haliza', 'inisial_belakang' => 'S. Putri', 'prodi' => 'Informatika', 'tahun_lulus' => 2023, 'gelar_akademik' => 'S.Kom'],
            ['nim' => '201910370311003', 'nama_lengkap' => 'Muhammad Fauzan Hakim', 'nama_panggilan' => 'Fauzan', 'inisial_belakang' => 'M. Hakim', 'prodi' => 'Informatika', 'tahun_lulus' => 2023, 'gelar_akademik' => 'S.Kom'],
            ['nim' => '201910370311004', 'nama_lengkap' => 'Dewi Anggraini', 'nama_panggilan' => 'Dewi', 'inisial_belakang' => 'D. Anggraini', 'prodi' => 'Informatika', 'tahun_lulus' => 2022, 'gelar_akademik' => 'S.Kom'],
            ['nim' => '201910370311005', 'nama_lengkap' => 'Budi Santoso', 'nama_panggilan' => 'Budi', 'inisial_belakang' => 'B. Santoso', 'prodi' => 'Informatika', 'tahun_lulus' => 2022, 'gelar_akademik' => 'S.Kom'],
            ['nim' => '201910370311006', 'nama_lengkap' => 'Rina Wulandari', 'nama_panggilan' => 'Rina', 'inisial_belakang' => 'R. Wulandari', 'prodi' => 'Teknik Elektro', 'tahun_lulus' => 2023, 'gelar_akademik' => 'S.T'],
            ['nim' => '201910370311007', 'nama_lengkap' => 'Fajar Ramadhan', 'nama_panggilan' => 'Fajar', 'inisial_belakang' => 'F. Ramadhan', 'prodi' => 'Teknik Elektro', 'tahun_lulus' => 2022, 'gelar_akademik' => 'S.T'],
            ['nim' => '201910370311008', 'nama_lengkap' => 'Indah Permatasari', 'nama_panggilan' => 'Indah', 'inisial_belakang' => 'I. Permatasari', 'prodi' => 'Sistem Informasi', 'tahun_lulus' => 2023, 'gelar_akademik' => 'S.Kom'],
            ['nim' => '201910370311009', 'nama_lengkap' => 'Andi Wijaya', 'nama_panggilan' => 'Andi', 'inisial_belakang' => 'A. Wijaya', 'prodi' => 'Sistem Informasi', 'tahun_lulus' => 2022, 'gelar_akademik' => 'S.Kom'],
            ['nim' => '201910370311010', 'nama_lengkap' => 'Lestari Handayani', 'nama_panggilan' => 'Lestari', 'inisial_belakang' => 'L. Handayani', 'prodi' => 'Informatika', 'tahun_lulus' => 2021, 'gelar_akademik' => 'S.Kom'],
            ['nim' => '201910370311011', 'nama_lengkap' => 'Yoga Aditya', 'nama_panggilan' => 'Yoga', 'inisial_belakang' => 'Y. Aditya', 'prodi' => 'Informatika', 'tahun_lulus' => 2021, 'gelar_akademik' => 'S.Kom'],
            ['nim' => '201910370311012', 'nama_lengkap' => 'Nur Aisyah', 'nama_panggilan' => 'Aisyah', 'inisial_belakang' => 'N. Aisyah', 'prodi' => 'Informatika', 'tahun_lulus' => 2024, 'gelar_akademik' => 'S.Kom'],
            ['nim' => '201910370311013', 'nama_lengkap' => 'Reza Firmansyah', 'nama_panggilan' => 'Reza', 'inisial_belakang' => 'R. Firmansyah', 'prodi' => 'Teknik Elektro', 'tahun_lulus' => 2024, 'gelar_akademik' => 'S.T'],
            ['nim' => '201910370311014', 'nama_lengkap' => 'Dian Pratiwi', 'nama_panggilan' => 'Dian', 'inisial_belakang' => 'D. Pratiwi', 'prodi' => 'Sistem Informasi', 'tahun_lulus' => 2024, 'gelar_akademik' => 'S.Kom'],
            ['nim' => '201910370311015', 'nama_lengkap' => 'Hendra Kurniawan', 'nama_panggilan' => 'Hendra', 'inisial_belakang' => 'H. Kurniawan', 'prodi' => 'Informatika', 'tahun_lulus' => 2020, 'gelar_akademik' => 'S.Kom'],
        ];

        foreach ($alumniData as $data) {
            Alumni::updateOrCreate(
                ['nim' => $data['nim']],
                array_merge($data, ['status_pelacakan' => StatusPelacakan::BELUM_DILACAK])
            );
        }
    }
}
