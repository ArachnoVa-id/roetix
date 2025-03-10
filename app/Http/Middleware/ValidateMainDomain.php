<?php
// app/Http/Middleware/ValidateMainDomain.php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ValidateMainDomain
{
    public function handle(Request $request, Closure $next): Response
    {
        $mainDomain = config('app.domain'); // dev-staging-novatix.id
        $currentDomain = $request->getHost(); // Get the current request domain

        if ($currentDomain !== $mainDomain) {
            abort(403, 'Access denied'); // Block subdomains from accessing admin
        }

        return $next($request);
    }
}
