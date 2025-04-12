<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
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
                    'props' => $props
                ]);
            }
        }

        return $next($request);
    }
}
