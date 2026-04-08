<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('tracking_results', function (Blueprint $table) {
            // Kontak
            $table->string('email')->nullable()->after('snippet');
            $table->string('no_hp')->nullable()->after('email');

            // Sosial Media
            $table->string('linkedin')->nullable()->after('no_hp');
            $table->string('instagram')->nullable()->after('linkedin');
            $table->string('facebook')->nullable()->after('instagram');
            $table->string('tiktok')->nullable()->after('facebook');

            // Pekerjaan
            $table->string('tempat_bekerja')->nullable()->after('tiktok');
            $table->string('alamat_bekerja')->nullable()->after('tempat_bekerja');
            $table->string('posisi')->nullable()->after('alamat_bekerja');
            $table->string('jenis_pekerjaan')->nullable()->after('posisi');
            $table->text('sosmed_tempat_bekerja')->nullable()->after('jenis_pekerjaan');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tracking_results', function (Blueprint $table) {
            $table->dropColumn([
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
                'sosmed_tempat_bekerja'
            ]);
        });
    }
};
