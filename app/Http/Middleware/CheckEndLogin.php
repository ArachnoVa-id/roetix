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

        $traffic = Traffic::where('user_id', $user->id)
            ->whereNull('stop_at')
            ->latest()
            ->first();

        $trafficNumber = TrafficNumbersSlug::where('event_id', $event_id)->first();

        if ($trafficNumber->active_sessions >= 2) {
            $client = $request->route('client');
            $props = $request->get('props');

            // Check if the event is in overload mode
            return Inertia::render('User/Overload', [
                'client' => $client,
                'event' => [
                    'name' => $event->name,
                    'slug' => $event->slug
                ],
                'maintenance' => [
                    'title' => 'Overload user ' . $trafficNumber->active_sessions,
                    'message' => 'Try again latter',
                    'expected_finish' => Carbon::now()->addMinutes(1)->format('H:i:s'),

                ],
            ]);
        }

        // dd("kuota belom lewat batas satu", $trafficNumber->active_sessions);

        // Jika sudah lewat end_login, update stop_at dan logout
        if ($traffic && Carbon::now()->gte(Carbon::parse($traffic->end_login))) {
            $traffic->stop_at = Carbon::now();
            $traffic->save();

            return redirect()->route('logout')->withErrors(['expired' => 'Sesi Anda telah berakhir.']);
        }

        return $next($request);
    }
}
