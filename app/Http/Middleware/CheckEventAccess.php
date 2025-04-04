<?php

namespace App\Http\Middleware;

use App\Enums\UserRole;
use Closure;
use App\Models\User;
use App\Models\Event;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

class CheckEventAccess
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next)
    {
        $user = session('auth_user');

        if ($user->isAdmin()) return $next($request);

        $userId = $user->id;

        // Check both route parameter and query parameter for event_id
        $eventId = $request->route('event_id') ?? $request->query('event_id');

        if (!$eventId) {
            abort(400, 'Missing event_id');
        }

        $userTeamIds = User::find($userId)
            ->teams()
            ->pluck('teams.team_id')
            ->toArray();

        $eventTeamId = Event::where('event_id', $eventId)
            ->value('team_id');

        if (!$eventTeamId || !in_array($eventTeamId, $userTeamIds)) {
            abort(403, 'Unauthorized Access to Event');
        }

        return $next($request);
    }
}
