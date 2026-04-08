<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('alumni', function (Blueprint $table) {
            $table->decimal('skor_keseluruhan', 3, 2)->default(0.00)->after('status_pelacakan');
        });
    }

    public function down(): void
    {
        Schema::table('alumni', function (Blueprint $table) {
            $table->dropColumn('skor_keseluruhan');
        });
    }
};
