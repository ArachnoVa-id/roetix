<?php

use App\Http\Controllers\ProfileController;
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

// order backend slash
Route::resource('orders', OrderController::class);

// route acara disini
Route::get('/eo/acara', [EoAcaraController::class, 'index'])
->middleware(['auth', 'verified', CheckRole::class])
->name('acara.index');

Route::get('/eo/buat-acara', [EoAcaraController::class, 'create'])
->middleware(['auth', 'verified', CheckRole::class])
->name('acara.create');

Route::get('/eo/edit-acara', [EoAcaraController::class, 'edit'])
->middleware(['auth', 'verified', CheckRole::class])
->name('acara.edit');

// route venue disini
Route::get('/eo/venue', [EoVenueController::class, 'index'])
->middleware(['auth', 'verified', CheckRole::class])
->name('vanue.index');

Route::get('/eo/sewa-venue', [EoVenueController::class, 'sewa'])
->middleware(['auth', 'verified', CheckRole::class])
->name('vanue.sewa');

Route::get('/eo/pengaturan-venue', [EoVenueController::class, 'pengaturan'])
->middleware(['auth', 'verified', CheckRole::class])
->name('vanue.pengaturan');

// route tiket disini
Route::get('/eo/tiket-pengaturan', [EoTiketController::class, 'pengaturan'])
->middleware(['auth', 'verified', CheckRole::class])
->name('tiket.pengaturan');

Route::get('/eo/tiket-harga', [EoTiketController::class, 'harga'])
->middleware(['auth', 'verified', CheckRole::class])
->name('tiket.harga');

Route::get('/eo/tiket-verifikasi', [EoTiketController::class, 'verifikasi'])
->middleware(['auth', 'verified', CheckRole::class])
->name('tiket.verifikasi');

// route analitik disini
Route::get('/eo/penjualan', [EoAnalitikController::class, 'analitikpenjualan'])
->middleware(['auth', 'verified', CheckRole::class])
->name('penjualan.index');

Route::get('/eo/riwayat-acara', [EoAnalitikController::class, 'riwayatacara'])
->middleware(['auth', 'verified', CheckRole::class])
->name('acara.riwayat');

// route profile disini
Route::get('/eo/profile', [EoProfilController::class, 'pengaturan'])
->middleware(['auth', 'verified', CheckRole::class])
->name('profil.index');

Route::get('/eo/profile-edit', [EoProfilController::class, 'edit'])
->middleware(['auth', 'verified', CheckRole::class])
->name('profil.edit');


Route::get('/dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
