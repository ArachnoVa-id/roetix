<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use App\Models\Traffic;
use App\Models\Event;
use App\Models\TrafficNumbersSlug;
use Inertia\Inertia;
use Illuminate\Support\Facades\File;

class CheckEndLogin
{
    public function handle(Request $request, Closure $next)
    {
        $user = Auth::user();

        if (!$user) {
            return $next($request);
        }

        $subdomain = $request->route('client');
        $event = Event::where('slug', $subdomain)->first();

        if (!$event) {
            abort(404, 'Event tidak ditemukan.');
        }

        $event_id = $event->id;
        $trafficNumber = TrafficNumbersSlug::where('event_id', $event_id)->first();

        if ($trafficNumber->active_sessions >= 2) {
            return Inertia::render('User/Overload', [
                'client' => $subdomain,
                'event' => [
                    'name' => $event->name,
                    'slug' => $event->slug
                ],
                'maintenance' => [
                    'title' => 'Total Number of user ' . $trafficNumber->active_sessions,
                    'message' => 'Try again latter',
                    'expected_finish' => Carbon::now()->addMinutes(1)->format('H:i:s'),
                ],
            ]);
        }

        return $next($request);
    }
}
