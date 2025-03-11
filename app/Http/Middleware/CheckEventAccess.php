<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CheckEventAccess
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next)
    {
        $userId = Auth::id();

        // Check both route parameter and query parameter for event_id
        $eventId = $request->route('event_id') ?? $request->query('event_id');

        if (!$eventId) {
            abort(400, 'Missing event_id');
        }

        $userTeamIds = DB::table('user_team')
            ->where('user_id', $userId)
            ->pluck('team_id')
            ->toArray();

        $eventTeamId = DB::table('events')
            ->where('event_id', $eventId)
            ->value('team_id');

        if (!$eventTeamId || !in_array($eventTeamId, $userTeamIds)) {
            // Add logging to help debug
            Log::error('Event access denied', [
                'user_id' => $userId,
                'event_id' => $eventId,
                'user_team_ids' => $userTeamIds,
                'event_team_id' => $eventTeamId
            ]);

            abort(403, 'Unauthorized Access to Event');
        }

        return $next($request);
    }
}
