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
    
        // User online pertama (paling awal)
        $stmt = $pdo->query("
            SELECT * FROM user_logs
            WHERE status = 'waiting'
            ORDER BY created_at ASC
            LIMIT 1
        ");
        $firstOnlineUser = $stmt->fetch(PDO::FETCH_ASSOC);
    
        // Cek suspected offline user
        $suspected_offline_user = $pdo->query("
            SELECT * FROM user_logs
            WHERE status = 'online'
            AND expected_end_time <= datetime('now', '-1 minute')
            ORDER BY expected_end_time ASC
            LIMIT 1
        ")->fetch(PDO::FETCH_ASSOC);
    
        // Jika ada user yang melewati batas waktu, hapus log-nya
        if ($suspected_offline_user) {
            $stmt = $pdo->prepare("DELETE FROM user_logs WHERE id = ?");
            $stmt->execute([$suspected_offline_user['id']]);
    
            // Setelah dihapus, update ulang trafficNumber
            $stmt = $pdo->query("SELECT COUNT(*) FROM user_logs WHERE status = 'online'");
            $trafficNumber = $stmt->fetchColumn();
        }
    
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
                    'title' => "Overload User Capacity",
                    'message' => 'Please wait...',
                    'expected_finish' => Carbon::now()->addMinutes(1)->format('H:i:s'),
                ],
            ]);
        }
    
        return $next($request);
    }
    
}
