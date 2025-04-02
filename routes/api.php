<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PaymentController;

// Midtrans Payment Callback
Route::post('/payment/midtranscallback', [PaymentController::class, 'midtransCallback'])
    ->name('payment.midtranscallback');
