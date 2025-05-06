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

        $traffic = Traffic::where('user_id', $user->id)
            ->whereNull('stop_at')
            ->latest()
            ->first();

        $event = Event::where('slug', $subdomain)->first();

        if (!$event) {
            abort(404, 'Event tidak ditemukan.');
        }

        $event_id = $event->id;

        // Cek jumlah sesi aktif
        $trafficNumber = TrafficNumbersSlug::where('event_id', $event_id)->first();

        if ($trafficNumber && $trafficNumber->active_sessions > 1) {
            dd("kuota penuh", $trafficNumber->active_sessions);
        }

        // dd($trafficNumber->active_sessions);

        // Jika sudah lewat end_login, update stop_at dan logout
        if ($traffic && Carbon::now()->gte(Carbon::parse($traffic->end_login))) {
            $traffic->stop_at = Carbon::now();
            $traffic->save();

            // Kurangi jumlah active_sessions
            $event = \App\Models\Event::where('slug', $request->route('client'))->first();
            if ($event) {
                $trafficNumber = \App\Models\TrafficNumbersSlug::where('event_id', $event->id)->first();
                if ($trafficNumber && $trafficNumber->active_sessions > 0) {
                    $trafficNumber->decrement('active_sessions');
                }
            }

            // Hapus sesi dan logout
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
            session()->flush();

            return redirect()->route('login')->withErrors(['expired' => 'Sesi Anda telah berakhir.']);
        }

        return $next($request);
    }
}
