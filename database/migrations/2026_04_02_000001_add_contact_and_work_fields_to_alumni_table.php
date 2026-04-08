<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('alumni', function (Blueprint $table) {
            // Data akademik tambahan (dari spreadsheet)
            $table->year('tahun_masuk')->nullable()->after('gelar_akademik');
            $table->string('tanggal_lulus', 50)->nullable()->after('tahun_masuk');
            $table->string('fakultas', 150)->nullable()->after('tanggal_lulus');

            // Kontak
            $table->string('email', 255)->nullable()->after('fakultas');
            $table->string('no_hp', 30)->nullable()->after('email');

            // Sosial media alumni
            $table->string('linkedin', 500)->nullable()->after('no_hp');
            $table->string('instagram', 255)->nullable()->after('linkedin');
            $table->string('facebook', 255)->nullable()->after('instagram');
            $table->string('tiktok', 255)->nullable()->after('facebook');

            // Data pekerjaan
            $table->string('tempat_bekerja', 255)->nullable()->after('tiktok');
            $table->text('alamat_bekerja')->nullable()->after('tempat_bekerja');
            $table->string('posisi', 255)->nullable()->after('alamat_bekerja');
            $table->string('jenis_pekerjaan', 30)->nullable()->after('posisi');
            $table->text('sosmed_tempat_bekerja')->nullable()->after('jenis_pekerjaan');
        });
    }

    public function down(): void
    {
        Schema::table('alumni', function (Blueprint $table) {
            $table->dropColumn([
                'tahun_masuk',
                'tanggal_lulus',
                'fakultas',
                'email',
                'no_hp',
                'linkedin',
                'instagram',
                'facebook',
                'tiktok',
                'tempat_bekerja',
                'alamat_bekerja',
                'posisi',
                'jenis_pekerjaan',
                'sosmed_tempat_bekerja',
            ]);
        });
    }
};
