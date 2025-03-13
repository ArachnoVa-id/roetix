<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\Event;
use App\Models\EventVariables;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(string $client = ''): Response
    {
        if ($client) {
            // Get the event and associated venue
            $event = Event::where('slug', $client)
                ->first();

            $props = EventVariables::findOrFail($event->event_variables_id);
        } else {
            $event = [
                'name' => 'Admin NovaTix'
            ];
            $props = [
                'logo' => '/images/novatix-logo/android-chrome-512x512.png',
                'alt' => 'Novatix Logo'
            ];
        }

        return Inertia::render('Auth/Login', [
            'canResetPassword' => Route::has('password.request'),
            'status' => session('status'),
            'event' => $event,
            'client' => $client,
            'props' => $props,
        ]);
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): SymfonyResponse
    {
        $request->authenticate();
        $request->session()->regenerate();

        $user = Auth::user();

        if ($request->client && $user) {
            return redirect()->intended(
                $request->client
                    ? route('client.home', ['client' => $request->client], false)
                    : route('client.login', ['client' => $request->client], false)
            );
        }

        $userModel = User::find($user->user_id);
        $firstTeam = $userModel->teams()->first();

        if ($userModel->role === 'admin') {
            return Inertia::location(route('filament.novatix-admin.pages.dashboard'));
        }

        return Inertia::location(route('filament.admin.pages.dashboard', ['tenant' => $firstTeam->code]));
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/login');
    }
}
