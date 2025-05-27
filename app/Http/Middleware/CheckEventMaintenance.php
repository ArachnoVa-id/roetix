<?php

namespace App\Http\Middleware;

use App\Models\User;
use Carbon\Carbon;
use Closure;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CheckEventMaintenance
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next)
    {
        $client = $request->route('client');
        $event = $request->get('event');
        $props = $request->get('props');

        $client = User::find($client->id);
        if (!$client) {
            return Inertia::render('Error/NotFound', [
                'message' => 'Client not found.'
            ]);
        }

        // Bypass for eo and admin
        if ($client->isEO() || $client->isAdmin()) {
            return $next($request);
        }

        // Check if the event is in maintenance mode
        if ($props->is_maintenance) {
            return Inertia::render('User/Maintenance', [
                'client' => $client,
                'event' => [
                    'name' => $event->name,
                    'slug' => $event->slug
                ],
                'maintenance' => [
                    'title' => $props->maintenance_title ?: 'Site Under Maintenance',
                    'message' => $props->maintenance_message ?: 'We are currently performing maintenance on our system. Please check back later.',
                    'expected_finish' => $props->maintenance_expected_finish ? Carbon::parse($props->maintenance_expected_finish)->format('F j, Y, g:i a') : null,
                ],
                'props' => $props->getSecure()
            ]);
        }

        return $next($request);
    }
}
