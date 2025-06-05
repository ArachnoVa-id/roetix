<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class CheckEventLock
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next)
    {
        $client = $request->route('client');
        $event = $request->get('event');
        $props = $request->get('props');

        // Bypass for event organizers (EO) and admins
        $user = Auth::user();

        if (!$user) {
            return redirect()->route('login');
        }

        $user = User::find($user->id);

        if ($user->isEo() || $user->isAdmin()) {
            return $next($request);
        }

        // Check if the event is locked
        $isAuthenticated = false;

        if ($props->is_locked) {
            $isAuthenticated = $request->session()->get("event_auth_{$event->id}", false);

            // If not authenticated, show the lock screen
            if (!$isAuthenticated) {
                return Inertia::render('User/LockedEvent', [
                    'client' => $client,
                    'event' => [
                        'name' => $event->name,
                        'slug' => $event->slug
                    ],
                    'props' => $props->getSecure()
                ]);
            }
        }

        return $next($request);
    }
}
