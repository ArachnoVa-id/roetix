<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SeatController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

use App\Http\Controllers\OrderController;
use App\Http\Controllers\EoPenjualanController;
use App\Http\Controllers\EoAnalitikController;
use App\Http\Controllers\EoAcaraController;
use App\Http\Controllers\EoVenueController;
use App\Http\Controllers\EoTiketController;
use App\Http\Controllers\EoProfilController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\SocialiteController;
use App\Http\Controllers\UserPageController;
use Illuminate\Support\Facades\Auth;

use App\Http\Controllers\SeatGridController;

Route::get('/test-csrf', function () {
    return response()->json(['csrf_token' => csrf_token()]);
});

Route::get('/', [UserPageController::class, 'landing'])->name('home');

Route::domain('{client}.' . config('app.domain'))->group(function () {
    Route::get('/', [UserPageController::class, 'landing'])->name('client.home');
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('client.login');
});

Route::post('/payment/charge', [PaymentController::class, 'createCharge'])->name('payment.charge');
Route::post('/payment/midtranscallback', [PaymentController::class, 'midtransCallback'])->name('payment.midtranscallback');

Route::get('/seats', [SeatController::class, 'index'])->name('seats.index');
Route::get('/seats/edit', [SeatController::class, 'edit'])->name('seats.edit');
Route::post('/seats/update', [SeatController::class, 'update'])->name('seats.update');
Route::get('/seats/spreadsheet', [SeatController::class, 'spreadsheet'])->name('seats.spreadsheet');
Route::get('/seats/grid-edit', [SeatController::class, 'gridEdit'])->name('seats.grid-edit');
Route::post('/seats/update-layout', [SeatController::class, 'updateLayout'])->name('seats.update-layout');

Route::get('/my_tickets', [UserPageController::class, 'my_tickets'])->name('my_tickets');

Route::get('/test', function () {
    return Inertia::render('Test');
});

Route::controller(SocialiteController::class)->group(function () {
    Route::get('auth/google', 'googleLogin')->name('auth.google');
    Route::get('auth/google-callback', 'googleAuthentication')->name('auth.google-authentication');
});

Route::get('/test-login', function () {
    $user = \App\Models\User::where('email', 'vendor1@example.com')->first();
    Auth::login($user);

    return redirect('/');
});

Route::middleware(['auth', 'verified'])->prefix('eo')->group(function () {

    // Route untuk Acara
    Route::prefix('acara')->name('acara.')->group(function () {
        Route::get('/', [EoAcaraController::class, 'index'])->name('index');
        Route::get('/buat', [EoAcaraController::class, 'create'])->name('create');
        Route::get('/edit', [EoAcaraController::class, 'edit'])->name('edit');
    });

    // Route::get('/seats', [SeatController::class, 'index'])->name('seats.index');
    // Route::get('/seats/edit', [SeatController::class, 'edit'])->name('seats.edit');
    // Route::post('/seats/update', [SeatController::class, 'update'])->name('seats.update');
    // Route::get('/seats/spreadsheet', [SeatController::class, 'spreadsheet'])->name('seats.spreadsheet');

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


// Route::get('/dashboard', function () {
//     return Inertia::render('Dashboard');
// })->middleware(['auth', 'verified'])->name('home');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// Route::get('/seats', [SeatController::class, 'index'])->name('seats.index');
// Route::get('/seats/edit', [SeatController::class, 'edit'])->name('seats.edit');
// Route::post('/seats/update', [SeatController::class, 'update'])->name('seats.update');

require __DIR__ . '/auth.php';
