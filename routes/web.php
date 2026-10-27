<?php

use App\Http\Controllers\Api\VoteController;
use App\Http\Controllers\Api\VoterController;
use App\Http\Controllers\BoothController;
use Illuminate\Support\Facades\Route;

Route::get('/', [BoothController::class, 'home'])->name('home');

Route::middleware('guest')->group(function () {
    // Login bilik operator: satu-satunya path /login (nama kanonis `login`
    // agar middleware `auth` redirect ke sini saat buka /bilik tanpa login).
    // Filament /admin tetap memakai /admin/login via panel provider.
    Route::get('/login', [App\Http\Controllers\BoothAuthController::class, 'show'])->name('login');
    Route::post('/login', [App\Http\Controllers\BoothAuthController::class, 'login'])->name('booth.login.attempt');
});

// Kompatibilitas: path lama /masuk diarahkan permanen ke /login.
Route::redirect('/masuk', '/login', 301);

Route::middleware('auth')->group(function () {
    // Halaman scan + API bilik: boleh operator maupun scanner.
    Route::get('/bilik', [BoothController::class, 'index'])->name('booth.scan');
    Route::post('/keluar', [App\Http\Controllers\BoothAuthController::class, 'logout'])->name('booth.logout');

    Route::prefix('api')->group(function () {
        Route::get('/voters/{uuid}', [VoterController::class, 'show']);
        Route::post('/votes', [VoteController::class, 'store']);
    });

    // Cetak kartu QR: hanya operator (scanner cukup buka /bilik).
    Route::middleware('operator')->group(function () {
        Route::get('/students/qr-cards', [App\Http\Controllers\StudentQrCardController::class, 'bulk'])->name('students.qr-cards');
        Route::get('/students/qr-cards.pdf', [App\Http\Controllers\StudentQrCardController::class, 'pdf'])->name('students.qr-cards.pdf');
        Route::get('/students/qr-pdf/{job}', [App\Http\Controllers\StudentQrCardController::class, 'file'])->name('students.qr-pdf-file')->where('job', '[0-9a-f-]+');
        Route::get('/students/{student:uuid}/qr-card', [App\Http\Controllers\StudentQrCardController::class, 'show'])->name('students.qr-card');
    });
});
