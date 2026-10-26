<?php

use App\Http\Controllers\Api\VoteController;
use App\Http\Controllers\Api\VoterController;
use App\Http\Controllers\BoothController;
use Illuminate\Support\Facades\Route;

Route::get('/', [BoothController::class, 'index']);

// Public booth API (session + CSRF via web middleware; no api.php in this scaffold).
Route::prefix('api')->group(function () {
    Route::get('/voters/{uuid}', [VoterController::class, 'show']);
    Route::post('/votes', [VoteController::class, 'store']);
});

// Admin QR-print cards (opened from Filament; auth via Filament session guard below).
use App\Http\Controllers\StudentQrCardController;
Route::middleware('auth')->group(function () {
    Route::get('/students/qr-cards', [StudentQrCardController::class, 'bulk'])->name('students.qr-cards');
    Route::get('/students/qr-cards.pdf', [StudentQrCardController::class, 'pdf'])->name('students.qr-cards.pdf');
    Route::get('/students/qr-pdf/{job}', [StudentQrCardController::class, 'file'])->name('students.qr-pdf-file')->where('job', '[0-9a-f-]+');
    Route::get('/students/{student:uuid}/qr-card', [StudentQrCardController::class, 'show'])->name('students.qr-card');
});
