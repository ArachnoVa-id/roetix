<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use App\Models\Event;
use Exception;
use Inertia\Inertia;
use PhpMqtt\Client\MqttClient;
use PhpMqtt\Client\ConnectionSettings;
use Illuminate\Support\Facades\Log;

class UserQueueMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $user = Auth::user();

        if (!$user) {
            abort(404, 'Sesi User tidak ditemukan.');
        }

        $client = $request->route('client');
        $event = Event::where('slug', $client)->first();

        if (!$event) {
            abort(404, 'Event tidak ditemukan.');
        }

        $threshold = 1;
        $loginDuration = 5;

        $trafficNumber = Event::countOnlineUsers($event);
        $current_user = Event::getUser($event, $user);

        // user sudah tidak online
        if (!$current_user) {
            Auth::guard('web')->logout();

            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('client.login', ['client' => $client]);
        }

        if ($current_user->status == 'online') {
            $current_time = Carbon::now();
            $expected_end_time = Carbon::parse($current_user->expected_end_time);

            if ($current_time->greaterThanOrEqualTo($expected_end_time)) {
                Event::logoutUserAndPromoteNext($event, $user, $this);
                Auth::guard('web')->logout();

                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return redirect()->route('client.login', ['client' => $client]);
            } else {
                return $next($request);
            }
        }

        if ($trafficNumber >= $threshold) {
            // user sekarang waiting ? ttp waiting
            if ($current_user->status == 'waiting') {

                // check apakah ada user yang kemungkinan pending di web (udah habis sesinya tapi ga reload page)
                $current_time = Carbon::now();

                // Validate the limit (ensure it's a positive integer)
                if (!filter_var($threshold, FILTER_VALIDATE_INT, ["options" => ["min_range" => 1]])) {
                    throw new Exception("Invalid limit value.");
                }

                Event::removeExpiredUsers($event, $this, $threshold);

                // Get the user's position in the queue
                $position = Event::getUserPosition($event, $user);

                // Calculate total minutes by max divide of traffic number to the position + 1 time traffic number (to predict the worst case of waiting online users)
                $batch = ceil($position / $threshold);
                $totalMinutes = ($batch - 1) * $loginDuration + $loginDuration;
                $expected_end = Carbon::now()->addMinutes($totalMinutes)->toDateTimeString();

                // hapus sesinya
                return Inertia::render('User/Overload', [
                    'client' => $client,
                    'event' => [
                        'name' => $event->name,
                        'slug' => $event->slug,
                        'user_id' => $current_user->user_id,
                    ],
                    'queue' => [
                        'title' => "You are in a queue number " . $position,
                        'message' => 'Please wait...',
                        'expected_finish' => $expected_end,
                    ],
                ]);
            }
        }

        // user sekarang waiting => traffic number lebih dari treshold
        Event::promoteUser($event, $user, $loginDuration);

        return $next($request);
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
