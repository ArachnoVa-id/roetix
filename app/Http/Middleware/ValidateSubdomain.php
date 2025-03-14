<?php

namespace App\Http\Middleware;

use App\Models\Event;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class ValidateSubdomain
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // NOTE: this only occurs when there exist the subdomain, because the main root is redirected to another route
        $client = request()->getHost();
        // cut the subdomain
        $client = explode('.', $client)[0];

        if (!$client || !Event::where('slug', $client)->exists()) {
            abort(404);
        }

        if (!Auth::check()) {
            if ($request->route()->getName() !== 'client.login')
                return redirect()->route('client.login', ['client' => $client]);
            return $next($request);
        } else if ($request->route()->getName() === 'client.login') {
            return redirect()->route('client.home', ['client' => $client]);
        }

        return $next($request);
    }
}
