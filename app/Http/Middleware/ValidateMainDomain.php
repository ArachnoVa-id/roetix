<?php

namespace App\Http\Middleware;

use App\Enums\UserRole;
use App\Models\User;
use Closure;
use Illuminate\Container\Attributes\Log;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Facade;
use Illuminate\Support\Facades\Log as FacadesLog;

class ValidateMainDomain
{
    public function handle(Request $request, Closure $next)
    {
        $mainDomain = config('app.domain');
        $currentDomain = $request->getHost();

        // Pastikan auth.google dan auth.google-authentication bisa diakses
        if (in_array($request->route()->getName(), ['auth.google', 'auth.google-authentication', 'privacy_policy', 'terms_conditions'])) {
            return $next($request);
        }

        // Ensure subdomains are blocked from the main domain
        if ($currentDomain !== $mainDomain) {
            return redirect()->route('client.home');
        }

        // Check if user is authenticated before accessing properties
        $user = Auth::user();

        if (!$user) {
            if ($request->route()->getName() !== 'login') {
                return redirect()->route('login');
            }
            return $next($request);
        }

        $user = User::find($user->id);

        if ($user->role === UserRole::USER->value) {
            Auth::logout();
            return redirect()->route('login');
        }

        $firstTeam = optional($user->teams()->first());
        if (!$firstTeam) {
            Auth::logout();
            return redirect()->route('login');
        }

        if ($request->route()->getName() === 'login') {
            if ($user->role == UserRole::ADMIN->value) {
                return redirect()->route('filament.novatix-admin.pages.dashboard');
            }
            return redirect()->route('filament.admin.pages.dashboard', ['tenant' => $firstTeam->code]);
        }

        return $next($request);
    }
}
