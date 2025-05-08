<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use App\Models\Event;
use Inertia\Inertia;
use PDO;
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

        $subdomain = $request->route('client');
        $event = Event::where('slug', $subdomain)->first();

        if (!$event) {
            abort(404, 'Event tidak ditemukan.');
        }

        $event_id = $event->id;
        $path = storage_path("sql/events/{$event_id}.db");
        $pdo = new PDO("sqlite:" . $path);

        // Hitung jumlah user online
        $stmt = $pdo->query("SELECT COUNT(*) FROM user_logs WHERE status = 'online'");
        $trafficNumber = $stmt->fetchColumn();

        $stmt = $pdo->prepare("SELECT * FROM user_logs WHERE user_id = ?");
        $stmt->execute([$user->id]);
        $current_user = $stmt->fetch(PDO::FETCH_ASSOC);

        // dd($current_user, $user->id, $trafficNumber);

        // jika trafic number lebih dari treshold =>
        if ($trafficNumber >= 1) {

            // user sudah tidak online
            if (!$current_user) {
                Auth::guard('web')->logout();

                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return redirect('/login');
            }
            
            // user sekarang waiting ? ttp waiting
            if ($current_user['status'] == 'waiting') {

                // check apakah ada user yang kemungkinan pending di web (udah habis sesinya tapi ga reload page)
                $current_time = Carbon::now();

                $stmt = $pdo->prepare("
                    SELECT * FROM user_logs 
                    WHERE status = 'online' AND expected_end_time < ? 
                    ORDER BY created_at ASC 
                    LIMIT 1
                ");
                $stmt->execute([$current_time->toDateTimeString()]);

                $offline_suspected_user = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($offline_suspected_user) {
                    // hapus sesi offline_suspected_user
                    $stmt = $pdo->prepare("DELETE FROM user_logs WHERE user_id = ?");
                    $stmt->execute([$offline_suspected_user['user_id']]);

                    // publish mqtt
                    $mqttData = [
                        'event' => 'user_logout',
                        'next_user_id' => $user->id,
                    ];

                    $this->publishMqtt($mqttData, $event->slug);
                }


                // hapus sesinya

                return Inertia::render('User/Overload', [
                    'client' => $subdomain,
                    'event' => [
                        'name' => $event->name,
                        'slug' => $event->slug,
                        'event_slug' => $event->slug,
                        'user_id' => $current_user['user_id'],
                    ],
                    'maintenance' => [
                        'title' => "Overload User Capacity",
                        'message' => 'Please wait...',
                        'expected_finish' => Carbon::now()->addMinutes(1)->toIso8601String(),
                        // 'expected_finish' => Carbon::now()->addMinutes(1)->format('H:i:s'),
                    ],
                ]);
            }

            // user sekarang online =>
            if ($current_user['status'] == 'online') {
                $current_time = Carbon::now();
                $tolerance = Carbon::parse($current_user['expected_end_time'])->addMinutes(1);

                // ebih dari expected_end_time + 1 menit (tolarance) => logout
                if ($current_time->greaterThanOrEqualTo($tolerance)) {
                    $stmt = $pdo->prepare("DELETE FROM user_logs WHERE user_id = ?");
                    $stmt->execute([$user->id]);
                    Auth::guard('web')->logout();

                    $request->session()->invalidate();
                    $request->session()->regenerateToken();

                    return redirect('/login');
                } else {
                    return $next($request);
                }
            }
        }

        // user sekarang waiting => traffic number lebih dari treshold
        $start = Carbon::now();
        $end = Carbon::now()->addMinutes(1);
        $pdo = new PDO("sqlite:" . $path);
        $stmt = $pdo->prepare("
            UPDATE user_logs
            SET status = 'online', start_time = ?, expected_end_time = ?
            WHERE user_id = ?
        ");
        $stmt->execute([$start, $end, $user->id]);

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
            dd($th);
            Log::error('MQTT Publish Failed: ' . $th->getMessage());
        }
    }
}
