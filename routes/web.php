<?php

use App\Http\Controllers\AlumniController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ConfigController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\TrackingController;
use App\Http\Controllers\VerificationController;
use Illuminate\Support\Facades\Route;

// Auth routes
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// Protected routes
Route::middleware(['auth'])->group(function () {
    // Dashboard
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    // Alumni CRUD
    Route::resource('alumni', AlumniController::class)->parameters([
        'alumni' => 'alumni'
    ]);
    Route::post('alumni/{alumni}/unvalidate', [AlumniController::class, 'unvalidate'])->name('alumni.unvalidate');

    // Pelacakan
    Route::post('tracking/run', [TrackingController::class, 'runAll'])->name('tracking.run');
    Route::post('tracking/run/{alumni}', [TrackingController::class, 'runSingle'])->name('tracking.run-single');

    // Verifikasi Manual
    Route::get('verification', [VerificationController::class, 'index'])->name('verification.index');
    Route::post('verification/{trackingResult}/confirm', [VerificationController::class, 'confirm'])->name('verification.confirm');
    Route::post('verification/{trackingResult}/reject', [VerificationController::class, 'reject'])->name('verification.reject');
    Route::post('verification/{trackingResult}/skip', [VerificationController::class, 'skip'])->name('verification.skip');

    // Laporan
    Route::get('reports', [ReportController::class, 'index'])->name('reports.index');
    Route::get('reports/export/csv', [ReportController::class, 'exportCsv'])->name('reports.export.csv');
    Route::get('reports/export/pdf', [ReportController::class, 'exportPdf'])->name('reports.export.pdf');

    // Konfigurasi
    Route::get('config', [ConfigController::class, 'index'])->name('config.index');
    Route::put('config', [ConfigController::class, 'update'])->name('config.update');
});
