<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;

class DynamicSessionCookie
{
    public function handle(Request $request, Closure $next)
    {
        $host = str_replace('.', '_', $request->getHost());
        $subdomain = explode('.', $host)[0];

        $request->attributes->set('subdomain', $subdomain);
        config(['app.subdomain' => $subdomain]);

        return $next($request);
    }
}
