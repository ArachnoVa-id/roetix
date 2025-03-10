<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ValidateMainDomain
{
    public function handle(Request $request, Closure $next)
    {
        $mainDomain = config('app.main_domain');
        $currentDomain = $request->getHost();

        // Ensure subdomains are blocked from the main domain
        if ($currentDomain !== $mainDomain) {
            abort(403, 'Access denied');
        }

        // Check if user is authenticated before accessing properties
        if (!Auth::check()) {
            return redirect()->route('client.login'); // Redirect to login if unauthenticated
        }

        $user = Auth::user(); // Now safe to access
        $user = \App\Models\User::find($user->user_id); // Ensure valid user

        return $next($request);
    }
}
