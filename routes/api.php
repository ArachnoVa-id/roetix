<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->get('/api/user', function () {
    $userId = Auth::id();
    $teamIds = DB::table('user_team')
        ->where('user_id', $userId)
        ->pluck('team_id');

    return response()->json([
        'user_id' => $userId,
        'team_ids' => $teamIds,
    ]);
});
