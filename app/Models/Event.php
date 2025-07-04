<?php

namespace App\Models;

use Carbon\Carbon;
use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use PhpMqtt\Client\MqttClient;
use PhpMqtt\Client\ConnectionSettings;
use PDO;
use Throwable;

class Event extends Model
{
    use HasFactory, Notifiable;

    public $incrementing = false;
    protected $keyType = 'string';
    protected $fillable = [
        'venue_id',
        'name',
        'slug',
        'start_date',
        'event_date',
        'location',
        'status',
        'team_id',
    ];

    protected $casts = [
        'start_date' => 'datetime',
        'end_date' => 'datetime',
        'event_date' => 'datetime',
    ];

    protected $with = [
        'team'
    ];

    // Add this to your Event model
    private static $connections = [];

    protected static function getPdo($event)
    {
        $path = storage_path("sql/events/{$event->id}.db");

        $dir = dirname($path);
        File::ensureDirectoryExists($dir);

        if (!file_exists($path)) {
            self::createQueueSqlite($event);
            touch($path);
            chmod($path, 0666);  // writable by all users (or adjust to your needs)
        }

        $pdo = new PDO("sqlite:" . $path, null, null, [
            PDO::ATTR_TIMEOUT => 5, // increased timeout to 5 seconds
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        ]);

        // Enable WAL mode for better concurrency
        $pdo->exec('PRAGMA journal_mode = WAL;');
        $pdo->exec('PRAGMA busy_timeout = 5000;');

        return $pdo;
    }

    public static function createQueueSqlite($event)
    {
        $path = storage_path("sql/events/{$event->id}.db");
        File::ensureDirectoryExists(dirname($path));

        $pdo = new PDO("sqlite:" . $path);

        // Enable WAL mode right after creating new DB for better concurrent reads/writes
        $pdo->exec('PRAGMA journal_mode = WAL;');

        // Set busy timeout to wait before throwing "database is locked" errors
        $pdo->exec('PRAGMA busy_timeout = 5000;');  // Wait up to 5 seconds

        // Use foreign keys enforcement if you want referential integrity (optional)
        $pdo->exec('PRAGMA foreign_keys = ON;');

        // Create user_logs table with UNIQUE constraint on user_id to prevent duplicate users
        $query = "
        CREATE TABLE IF NOT EXISTS user_logs (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id TEXT NOT NULL UNIQUE,
            status TEXT NULL,
            start_time DATETIME NULL,
            expected_kick DATETIME,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )";

        $pdo->exec($query);
    }

    public static function loginUser($event, $user)
    {
        session([
            'auth_user' => $user,
        ]);
        Auth::login($user);

        // if user is admin, skip queue
        if ($user->isAdmin()) {
            return;
        }

        // Check if user already exists in the queue
        $userQueue = self::getUser($event, $user);
        // if ($userQueue) {
        //    Auth::logout();
        //    throw new Exception('User already logged in or queued.');
        // }

        $pdo = self::getPdo($event);

        $stmt = $pdo->prepare("INSERT INTO user_logs (user_id, status) VALUES (?, 'waiting')");
        $stmt->execute([$user->id]);

        // Optionally trigger adjust immediately
        if (cache()->lock('adjust_users_lock', 10)->get()) {
            try {
                self::adjustUsers($event->id);
            } finally {
                cache()->forget('adjust_users_lock');
            }
        }
    }

    public static function promoteUser($event, $user)
    {
        $pdo = self::getPdo($event);

        $eventVariables = $event->eventVariables;
        $duration = $eventVariables->active_users_duration;

        $start = Carbon::now();
        $end = $start->copy()->addMinutes($duration);

        $stmt = $pdo->prepare("UPDATE user_logs SET status = 'online', start_time = ?, expected_kick = ? WHERE user_id = ?");
        $stmt->execute([$start, $end, $user->id]);

        // Publish MQTT message for user promotion
        $mqttData = [
            'event' => 'user_promoted',
            'user_id' => $user->id,
            'start_time' => $start->toDateTimeString(),
            'expected_kick' => $end->toDateTimeString(),
        ];

        self::publishMqtt($mqttData, $event->slug);
    }

    public static function getUser($event, $user)
    {
        $pdo = self::getPdo($event);

        $stmt = $pdo->prepare("SELECT * FROM user_logs WHERE user_id = ?");
        $stmt->execute([$user->id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        return $user ?: null;
    }

    public static function getUserPosition($event, $user)
    {
        $pdo = self::getPdo($event);

        $stmt = $pdo->prepare(
            "SELECT COUNT(*) FROM user_logs
            WHERE status = 'waiting'
                AND id < (SELECT id FROM user_logs WHERE user_id = ?)"
        );
        $stmt->execute([$user->id]);
        return $stmt->fetchColumn() + 1;
    }

    public static function countOnlineUsers($event)
    {
        $pdo = self::getPdo($event);

        $stmt = $pdo->query("SELECT COUNT(*) FROM user_logs WHERE status = 'online'");
        return $stmt->fetchColumn();
    }

    public static function logoutUser($event, $user)
    {
        $pdo = self::getPdo($event);

        try {
            $pdo->beginTransaction();

            // Remove the user
            $stmt = $pdo->prepare("DELETE FROM user_logs WHERE user_id = ?");
            $stmt->execute([$user->id]);

            $pdo->commit();

            Auth::logout();
        } catch (Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }

        // MQTT publish logout (we don't yet know the next user here)
        $nextStmt = $pdo->prepare("
            SELECT user_id FROM user_logs
            WHERE status = 'waiting'
            ORDER BY created_at ASC LIMIT 1
        ");
        $nextStmt->execute();

        $mqttData = [
            'event' => 'user_logout'
        ];
        self::publishMqtt($mqttData, $event->slug);

        // Attempt to call adjustUsers if it's not already running
        if (cache()->lock('adjust_users_lock', 10)->get()) {
            try {
                self::adjustUsers($event->id);
            } finally {
                cache()->forget('adjust_users_lock');
            }
        }
    }

    public static function adjustUsers($specificEventId = null)
    {
	return;
        if ($specificEventId) {
            // Process single event
            $event = Event::where('id', $specificEventId)
                ->where('status', 'active')
                ->first();

            if (!$event) {
                Log::warning("Event {$specificEventId} not found or not active");
                return;
            }

            $activeEvents = collect([$event]);
        } else {
            // Process all active events (legacy behavior)
            $activeEvents = Event::where('status', 'active')->get();
        }

        foreach ($activeEvents as $event) {
            $pdo = self::getPdo($event);
            $now = Carbon::now()->toDateTimeString();

            // 1. Kick users whose expected_kick has passed or is NULL
            $stmt = $pdo->prepare("
            SELECT * FROM user_logs
            WHERE expected_kick < ?
            ORDER BY created_at ASC
        ");
            $stmt->execute([$now]);
            $expiredUsers = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Prefetch users to avoid N+1
            $userIds = array_column($expiredUsers, 'user_id');
            $users = User::whereIn('id', $userIds)->get()->keyBy('id');

            foreach ($expiredUsers as $expiredUser) {
                $userModel = $users->get($expiredUser['user_id']);
                if ($userModel) {
                    self::logoutUser($event, $userModel);
                }
            }

            // 2. Re-broadcast still online users in case stuck in loading
            $stmt = $pdo->prepare("
            SELECT * FROM user_logs
            WHERE status = 'online'
        ");
            $stmt->execute();
            $onlineUsers = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $userIds = array_column($onlineUsers, 'user_id');
            $users = User::whereIn('id', $userIds)->get()->keyBy('id');

            foreach ($onlineUsers as $onlineUser) {
                $userModel = $users->get($onlineUser['user_id']);
                if ($userModel) {
                    // Re-publish MQTT message for online users
                    $mqttData = [
                        'event' => 'user_promoted',
                        'user_id' => $userModel->id,
                        'start_time' => $onlineUser['start_time'],
                        'expected_kick' => $onlineUser['expected_kick']
                    ];
                    self::publishMqtt($mqttData, $event->slug);
                }
            }

            // 3. Promote waiting users up to active_users_threshold
            $maxOnline = $event->eventVariables->active_users_threshold ?? 0;
            $currentOnlineCount = self::countOnlineUsers($event);
            $availableSlots = max(0, $maxOnline - $currentOnlineCount);

            if ($availableSlots <= 0) {
                continue;
            }

            // Get waiting users regardless of expected_online
            $stmt = $pdo->prepare("
            SELECT * FROM user_logs
            WHERE status = 'waiting'
            ORDER BY created_at ASC
            LIMIT ?
        ");
            $stmt->execute([$availableSlots]);
            $readyUsers = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $userIdsToPromote = array_column($readyUsers, 'user_id');
            $userModels = User::whereIn('id', $userIdsToPromote)->get()->keyBy('id');

            foreach ($readyUsers as $userRow) {
                $userModel = $userModels->get($userRow['user_id']);
                if ($userModel) {
                    self::promoteUser($event, $userModel);
                }
            }
        }
    }

    private static function processEventWithRetry($event, $attempt)
    {
        $pdo = self::getPdo($event);
        $now = Carbon::now()->toDateTimeString();

        // Configure SQLite for better concurrency
        $pdo->exec('PRAGMA busy_timeout = 10000;'); // 10 seconds
        $pdo->exec('PRAGMA synchronous = NORMAL;'); // Better performance
        $pdo->exec('PRAGMA cache_size = -64000;'); // 64MB cache

        // Use IMMEDIATE transaction to reduce lock contention
        $pdo->exec('BEGIN IMMEDIATE;');

        try {
            // Step 1: Clean up expired users with single atomic operation
            $expiredCount = self::cleanupExpiredUsers($pdo, $event, $now);

            // Step 2: Get current counts and calculate available slots
            $maxOnline = $event->eventVariables->active_users_threshold ?? 0;

            // Use a single query to get current online count
            $stmt = $pdo->prepare("
                SELECT COUNT(*) FROM user_logs
                WHERE status = 'online' AND (expected_kick IS NULL OR expected_kick >= ?)
            ");
            $stmt->execute([$now]);
            $currentOnlineCount = $stmt->fetchColumn();

            $availableSlots = max(0, $maxOnline - $currentOnlineCount);

            Log::info("Event {$event->id} (attempt {$attempt}): {$currentOnlineCount}/{$maxOnline} online, {$availableSlots} slots available");

            if ($availableSlots > 0) {
                $promotedCount = self::promoteWaitingUsers($pdo, $event, $availableSlots, $now);
                Log::info("Event {$event->id}: Promoted {$promotedCount} users");
            }

            $pdo->exec('COMMIT;');
            return true;
        } catch (\Exception $e) {
            $pdo->exec('ROLLBACK;');

            // Check if it's a database lock error
            if (strpos($e->getMessage(), 'database is locked') !== false) {
                Log::warning("Database locked for event {$event->id} on attempt {$attempt}");
                return false; // Retry
            }

            throw $e; // Other errors should bubble up
        }
    }

    private static function cleanupExpiredUsers($pdo, $event, $now)
    {
        // Get expired users in a single query
        $stmt = $pdo->prepare("
            SELECT user_id FROM user_logs
            WHERE status = 'online' AND expected_kick < ?
            ORDER BY expected_kick ASC
        ");
        $stmt->execute([$now]);
        $expiredUserIds = $stmt->fetchAll(PDO::FETCH_COLUMN);

        if (empty($expiredUserIds)) {
            return 0;
        }

        // Update their status to 'kicked' first (atomic operation)
        $placeholders = str_repeat('?,', count($expiredUserIds) - 1) . '?';
        $updateStmt = $pdo->prepare("
            UPDATE user_logs
            SET status = 'kicked'
            WHERE user_id IN ({$placeholders}) AND status = 'online'
        ");
        $updateStmt->execute($expiredUserIds);
        $kickedCount = $updateStmt->rowCount();

        if ($kickedCount > 0) {
            // Get user models for MQTT publishing (outside transaction if possible)
            $users = User::whereIn('id', array_slice($expiredUserIds, 0, $kickedCount))->get();

            // Publish MQTT messages for kicked users
            foreach ($users as $user) {
                $mqttData = [
                    'event' => 'user_kicked',
                    'user_id' => $user->id,
                    'reason' => 'expired'
                ];
                self::publishMqtt($mqttData, $event->slug);
            }
        }

        return $kickedCount;
    }

    private static function promoteWaitingUsers($pdo, $event, $availableSlots, $now)
    {
        // Get waiting users with row-level locking simulation
        $stmt = $pdo->prepare("
            SELECT user_id, created_at FROM user_logs
            WHERE status = 'waiting'
            ORDER BY created_at ASC
            LIMIT ?
        ");
        $stmt->execute([$availableSlots]);
        $waitingUsers = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($waitingUsers)) {
            return 0;
        }

        $promotedCount = 0;
        $startTime = $now;
        $expectedKick = Carbon::parse($now)->addMinutes(5)->toDateTimeString();

        foreach ($waitingUsers as $userRow) {
            // Atomic promotion with double-check
            $updateStmt = $pdo->prepare("
                UPDATE user_logs
                SET status = 'online',
                    start_time = ?,
                    expected_kick = ?
                WHERE user_id = ? AND status = 'waiting'
            ");

            $result = $updateStmt->execute([$startTime, $expectedKick, $userRow['user_id']]);

            if ($result && $updateStmt->rowCount() > 0) {
                $promotedCount++;

                // Publish MQTT for successful promotion
                $mqttData = [
                    'event' => 'user_promoted',
                    'user_id' => $userRow['user_id'],
                    'start_time' => $startTime,
                    'expected_kick' => $expectedKick
                ];
                self::publishMqtt($mqttData, $event->slug);
            }
        }

        return $promotedCount;
    }

    public static function publishMqtt(array $data, string $mqtt_code = "defaultcode")
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

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->id)) {
                $model->id = (string) Str::uuid();
            }
        });

        static::created(function ($model) {
            static::createQueueSqlite($model);
        });
    }

    public function eventYear(): string
    {
        return $this->start_date->format('Y');
    }

    public function getEventDate(): string
    {
        $months = [
            1 => 'Januari',
            2 => 'Februari',
            3 => 'Maret',
            4 => 'April',
            5 => 'Mei',
            6 => 'Juni',
            7 => 'Juli',
            8 => 'Agustus',
            9 => 'September',
            10 => 'Oktober',
            11 => 'November',
            12 => 'Desember'
        ];

        $day = $this->event_date->format('j');
        $month = $months[(int)$this->event_date->format('n')];
        $year = $this->event_date->format('Y');

        return "{$day} {$month} {$year}";
    }

    public function getEventTime(): string
    {
        return $this->event_date->format('H:i') . ' WIB';
    }

    public function getAllTicketsPDFTitle()
    {
        return strtoupper($this->slug) . '-' . $this->eventYear() . '-TICKETS' . '.pdf';
    }

    public function timelineSessions(): HasMany
    {
        return $this
            ->hasMany(TimelineSession::class, 'event_id', 'id')
            ->orderBy('start_date');
    }

    public function ticketCategories(): HasMany
    {
        return $this
            ->hasMany(TicketCategory::class, 'event_id', 'id')
            ->orderBy('created_at');
    }

    public function eventVariables(): HasOne
    {
        return $this->hasOne(EventVariables::class, 'event_id', 'id');
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class, 'team_id', 'id');
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class, 'event_id', 'id');
    }

    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class, 'event_id', 'id');
    }

    // public function getTicketNumbers()
    // {
    //     return TicketOrder::whereHas('ticket', function ($query) {
    //         $query->where('event_id', $this->id)
    //             ->where('status', 'completed');
    //     })->count();
    // }


    // public function getRouteKeyName()
    // {
    //     return 'slug';
    // }
}
