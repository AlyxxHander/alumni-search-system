<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('alumni', function (Blueprint $table) {
            $table->dropColumn(['inisial_belakang', 'tanggal_lulus']);
        });
    }

    public function down(): void
    {
        Schema::table('alumni', function (Blueprint $table) {
            $table->string('inisial_belakang', 100)->nullable()->after('nama_panggilan');
            $table->string('tanggal_lulus', 50)->nullable()->after('tahun_masuk');
        });
    }
};
