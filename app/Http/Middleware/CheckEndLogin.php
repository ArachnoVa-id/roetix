<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class CheckEndLogin
{
    public function handle(Request $request, Closure $next)
    {
        $user = Auth::user();

        if (!$user) {
            return $next($request);
        }

        $traffic = \App\Models\Traffic::where('user_id', $user->id)
            ->whereNull('stop_at')
            ->latest()
            ->first();

        // dd($traffic);

        if ($traffic && Carbon::now()->gte(Carbon::parse($traffic->end_login))) {
            // Update stop_at saat logout
            $traffic->stop_at = Carbon::now();
            $traffic->save();

            // Hapus semua sesi dan logout
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
            session()->flush();

            return redirect()->route('login')->withErrors(['expired' => 'Sesi Anda telah berakhir.']);
        }

        return $next($request);
    }
}
