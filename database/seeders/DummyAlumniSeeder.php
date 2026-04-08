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
            ['nim' => '201910370311001', 'nama_lengkap' => 'Ahmad Rizky Pratama', 'nama_panggilan' => 'Rizky', 'fakultas' => 'Teknik', 'prodi' => 'Informatika', 'tahun_masuk' => 2019, 'tahun_lulus' => 2023, 'gelar_akademik' => 'S.Kom'],
            ['nim' => '201910370311002', 'nama_lengkap' => 'Siti Nurhaliza Putri', 'nama_panggilan' => 'Haliza', 'fakultas' => 'Teknik', 'prodi' => 'Informatika', 'tahun_masuk' => 2019, 'tahun_lulus' => 2023, 'gelar_akademik' => 'S.Kom'],
            ['nim' => '201910370311003', 'nama_lengkap' => 'Muhammad Fauzan Hakim', 'nama_panggilan' => 'Fauzan', 'fakultas' => 'Teknik', 'prodi' => 'Informatika', 'tahun_masuk' => 2019, 'tahun_lulus' => 2023, 'gelar_akademik' => 'S.Kom'],
            ['nim' => '201910370311004', 'nama_lengkap' => 'Dewi Anggraini', 'nama_panggilan' => 'Dewi', 'fakultas' => 'Teknik', 'prodi' => 'Informatika', 'tahun_masuk' => 2019, 'tahun_lulus' => 2022, 'gelar_akademik' => 'S.Kom'],
            ['nim' => '201910370311005', 'nama_lengkap' => 'Budi Santoso', 'nama_panggilan' => 'Budi', 'fakultas' => 'Teknik', 'prodi' => 'Informatika', 'tahun_masuk' => 2019, 'tahun_lulus' => 2022, 'gelar_akademik' => 'S.Kom'],
            ['nim' => '201910370311006', 'nama_lengkap' => 'Rina Wulandari', 'nama_panggilan' => 'Rina', 'fakultas' => 'Teknik', 'prodi' => 'Teknik Elektro', 'tahun_masuk' => 2019, 'tahun_lulus' => 2023, 'gelar_akademik' => 'S.T'],
            ['nim' => '201910370311007', 'nama_lengkap' => 'Fajar Ramadhan', 'nama_panggilan' => 'Fajar', 'fakultas' => 'Teknik', 'prodi' => 'Teknik Elektro', 'tahun_masuk' => 2019, 'tahun_lulus' => 2022, 'gelar_akademik' => 'S.T'],
            ['nim' => '201910370311008', 'nama_lengkap' => 'Indah Permatasari', 'nama_panggilan' => 'Indah', 'fakultas' => 'Teknik', 'prodi' => 'Sistem Informasi', 'tahun_masuk' => 2019, 'tahun_lulus' => 2023, 'gelar_akademik' => 'S.Kom'],
            ['nim' => '201910370311009', 'nama_lengkap' => 'Andi Wijaya', 'nama_panggilan' => 'Andi', 'fakultas' => 'Teknik', 'prodi' => 'Sistem Informasi', 'tahun_masuk' => 2019, 'tahun_lulus' => 2022, 'gelar_akademik' => 'S.Kom'],
            ['nim' => '201910370311010', 'nama_lengkap' => 'Lestari Handayani', 'nama_panggilan' => 'Lestari', 'fakultas' => 'Teknik', 'prodi' => 'Informatika', 'tahun_masuk' => 2019, 'tahun_lulus' => 2021, 'gelar_akademik' => 'S.Kom'],
            ['nim' => '201910370311011', 'nama_lengkap' => 'Yoga Aditya', 'nama_panggilan' => 'Yoga', 'fakultas' => 'Teknik', 'prodi' => 'Informatika', 'tahun_masuk' => 2019, 'tahun_lulus' => 2021, 'gelar_akademik' => 'S.Kom'],
            ['nim' => '201910370311012', 'nama_lengkap' => 'Nur Aisyah', 'nama_panggilan' => 'Aisyah', 'fakultas' => 'Teknik', 'prodi' => 'Informatika', 'tahun_masuk' => 2019, 'tahun_lulus' => 2024, 'gelar_akademik' => 'S.Kom'],
            ['nim' => '201910370311013', 'nama_lengkap' => 'Reza Firmansyah', 'nama_panggilan' => 'Reza', 'fakultas' => 'Teknik', 'prodi' => 'Teknik Elektro', 'tahun_masuk' => 2019, 'tahun_lulus' => 2024, 'gelar_akademik' => 'S.T'],
            ['nim' => '201910370311014', 'nama_lengkap' => 'Dian Pratiwi', 'nama_panggilan' => 'Dian', 'fakultas' => 'Teknik', 'prodi' => 'Sistem Informasi', 'tahun_masuk' => 2019, 'tahun_lulus' => 2024, 'gelar_akademik' => 'S.Kom'],
            ['nim' => '201910370311015', 'nama_lengkap' => 'Hendra Kurniawan', 'nama_panggilan' => 'Hendra', 'fakultas' => 'Teknik', 'prodi' => 'Informatika', 'tahun_masuk' => 2019, 'tahun_lulus' => 2020, 'gelar_akademik' => 'S.Kom'],
        ];

        foreach ($alumniData as $data) {
            Alumni::updateOrCreate(
                ['nim' => $data['nim']],
                array_merge($data, ['status_pelacakan' => StatusPelacakan::BELUM_DILACAK])
            );
        }
    }
}
