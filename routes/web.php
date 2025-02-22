<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SeatController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
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
    Route::get('/seats/spreadsheet', [SeatController::class, 'spreadsheet'])->name('seats.spreadsheet');
});

// Route::get('/seats', [SeatController::class, 'index'])->name('seats.index');
// Route::get('/seats/edit', [SeatController::class, 'edit'])->name('seats.edit');
// Route::post('/seats/update', [SeatController::class, 'update'])->name('seats.update');

require __DIR__.'/auth.php';
