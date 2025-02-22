<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SeatController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Middleware\CheckRole;

use App\Http\Controllers\OrderController;
use App\Http\Controllers\EoPenjualanController;
use App\Http\Controllers\EoAnalitikController;
use App\Http\Controllers\EoAcaraController;
use App\Http\Controllers\EoVenueController;
use App\Http\Controllers\EoTiketController;
use App\Http\Controllers\EoProfilController;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

Route::get('/test', function () {
    return Inertia::render('Test');
});

Route::middleware(['auth', 'verified', CheckRole::class])->prefix('eo')->group(function () {

    // Route untuk Acara
    Route::prefix('acara')->name('acara.')->group(function () {
        Route::get('/', [EoAcaraController::class, 'index'])->name('index');
        Route::get('/buat', [EoAcaraController::class, 'create'])->name('create');
        Route::get('/edit', [EoAcaraController::class, 'edit'])->name('edit');
    });

    // Route untuk Venue
    Route::prefix('venue')->name('venue.')->group(function () {
        Route::get('/', [EoVenueController::class, 'index'])->name('index');
        Route::get('/sewa', [EoVenueController::class, 'sewa'])->name('sewa');
        Route::get('/pengaturan', [EoVenueController::class, 'pengaturan'])->name('pengaturan');
    });

    // Route untuk Tiket
    Route::prefix('tiket')->name('tiket.')->group(function () {
        Route::get('/pengaturan', [EoTiketController::class, 'pengaturan'])->name('pengaturan');
        Route::get('/harga', [EoTiketController::class, 'harga'])->name('harga');
        Route::get('/verifikasi', [EoTiketController::class, 'verifikasi'])->name('verifikasi');
    });

    // Route untuk Analitik
    Route::prefix('analitik')->group(function () {
        Route::get('/penjualan', [EoAnalitikController::class, 'analitikpenjualan'])
        ->name('penjualan.index');
        Route::get('/penjualan/{orderId}', [EoAnalitikController::class, 'penjualan'])
        ->name('penjualan.detail');
        Route::get('/riwayat-acara', [EoAnalitikController::class, 'riwayatacara'])
        ->name('acara.riwayat');
    });

    // Route untuk Profil
    Route::prefix('profile')->name('profil.')->group(function () {
        Route::get('/', [EoProfilController::class, 'pengaturan'])->name('index');
        Route::get('/edit', [EoProfilController::class, 'edit'])->name('edit');
    });

});


Route::get('/dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    Route::get('/seats', [SeatController::class, 'index'])->name('seats.index');
    Route::get('/seats/edit', [SeatController::class, 'edit'])->name('seats.edit');
    Route::post('/seats/update', [SeatController::class, 'update'])->name('seats.update');
});

// Route::get('/seats', [SeatController::class, 'index'])->name('seats.index');
// Route::get('/seats/edit', [SeatController::class, 'edit'])->name('seats.edit');
// Route::post('/seats/update', [SeatController::class, 'update'])->name('seats.update');

require __DIR__.'/auth.php';
