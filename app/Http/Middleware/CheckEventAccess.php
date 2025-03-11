<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

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
        $eventId = $request->route('event_id');
        $userTeamIds = DB::table('user_team')
            ->where('user_id', $userId)
            ->pluck('team_id')
            ->toArray();

        $eventTeamId = DB::table('events')
            ->where('event_id', $eventId)
            ->value('team_id');

        if (!in_array($eventTeamId, $userTeamIds)) {
            abort(403, 'Unauthorized Access');
        }

        return $next($request);
    }
}
