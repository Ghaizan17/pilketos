<?php

use App\Http\Controllers\Api\VoteController;
use App\Http\Controllers\Api\VoterController;
use App\Http\Controllers\BoothController;
use Illuminate\Support\Facades\Route;

Route::get('/', [BoothController::class, 'home'])->name('home');

Route::middleware('guest')->group(function () {
    Route::get('/masuk', [App\Http\Controllers\BoothAuthController::class, 'show'])->name('booth.login');
    // Alias agar middleware auth mengarah ke login bilik (bukan error route-hilang).
    Route::get('/login', [App\Http\Controllers\BoothAuthController::class, 'show'])->name('login');
    Route::post('/masuk', [App\Http\Controllers\BoothAuthController::class, 'login'])->name('booth.login.attempt');
});

Route::middleware('auth')->group(function () {
    Route::get('/bilik', [BoothController::class, 'index'])->name('booth.scan');
    Route::post('/keluar', [App\Http\Controllers\BoothAuthController::class, 'logout'])->name('booth.logout');

    // Public booth API (dipakai JS halaman bilik; session + CSRF via web middleware).
    Route::prefix('api')->group(function () {
        Route::get('/voters/{uuid}', [VoterController::class, 'show']);
        Route::post('/votes', [VoteController::class, 'store']);
    });

    // Cetak kartu QR (dibuka dari Filament; auth via session guard).
    Route::get('/students/qr-cards', [App\Http\Controllers\StudentQrCardController::class, 'bulk'])->name('students.qr-cards');
    Route::get('/students/qr-cards.pdf', [App\Http\Controllers\StudentQrCardController::class, 'pdf'])->name('students.qr-cards.pdf');
    Route::get('/students/qr-pdf/{job}', [App\Http\Controllers\StudentQrCardController::class, 'file'])->name('students.qr-pdf-file')->where('job', '[0-9a-f-]+');
    Route::get('/students/{student:uuid}/qr-card', [App\Http\Controllers\StudentQrCardController::class, 'show'])->name('students.qr-card');
});
