<?php

namespace App\Console\Commands;

use App\Jobs\TrackAlumniJob;
use App\Models\Alumni;
use Illuminate\Console\Command;

class RunTrackingCommand extends Command
{
    protected $signature = 'alumni:track {--nim= : Lacak alumni spesifik berdasarkan NIM}';
    protected $description = 'Menjalankan pelacakan alumni otomatis';

    public function handle(): int
    {
        $nim = $this->option('nim');

        if ($nim) {
            $alumni = Alumni::where('nim', $nim)->first();
            if (!$alumni) {
                $this->error("Alumni dengan NIM {$nim} tidak ditemukan.");
                return 1;
            }
            TrackAlumniJob::dispatch($alumni);
            $this->info("Job pelacakan di-dispatch untuk: {$alumni->nama_lengkap}");
            return 0;
        }

        $alumniList = Alumni::perluDilacak()->get();

        if ($alumniList->isEmpty()) {
            $this->info('Tidak ada alumni yang perlu dilacak.');
            return 0;
        }

        $this->info("Memulai pelacakan {$alumniList->count()} alumni...");

        $bar = $this->output->createProgressBar($alumniList->count());
        $bar->start();

        foreach ($alumniList as $alumni) {
            TrackAlumniJob::dispatch($alumni);
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info('Semua job pelacakan telah di-dispatch.');

        return 0;
    }
}
