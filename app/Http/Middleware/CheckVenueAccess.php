<?php

namespace App\Http\Middleware;

use Closure;
use App\Models\User;
use App\Models\Venue;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CheckVenueAccess
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next)
    {
        $user = session('userProps');
        if ($user->isAdmin()) return $next($request);

        $userId = $user->id;

        // Check both route parameter and query parameter for venue_id
        $venueId = $request->route('venue_id') ?? $request->query('venue_id');

        if (!$venueId) {
            abort(400, 'Missing venue_id');
        }

        // Ambil semua team_id milik user yang login
        $userTeamIds = User::find($userId)
            ->teams()
            ->pluck('teams.team_id')
            ->toArray();

        // Ambil team_id dari venue
        $venueTeamId = Venue::where('venue_id', $venueId)
            ->value('team_id');

        // Cek apakah user memiliki akses ke venue ini
        if (!$venueTeamId || !in_array($venueTeamId, $userTeamIds)) {
            abort(403, 'Unauthorized Access to Venue');
        }

        return $next($request);
    }
}
