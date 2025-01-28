<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Middleware\CheckRole;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\EoPenjualanController;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

// order backend slash
Route::resource('orders', OrderController::class);

Route::get('/penjualan', [EoPenjualanController::class, 'index'])
->middleware(['auth', 'verified', CheckRole::class])
->name('penjualan.index');

Route::get('/kursi', function () {
    return Inertia::render('EoKursi/Index', [
        'title' => 'Kursi',
        'subtitle' => 'Overview',
    ]);
})
->middleware(['auth', 'verified', CheckRole::class])
->name('kursi.index');

Route::get('/tiket', function () {
    return Inertia::render('EoTiket/Index', [
        'title' => 'Tiket',
        'subtitle' => 'Harga Tiket',
    ]);
})
->middleware(['auth', 'verified', CheckRole::class])
->name('tiket.index');


Route::get('/dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
