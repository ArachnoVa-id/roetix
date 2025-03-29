<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Inertia\Inertia;

class LoadUserContactInfo
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next)
    {
        if ($request->user()) {
            // Load the contactInfo relationship eagerly
            $user = $request->user()->load('contactInfo');

            // Share the user data with Inertia globally
            Inertia::share('auth.user', $user);
        }

        return $next($request);
    }
}
