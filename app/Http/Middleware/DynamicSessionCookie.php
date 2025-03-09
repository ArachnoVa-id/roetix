<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Str;

class DynamicSessionCookie
{
    public function handle(Request $request, Closure $next)
    {
        $host = $request->getHost();

        // Check if we are in a subdomain by checking the length of the host
        $isInSubdomain = count(explode('.', $host)) > 2;

        // Skip dynamic session handling for Filament (admin panel)
        if (!$isInSubdomain) {
            return $next($request);
        }

        config(['session.cookie' => 'session_user']);

        // Continue with tenant-based session logic
        // $subdomain = explode('.', $host)[0];
        // config(['session.cookie' => 'session_' . $subdomain]);

        return $next($request);
    }
}
