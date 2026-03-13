<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('alumni', function (Blueprint $table) {
            $table->id();
            $table->string('nim', 20)->unique();
            $table->string('nama_lengkap', 255);
            $table->string('nama_panggilan', 100)->nullable();
            $table->string('inisial_belakang', 100)->nullable();
            $table->string('prodi', 100);
            $table->year('tahun_lulus');
            $table->string('gelar_akademik', 50)->nullable();
            $table->string('status_pelacakan', 30)->default('BELUM_DILACAK');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('alumni');
    }
};
