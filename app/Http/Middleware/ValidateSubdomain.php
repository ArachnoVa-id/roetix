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
        // get host
        $host = $request->getHost();

        // check host length divided by .
        if (count(explode('.', $host)) < 2) {
            abort(404);
        }

        // get subdomain
        $client = explode('.', $host)[0];

        $event = Event::where('slug', $client)->firstOrFail();

        if (!$event) {
            abort(404);
        }

        if (!Auth::check()) {
            if ($request->route()->getName() !== 'client.login') {
                return redirect()->route('client.login', ['client' => $event->slug]);
            }
        } else if ($request->route()->getName() === 'client.login') {
            return redirect()->route('client.home', ['client' => $event->slug]);
        }

        return $next($request);
    }
}
