<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Event;
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

        return $next($request);
    }
}
