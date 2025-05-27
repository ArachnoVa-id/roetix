<?php

namespace App\Http\Middleware;

use App\Models\User;
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

        // $client = User::find($client->id);
        // if (!$client) {
        //     return Inertia::render('Error/NotFound', [
        //         'message' => 'Client not found.'
        //     ]);
        // }

        // Bypass for event organizers and admins
        if ($client->isEo() || $client->isAdmin()) {
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
