<?php

namespace App\Http\Middleware;

use App\Models\Event;
use App\Models\User;
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
        // Pastikan auth.google dan auth.google-authentication bisa diakses
        if (in_array($request->route()->getName(), ['client-auth.google', 'client-auth.google-authentication', 'client.privacy_policy', 'client.terms_conditions'])) {
            return $next($request);
        }

        // get host
        $host = $request->getHost();

        // check host length divided by .
        $exploded = explode('.', $host);
        if (count($exploded) < 2) {
            abort(404);
        }

        // get subdomain
        $client = $exploded[0];

        $event = Event::where('slug', $client)->first();

        if (!$event) {
            abort(404, 'Event not found! Please contact admin.', ['isRedirecting' => 'false']);
        }

        $url = $request->route()->getName();
        if (!Auth::check()) {
            if ($url !== 'client.login' && $url !== 'client.privateLogin') {
                return redirect()->route('client.login', ['client' => $event->slug]);
            }
        } else if ($url === 'client.login' || $url === 'client.privateLogin') {
            return redirect()->route('client.home', ['client' => $event->slug]);
        }

        // for receptionist, reject effort to go to buy and check tickets
        $user = User::find(Auth::id());
        if ($user && ($user->isReceptionist())) {
            if ($url === 'client.home' || $url === 'client.my_tickets') {
                return redirect()->route('client.scan', ['client' => $event->slug]);
            }
        }

        return $next($request);
    }
}
