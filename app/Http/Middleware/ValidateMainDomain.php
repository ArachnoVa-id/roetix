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
        if (!Auth::check()) {
            return redirect()->route('login'); // Redirect to login if unauthenticated
        }

        $user = Auth::user();
        $user = User::find($user->user_id);

        if ($user->role === 'user') {
            Auth::logout();
            return redirect()->route('login');
        }

        $firstTeam = optional($user->teams()->first())->name;
        if (!$firstTeam) {
            Auth::logout();
            return redirect()->route('login');
        }

        return $next($request);
    }
}
