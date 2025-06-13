<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use App\Models\Event;
use App\Models\User;
use Exception;
use Inertia\Inertia;
use PhpMqtt\Client\MqttClient;
use PhpMqtt\Client\ConnectionSettings;
use Illuminate\Support\Facades\Log;

class UserQueueMiddleware
{
    // Helper method untuk format event data yang konsisten
    private function formatEventData($event)
    {
        return [
            'id' => $event->id,
            'event_id' => $event->event_id ?? $event->id,
            'name' => $event->name,
            'slug' => $event->slug,
            'location' => $event->location,
            'date' => $event->date,
            'event_date' => $event->event_date ?? $event->date,
            'venue_id' => $event->venue_id,
            'status' => $event->status,
            'description' => $event->description ?? '',
            'event_variables_id' => $event->event_variables_id,
        ];
    }

    public function handle(Request $request, Closure $next)
    {
        $props = $request->get('props');

        $user = Auth::user();

        if (!$user) {
            abort(404, 'Sesi User tidak ditemukan.');
        }

        $client = $request->route('client');
        $event = Event::where('slug', $client)->first();

        if (!$event) {
            abort(404, 'Event tidak ditemukan.');
        }

        // avoid user to access inactive user

        $eventVariables = $event->eventVariables;

        if (!$eventVariables) {
            abort(404, 'Event Variables tidak ditemukan.');
        }

        // if ($eventVariables['is_maintenance'] == 1 || $eventVariables['is_locked'] == 1) {
        //     return Inertia::render('User/LockedEvent', [
        //         'client' => $client,
        //         'event' => $this->formatEventData($event),
        //         'props' => $props->getSecure(),
        //     ])->with([
        //         'errors' => ['event_password' => 'The password you entered is incorrect.']
        //     ]);
        // }

        if ($event['status'] != 'active'){
            return $next($request);
        }


        // If user is admin, bypass
        $userData = User::find($user->id);
        if ($userData->isAdmin() || $userData->isReceptionist()) {
            return $next($request);
        }

        $threshold = $eventVariables->active_users_threshold;
        $loginDuration = $eventVariables->active_users_duration;

        // ✅ Validate threshold once
        if (!is_numeric($threshold) || $threshold < 1) {
            throw new \Exception("Invalid active_users_threshold value.");
        }

        // ✅ Always work with latest data
        $current_user = (object) Event::getUser($event, $user);

        // ✅ Ensure valid user
        if (!isset($current_user->id)) {
            Auth::logout();
            return redirect()->route('client.login', ['client' => $client]);
        }

        // ✅ If online, check session timeout
        if ($current_user->status === 'online') {
            $now = Carbon::now();
            $expectedEnd = Carbon::parse($current_user->expected_kick);

            if ($now->gte($expectedEnd)) {
                Event::logoutUser($event, $user, $this);
                Auth::logout();

                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return redirect()->route('client.login', ['client' => $client]);
            }

            return $next($request);
        }

        // ✅ If waiting, check if next in queue
        if ($current_user->status === 'waiting') {
            $position = Event::getUserPosition($event, $user);
            $onlineUsers = Event::countOnlineUsers($event);
            $supposedOnlineSlot = $threshold - $onlineUsers;

            // Estimate waiting time
            $expected_end = null;
            $buffer = 20;
            if ($position <= $supposedOnlineSlot) {
                // this means the user is supposed to be online, so try waiting by giving expected_end as 20 seconds
                $expected_end = Carbon::now()->addSeconds($buffer)->toDateTimeString();
            } else {
                $batch = ceil($position / $threshold);
                $totalMinutes = ($batch - 1) * $loginDuration + $loginDuration;
                $totalBuffer = $buffer * ($batch - 1);
                $expected_end = Carbon::now()->addMinutes($totalMinutes)->addSeconds($totalBuffer)->toDateTimeString();
            }

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

        // ✅ Unknown case fallback
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
