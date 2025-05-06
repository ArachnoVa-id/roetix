<?php

namespace App\Http\Controllers\Auth;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\Event;
use App\Models\EventVariables;
use App\Models\User;
use App\Models\Traffic;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;
use Carbon\Carbon;


class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function login(string $client = ''): Response
    {
        if ($client) {
            // Get the event and associated venue
            $event = Event::where('slug', $client)
                ->first();

            $props = $event->eventVariables;
            $props->reconstructImgLinks();
        } else {
            $event = [
                'name' => 'Admin NovaTix'
            ];
            $props = EventVariables::getDefaultValue();

            $props['logo'] = '/images/novatix-logo-white/android-chrome-512x512.png';
            $props['logo_alt'] = 'Novatix Logo';
            $props['texture'] = '/images/default-texture/Texturelabs_Sky_152S.jpg';
        }

        return Inertia::render('Auth/Login', [
            'canResetPassword' => Route::has('password.request'),
            'status' => session('status'),
            'event' => $event,
            'client' => $client,
            'props' => (is_array($props) ? $props : $props->getSecure()),
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
        $userModel = User::find($user->id);

        if ($request->client) {
            session([
                'auth_user' => $userModel,
            ]);

            Traffic::create([
                'user_id' => $user->id,
                'start_login' => Carbon::now()->format('H:i:s'),
                // 'end_login' => Carbon::now()->addHours(2)->format('H:i:s'), // contoh 2 jam sesi
                'end_login' => Carbon::now()->addMinutes(1)->format('H:i:s'),
                'stop_at' => null,
            ]);

            $event = \App\Models\Event::where('slug', $request->client)->first();

            if ($event) {
                $trafficNumber = \App\Models\TrafficNumbersSlug::firstOrCreate(
                    ['event_id' => $event->id],
                    ['active_sessions' => 0]
                );

                $trafficNumber->increment('active_sessions');
            }

            // redirecting to
            $redirectProps = [
                'route' => ($user ? 'client.home' : 'client.login'),
                'client' => $request->client,
            ];

            return redirect()->route($redirectProps['route'], $redirectProps['client']);
        }

        $firstTeam = $userModel->teams()->first();

        if ($userModel->isAdmin()) {
            session([
                'auth_user' => $userModel,
            ]);

            return Inertia::location(route('filament.novatix-admin.pages.dashboard'));
        } else if ($userModel->isUser()) {
            Auth::logout();
            abort(403, 'User role not allowed.');
        } else if (!$firstTeam) {
            Auth::logout();
            abort(404, 'No team found for user. Please contact admin.');
        }

        session([
            'auth_user' => $userModel,
        ]);

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
