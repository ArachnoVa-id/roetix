<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class DynamicSessionCookie
{
    public function handle(Request $request, Closure $next)
    {
        $host = $request->getHost();

        // Check if we are in a subdomain
        $isInSubdomain = count(explode('.', $host)) > 2;

        // Ensure admin panel keeps a stable session
        if (!$isInSubdomain || str_ends_with($host, env('APP_DOMAIN', 'localhost'))) {
            return $next($request);
        }

        // Set session domain for clients
        $baseDomain = env('APP_DOMAIN', 'localhost');
        config([
            'session.cookie' => 'session_shared',
            'session.domain' => ".$baseDomain",
        ]);

        return $next($request);
    }
}
