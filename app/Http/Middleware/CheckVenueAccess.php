<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CheckVenueAccess
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next)
    {
        $userId = Auth::id();

        // Check both route parameter and query parameter for venue_id
        $venueId = $request->route('venue_id') ?? $request->query('venue_id');

        if (!$venueId) {
            abort(400, 'Missing venue_id');
        }

        // Ambil semua team_id milik user yang login
        $userTeamIds = DB::table('user_team')
            ->where('user_id', $userId)
            ->pluck('team_id')
            ->toArray();

        // Ambil team_id dari venue
        $venueTeamId = DB::table('venues')
            ->where('venue_id', $venueId)
            ->value('team_id');

        // Cek apakah user memiliki akses ke venue ini
        if (!$venueTeamId || !in_array($venueTeamId, $userTeamIds)) {
            // Add logging to help debug
            Log::error('Venue access denied', [
                'id' => $userId,
                'venue_id' => $venueId,
                'user_team_ids' => $userTeamIds,
                'venue_team_id' => $venueTeamId
            ]);

            abort(403, 'Unauthorized Access to Venue');
        }

        return $next($request);
    }
}
