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
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
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

            $event = \App\Models\Event::where('slug', $request->client)->first();

            // queque dulu
            $path = storage_path("sql/events/{$event->id}.db");

            if (!File::exists($path)) {
                return response()->json(['error' => 'SQL file not found'], 404);
            }

            $sql = File::get($path);
            $startLogin = Carbon::now()->format('Y-m-d H:i:s');

            $appendSql = "\n-- Login Queue\n";
            $appendSql .= "INSERT INTO event_logs (user_id, event_id, start_login, end_at) VALUES (\n";
            $appendSql .= "  '{$user->id}',\n";
            $appendSql .= "  '{$event->id}',\n";
            $appendSql .= "  '{$startLogin}',\n";
            $appendSql .= "  NULL\n";
            $appendSql .= ");\n";

            File::put($path, $sql . $appendSql);

            if ($event) {
                $trafficNumber = \App\Models\TrafficNumbersSlug::where('event_id', $event->id)->first();
                $trafficNumber->increment('active_sessions');
                $trafficNumber->save();
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
        $host = $request->getHost();
        $subdomain = explode('.', $host)[0];

        $event = Event::where('slug', $subdomain)->first();

        if ($event) {
            $user = Auth::user();
            if ($user) {
                $path = storage_path("sql/events/{$event->id}.sql");

                // dd($path);
    
                if (File::exists($path)) {
                    $sql = File::get($path);
                    $endLogin = Carbon::now()->format('Y-m-d H:i:s');
    
                    // Tambahkan SQL update
                    $appendSql = "\n-- Logout Queue\n";
                    $appendSql .= "UPDATE event_logs SET end_at = '{$endLogin}'\n";
                    $appendSql .= "WHERE user_id = '{$user->id}' AND event_id = '{$event->id}' AND end_at IS NULL;\n";
    
                    File::put($path, $sql . $appendSql);
                }
            }

            $trafficNumber = \App\Models\TrafficNumbersSlug::where('event_id', $event->id)->first();
            if ($trafficNumber && $trafficNumber->active_sessions > 0) {
                $trafficNumber->decrement('active_sessions');
            }
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
        // $topic = 'novatix/midtrans/' . $client_name . '/' . $mqtt_code . '/ticketpurchased';
        $topic = 'novatix/logs/defaultcode';

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
            // biarin lewat aja biar ga bikin masalah di payment controller flow nya
            Log::error('MQTT Publish Failed: ' . $th->getMessage());
        }
    }
}
