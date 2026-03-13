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
            // Drop the old constraint that might be referencing the wrong DB
            $table->dropForeign(['verified_by']);
            
            // Re-add it referencing the current DB's users table
            $table->foreign('verified_by')
                  ->references('id')
                  ->on('users')
                  ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tracking_results', function (Blueprint $table) {
            $table->dropForeign(['verified_by']);
            
            $table->foreign('verified_by')
                  ->references('id')
                  ->on('users')
                  ->nullOnDelete();
        });
    }
};
