<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Cookie;

class DynamicSessionCookie
{
    public function handle(Request $request, Closure $next)
    {
        $host = $request->getHost();
        $parts = explode('.', $host);
        $isInSubdomain = count($parts) > 2;

        // Define different session cookie names
        $appName = config('app.name');
        $sessionCookieName = strtolower($appName) . ($isInSubdomain ? '_client_session' : '_session');

        // Remove conflicting session cookies
        Cookie::queue(Cookie::forget($isInSubdomain ? $sessionCookieName . '_admin' : $sessionCookieName . '_client'));

        // Dynamically set the session domain
        config([
            'session.cookie' => $sessionCookieName,
            'session.domain' => '.' . config('app.domain'),
        ]);

        return $next($request);
    }
}
