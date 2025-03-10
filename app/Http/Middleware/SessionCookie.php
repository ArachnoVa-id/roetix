<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Cookie;

class SessionCookie
{
    public function handle(Request $request, Closure $next)
    {
        // Define different session cookie names
        $appName = config('app.name');
        $sessionCookieName = strtolower($appName) . '_session';

        // Dynamically set the session domain
        config([
            'session.cookie' => $sessionCookieName,
            'session.domain' => '.' . config('app.domain'),
        ]);

        return $next($request);
    }
}
