<?php

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\TicketController;

// Midtrans Payment Callback
Route::post('/payment/midtranscallback', [PaymentController::class, 'midtransCallback'])
    ->name('payment.midtranscallback');

Route::middleware('auth:sanctum')->get('/user', function () {
    $userId = Auth::id();

    // Get all teams the user belongs to
    $userTeams =  User::find($userId)
        ->teams()
        ->pluck('team_id')
        ->toArray();

    // Make sure we're returning an array even if there are no results
    $teamIds = !empty($userTeams) ? $userTeams : [];

    return response()->json([
        'id' => $userId,
        'team_ids' => $teamIds,
    ]);
});
