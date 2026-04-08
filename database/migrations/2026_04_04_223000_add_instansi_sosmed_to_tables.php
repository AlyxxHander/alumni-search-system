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
        Schema::table('alumni', function (Blueprint $table) {
            $table->string('instansi_linkedin')->nullable()->after('sosmed_tempat_bekerja');
            $table->string('instansi_instagram')->nullable()->after('instansi_linkedin');
            $table->string('instansi_facebook')->nullable()->after('instansi_instagram');
            $table->string('instansi_tiktok')->nullable()->after('instansi_facebook');
        });

        Schema::table('tracking_results', function (Blueprint $table) {
            $table->string('instansi_linkedin')->nullable()->after('sosmed_tempat_bekerja');
            $table->string('instansi_instagram')->nullable()->after('instansi_linkedin');
            $table->string('instansi_facebook')->nullable()->after('instansi_instagram');
            $table->string('instansi_tiktok')->nullable()->after('instansi_facebook');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('alumni', function (Blueprint $table) {
            $table->dropColumn(['instansi_linkedin', 'instansi_instagram', 'instansi_facebook', 'instansi_tiktok']);
        });

        Schema::table('tracking_results', function (Blueprint $table) {
            $table->dropColumn(['instansi_linkedin', 'instansi_instagram', 'instansi_facebook', 'instansi_tiktok']);
        });
    }
};
