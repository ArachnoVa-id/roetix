<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ValidateMainDomain
{
    public function handle(Request $request, Closure $next)
    {
        $mainDomain = config('app.domain');
        $currentDomain = $request->getHost();

        // Ensure subdomains are blocked from the main domain
        if ($currentDomain !== $mainDomain) {
            abort(403, 'Access denied');
        }

        // Check if user is authenticated before accessing properties
        $user = Auth::user();

        if (!$user) {
            if ($request->route()->getName() !== 'login') return redirect()->route('login');
            return $next($request);
        }

        $user = User::find($user->user_id);

        if ($user->role === 'user') {
            Auth::logout();
            abort(403, 'Forbidden Account');
        }

        $firstTeam = optional($user->teams()->first());
        if (!$firstTeam) {
            Auth::logout();
            return redirect()->route('login');
        }

        if ($request->route()->getName() === 'login') {
            if ($user->role == 'admin') {
                return redirect()->route('filament.novatix-admin.pages.dashboard');
            }
            return redirect()->route('filament.admin.pages.dashboard', ['tenant' => $firstTeam->code]);
        }

        return $next($request);
    }
}
