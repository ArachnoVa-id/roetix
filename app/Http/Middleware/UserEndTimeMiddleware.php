<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use App\Models\Event;
use PDO;
use PhpMqtt\Client\MqttClient;
use PhpMqtt\Client\ConnectionSettings;
use Illuminate\Support\Facades\Log;

class UserEndTimeMiddleware
{
    public function handle(Request $request, Closure $next)
    {

        $user = Auth::user();

        if (!$user) {
            abort(404, 'Sesi User tidak ditemukan.');
        }

        $subdomain = $request->route('client');
        $event = Event::where('slug', $subdomain)->first();

        if (!$event) {
            abort(404, 'Event tidak ditemukan.');
        }

        $event_id = $event->id;
        $path = storage_path("sql/events/{$event_id}.db");

        $pdo = new PDO("sqlite:" . $path);
        $stmt = $pdo->query("
            SELECT * FROM user_logs
            WHERE user_id = ?
            AND status = 'online'
            ORDER BY created_at DESC
            LIMIT 1
        ");

        $stmt->execute([$user->id]);
        $user_log = $stmt->fetch(PDO::FETCH_ASSOC);

        // dd($user_log, $user->id);

        if ($user_log) {
            $current_time = Carbon::now();

            // Check if user is not in page or close the web or other reaseon  [tolerance 3 minutes]
            $tolerance = Carbon::parse($user_log['expected_end_time'])->addMinutes(3);
            if ($current_time->greaterThanOrEqualTo($tolerance)) {

                // dd($user_log);
                // Update end_login untuk login terakhir user
                $stmt = $pdo->prepare("DELETE FROM user_logs WHERE user_id = ? AND status = 'online'");
                $stmt->execute([$user->id]);

                Auth::guard('web')->logout();

                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return redirect('/login');
            }
        }

        if ($user_log) {
            $current_time = Carbon::now();

            // Check if user is not in page or close the web or other reaseon  [tolerance 3 minutes]
            $tolerance = Carbon::parse($user_log['expected_end_time'])->addMinutes(3);
            if ($current_time->greaterThanOrEqualTo($tolerance)) {

                // Auth::guard('web')->logout();

                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return redirect('/login');
            }
        }

        // dd($user_log);

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
        $topic = 'novatix/midtrans/defaultcode/';

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
            dd($th);
            Log::error('MQTT Publish Failed: ' . $th->getMessage());
        }
    }
}
