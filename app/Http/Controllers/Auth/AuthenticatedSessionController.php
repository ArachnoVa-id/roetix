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
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;
use PhpMqtt\Client\MqttClient;
use PhpMqtt\Client\ConnectionSettings;
use Illuminate\Support\Facades\Log;

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
            'privateLogin' => false
        ]);
    }

    public function privateLogin(string $client = ''): Response
    {
        if ($client) {
            // Get the event and associated venue
            $event = Event::where('slug', $client)
                ->first();

            $props = $event->eventVariables;
            $props->reconstructImgLinks();
        } // else reject
        else {
            abort(403, 'Unauthorized action.');
        }

        return Inertia::render('Auth/Login', [
            'canResetPassword' => Route::has('password.request'),
            'status' => session('status'),
            'event' => $event,
            'client' => $client,
            'props' => (is_array($props) ? $props : $props->getSecure()),
            'privateLogin' => true
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

        // For subdomain login
        $client = $request->client;
        $event = Event::where('slug', $client)->first();
        if ($client) {
            try {
                Event::loginUser($event, $user);
            } catch (\Throwable $e) {
                return redirect()->route(($client ? 'client.login' : 'login'), ['client' => $client]);
            }

            // redirecting to
            $redirectProps = [
                'route' => ($user ? 'client.home' : 'client.login'),
                'client' => $request->client,
            ];

            return redirect()->route($redirectProps['route'], $redirectProps['client']);
        }

        // For main login
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
        $host = $request->getHost();
        $subdomain = explode('.', $host)[0];
        $user = Auth::user();

        $event = Event::where('slug', $subdomain)->first();

        if ($event) {
            Event::logoutUserAndPromoteNext($event, $user, $this);
        }

        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/login');
    }

    public function publishMqtt(array $data, string $mqtt_code = "defaultcode", string $client_name = "defaultclient")
    {
        $server = 'broker.emqx.io';
        $port = 1883;
        $clientId = 'novatix_midtrans' . rand(100, 999);
        $usrname = 'emqx';
        $password = 'public';
        $mqtt_version = MqttClient::MQTT_3_1_1;
        $sanitized_mqtt_code = str_replace('-', '', $mqtt_code);
        $topic = 'novatix/logs/' . $sanitized_mqtt_code;

        $conn_settings = (new ConnectionSettings)
            ->setUsername($usrname)
            ->setPassword($password)
            ->setLastWillMessage('client disconnected')
            ->setLastWillTopic('emqx/last-will')
            ->setLastWillQualityOfService(1);

        $mqtt = new MqttClient($server, $port, $clientId, $mqtt_version);

        try {
            $mqtt->connect($conn_settings, true);
            $mqtt->publish(
                $topic,
                json_encode($data),
                0
            );
            $mqtt->disconnect();
        } catch (\Throwable $th) {
            Log::error('MQTT Publish Failed: ' . $th->getMessage());
        }
    }
}
