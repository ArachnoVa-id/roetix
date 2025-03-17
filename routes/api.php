<?php

use App\Http\Controllers\PaymentController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->get('/api/user', function () {
    $userId = Auth::id();
    
    // Get all teams the user belongs to
    $userTeams = DB::table('user_team')
        ->where('user_id', $userId)
        ->pluck('team_id')
        ->toArray();
        
    // Make sure we're returning an array even if there are no results
    $teamIds = !empty($userTeams) ? $userTeams : [];
    
    return response()->json([
        'user_id' => $userId,
        'team_ids' => $teamIds,
    ]);
});

// Midtrans Payment Callback 2
Route::post('/payment/callback', [PaymentController::class, 'callback']);