<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use App\Models\Traffic;
use App\Models\Event;
use App\Models\TrafficNumbersSlug;

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
            dd("kuota lebih dari satu", $trafficNumber->active_sessions);
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
