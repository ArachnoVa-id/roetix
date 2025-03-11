<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

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
        $venueId = $request->query('venue_id'); // Ambil venue_id dari query string
        
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
        if (!in_array($venueTeamId, $userTeamIds)) {
            abort(403, 'Unauthorized Access');
        }

        return $next($request);
    }
}
