<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tracking_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('alumni_id')->constrained('alumni')->onDelete('cascade');
            $table->string('sumber', 30);
            $table->text('query_digunakan');
            $table->string('judul_profil', 500)->nullable();
            $table->string('instansi', 255)->nullable();
            $table->string('lokasi', 255)->nullable();
            $table->text('url_profil')->nullable();
            $table->text('foto_url')->nullable();
            $table->text('snippet')->nullable();
            $table->decimal('skor_probabilitas', 3, 2)->default(0.00);
            $table->string('status_verifikasi', 20)->default('PENDING');
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('verified_at')->nullable();
            $table->json('raw_search_response')->nullable();
            $table->json('raw_gemini_response')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tracking_results');
    }
};
