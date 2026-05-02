<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bulk_tracking_logs', function (Blueprint $table) {
            $table->id();
            $table->json('batch_alumni_ids');
            $table->enum('status', ['queued', 'processing', 'completed', 'failed'])->default('queued');
            $table->integer('total_alumni')->default(0);
            $table->integer('success_count')->default(0);
            $table->integer('failed_count')->default(0);
            $table->text('error_message')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bulk_tracking_logs');
    }
};
