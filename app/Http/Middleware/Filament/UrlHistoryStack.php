<?php

namespace App\Http\Middleware\Filament;

use Closure;
use Illuminate\Http\Request;

class UrlHistoryStack
{
    public function handle(Request $request, Closure $next)
    {
        $urlStack = session()->get('url_stack', []);
        $currentUrl = $request->url();

        // Prevent duplicate entries if refreshing the page or navigating to the same URL
        if (empty($urlStack) || end($urlStack) !== $currentUrl) {
            $urlStack[] = $currentUrl;
        }

        // Save the updated stack to the session
        session(['url_stack' => $urlStack]);

        return $next($request);
    }
}
