<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PaymentController;

// Midtrans
Route::post('/payment/midtransCallback', [PaymentController::class, 'midtransCallback'])
    ->name('payment.midtransCallback');

// Fastpay
Route::post('/payment/faspayCallback', [PaymentController::class, 'faspayCallback'])
    ->name('payment.faspayCallback');

Route::get('/payment/faspayReturn', [PaymentController::class, 'faspayReturn'])
    ->name('payment.faspayReturn');

// Tripay
Route::post('/payment/tripayCallback', [PaymentController::class, 'tripayCallback'])
    ->name('payment.tripayCallback');
