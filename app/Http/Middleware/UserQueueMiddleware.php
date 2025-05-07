<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use App\Models\Event;
use Inertia\Inertia;
use PDO;

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
            // user sekarang waiting ? ttp waiting
            if ($current_user['status'] == 'waiting') {
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
                        'expected_finish' => Carbon::now()->addMinutes(1)->format('H:i:s'),
                    ],
                ]);
            }

            // user sekarang online =>
            if ($current_user['status'] == 'online') {
                // ebih dari expected_end_time + 1 menit (tolarance) => logout
                $current_time = Carbon::now();
                $tolerance = Carbon::parse($current_user['expected_end_time'])->addMinutes(1);
                if ($current_time->greaterThanOrEqualTo($tolerance)) {
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
}
