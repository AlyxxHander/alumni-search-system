<?php

namespace App\Console\Commands;

use App\Models\Alumni;
use App\Models\TrackingResult;
use App\Enums\SumberPelacakan;
use Illuminate\Console\Command;

class FixMissingPlatformLinksCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'alumni:fix-links';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Memperbaiki data alumni yang sudah dilacak dengan menambahkan link profil platform jika sempat diabaikan AI.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info("Memulai perbaikan link platform...");

        $alumnis = Alumni::whereHas('trackingResults')->get();
        $updatedCount = 0;

        foreach ($alumnis as $alumni) {
            $platforms = ['linkedin', 'instagram', 'facebook', 'tiktok'];
            $foundLinks = [];
            
            // Cari link dari tabel TrackingResult per platform
            foreach ($platforms as $platform) {
                $sumber = strtoupper($platform);
                
                $result = TrackingResult::where('alumni_id', $alumni->id)
                            ->where('sumber', $sumber)
                            ->whereNotNull('url_profil')
                            ->where('url_profil', '!=', '')
                            ->orderBy('id', 'desc')
                            ->first();

                if ($result) {
                    $foundLinks[$platform] = $result->url_profil;
                    
                    // Update the individual TrackingResult field if it was empty
                    if (empty($result->$platform)) {
                        $result->$platform = $result->url_profil;
                        $result->save();
                    }
                }
            }

            if (!empty($foundLinks)) {
                $hasUpdate = false;
                
                // Update tabel Alumni
                foreach ($foundLinks as $platform => $url) {
                    if (empty($alumni->$platform)) {
                        $alumni->$platform = $url;
                        $hasUpdate = true;
                    }
                }

                if ($hasUpdate) {
                    $alumni->save();
                    $updatedCount++;
                }
                
                // Update tracking result GABUNGAN
                $gabungan = TrackingResult::where('alumni_id', $alumni->id)
                            ->where('sumber', SumberPelacakan::GABUNGAN)
                            ->orderBy('id', 'desc')
                            ->first();
                            
                if ($gabungan) {
                    $gabunganUpdated = false;
                    
                    // Update database columns on GABUNGAN
                    foreach ($foundLinks as $platform => $url) {
                        if (empty($gabungan->$platform)) {
                            $gabungan->$platform = $url;
                            $gabunganUpdated = true;
                        }
                    }
                    
                    // Update JSON raw_gemini_response['unified_data'] if needed
                    $rawGemini = $gabungan->raw_gemini_response ?? [];
                    if (isset($rawGemini['unified_data'])) {
                        foreach ($foundLinks as $platform => $url) {
                            if (empty($rawGemini['unified_data'][$platform])) {
                                $rawGemini['unified_data'][$platform] = $url;
                                $gabunganUpdated = true;
                            }
                        }
                        $gabungan->raw_gemini_response = $rawGemini;
                    }

                    if ($gabunganUpdated) {
                        $gabungan->save();
                    }
                }
            }
        }

        $this->info("Selesai! Berhasil memperbaiki $updatedCount data alumni.");
    }
}
