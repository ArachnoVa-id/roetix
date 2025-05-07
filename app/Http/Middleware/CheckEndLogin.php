<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use App\Models\Traffic;
use App\Models\Event;
use App\Models\TrafficNumbersSlug;
use Inertia\Inertia;
use Illuminate\Support\Facades\File;
use PDO;

class CheckEndLogin
{
    public function handle(Request $request, Closure $next)
    {
        $user = Auth::user();

        if (!$user) {
            return $next($request);
        }

        $subdomain = $request->route('client');
        $event = Event::where('slug', $subdomain)->first();

        if (!$event) {
            abort(404, 'Event tidak ditemukan.');
        }

        $event_id = $event->id;
        $path = storage_path("sql/events/{$event_id}.db");
        $pdo = new PDO("sqlite:" . $path);
        $stmt = $pdo->query("SELECT COUNT(*) FROM user_logs WHERE online = 1");
        $trafficNumber = $stmt->fetchColumn();

        $stmt = $pdo->query("
            SELECT * FROM user_logs
            WHERE online = 1
            ORDER BY created_at ASC
            LIMIT 1
        ");
        $firstOnlineUser = $stmt->fetch(PDO::FETCH_ASSOC);

        $nextUserIdInQueue = $firstOnlineUser['user_id'] ?? null;

        if ($nextUserIdInQueue != $user->id && $trafficNumber >= 2) {
            return Inertia::render('User/Overload', [
                'client' => $subdomain,
                'event' => [
                    'name' => $event->name,
                    'slug' => $event->slug,
                    'event_slug' => $event->slug,
                    'next_user_id' => $nextUserIdInQueue,
                ],
                'maintenance' => [
                    'title' => "Total user online: $trafficNumber",
                    'message' => 'Please wait...',
                    'expected_finish' => Carbon::now()->addSeconds(10)->format('H:i:s'),
                ],
            ]);
        }


        return $next($request);
    }
}
