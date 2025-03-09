<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

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

        // Set session domain to share sessions across all subdomains
        $baseDomain = preg_replace('/^(.+?)\./', '', $host); // Extract main domain
        config([
            'session.cookie' => 'session_shared',
            'session.domain' => ".$baseDomain", // Ensure it works across subdomains
        ]);

        // Continue with tenant-based session logic
        // $subdomain = explode('.', $host)[0];
        // config(['session.cookie' => 'session_' . $subdomain]);

        return $next($request);
    }
}
