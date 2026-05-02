<?php

namespace App\Console\Commands;

use App\Jobs\BulkTrackAlumniJob;
use App\Models\Alumni;
use App\Enums\StatusPelacakan;
use Illuminate\Console\Command;

class BulkTrackCommand extends Command
{
    protected $signature = 'alumni:bulk-track
        {--batch-size=5 : Jumlah alumni per batch Gemini request}
        {--limit=0 : Limit jumlah alumni yang diproses (0 = semua)}
        {--resume : Hanya proses alumni yang belum dilacak/perlu update}
        {--reset-stuck : Reset alumni yang stuck di SEDANG_DILACAK ke BELUM_DILACAK}';

    protected $description = 'Jalankan bulk tracking alumni menggunakan Playwright scraper + Gemini batch verification';

    public function handle(): int
    {
        // Handle --reset-stuck
        if ($this->option('reset-stuck')) {
            $count = Alumni::where('status_pelacakan', StatusPelacakan::SEDANG_DILACAK)
                ->update(['status_pelacakan' => StatusPelacakan::BELUM_DILACAK]);
            $this->info("✅ {$count} alumni di-reset dari SEDANG_DILACAK ke BELUM_DILACAK.");
            return 0;
        }

        $batchSize = (int) $this->option('batch-size');
        $limit = (int) $this->option('limit');

        // Query alumni yang perlu dilacak
        $query = Alumni::perluDilacak()->orderBy('id');

        if ($limit > 0) {
            $query->take($limit);
        }

        $alumniList = $query->get();

        if ($alumniList->isEmpty()) {
            $this->info('✅ Tidak ada alumni yang perlu dilacak.');
            return 0;
        }

        $this->info("📊 Total alumni yang akan diproses: {$alumniList->count()}");
        $this->info("📦 Batch size: {$batchSize} alumni per job");

        $chunks = $alumniList->chunk($batchSize);
        $totalBatches = $chunks->count();

        $this->info("🚀 Akan membuat {$totalBatches} batch job...");
        $this->newLine();

        if (!$this->confirm("Lanjutkan dispatch {$totalBatches} job?", true)) {
            $this->warn('Dibatalkan.');
            return 0;
        }

        $bar = $this->output->createProgressBar($totalBatches);
        $bar->start();

        foreach ($chunks as $chunk) {
            BulkTrackAlumniJob::dispatch(
                $chunk->pluck('id')->toArray()
            );
            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        $this->info("✅ {$totalBatches} batch job telah di-dispatch ke queue.");
        $this->info("💡 Jalankan queue worker: php artisan queue:work --timeout=900");

        return 0;
    }
}
