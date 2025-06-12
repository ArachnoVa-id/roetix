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

    public static function getPdo($event)
    {
        $eventId = $event->id;

        if (!isset(self::$connections[$eventId])) {
            self::$connections[$eventId] = self::createQueueSqlite($event);
        }

        return self::$connections[$eventId];
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
        if ($userQueue) {
            Auth::logout();
            throw new Exception('User already logged in or queued.');
        }

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
        if ($specificEventId) {
            $event = Event::where('id', $specificEventId)
                ->where('status', 'active')
                ->first();
            if (!$event) {
                Log::warning("Event {$specificEventId} not found or not active");
                return;
            }
            $activeEvents = collect([$event]);
        } else {
            $activeEvents = Event::where('status', 'active')->get();
        }

        foreach ($activeEvents as $event) {
            $maxRetries = 3;
            $retryDelay = 100; // milliseconds

            for ($attempt = 1; $attempt <= $maxRetries; $attempt++) {
                try {
                    if (self::processEventWithRetry($event, $attempt)) {
                        break; // Success, move to next event
                    }
                } catch (\Exception $e) {
                    if ($attempt === $maxRetries) {
                        Log::error("Failed to process event {$event->id} after {$maxRetries} attempts: " . $e->getMessage());
                    } else {
                        Log::warning("Attempt {$attempt} failed for event {$event->id}, retrying: " . $e->getMessage());
                        usleep($retryDelay * 1000 * $attempt); // Exponential backoff
                    }
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

    // Improved SQLite connection with better concurrency settings
    public static function createQueueSqlite($event)
    {
        $path = storage_path("sql/events/{$event->id}.db");
        File::ensureDirectoryExists(dirname($path));

        $pdo = new PDO("sqlite:" . $path);

        // Optimized SQLite settings for concurrent operations
        $pdo->exec('PRAGMA journal_mode = WAL;');
        $pdo->exec('PRAGMA synchronous = NORMAL;'); // Balance between speed and safety
        $pdo->exec('PRAGMA busy_timeout = 10000;'); // 10 seconds timeout
        $pdo->exec('PRAGMA foreign_keys = ON;');
        $pdo->exec('PRAGMA cache_size = -64000;'); // 64MB cache
        $pdo->exec('PRAGMA temp_store = MEMORY;'); // Use memory for temp tables
        $pdo->exec('PRAGMA mmap_size = 268435456;'); // 256MB memory mapping

        // Create table with better indexing
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

        // Create indexes for better performance
        $pdo->exec('CREATE INDEX IF NOT EXISTS idx_status_created ON user_logs(status, created_at);');
        $pdo->exec('CREATE INDEX IF NOT EXISTS idx_expected_kick ON user_logs(expected_kick) WHERE status = "online";');
        $pdo->exec('CREATE INDEX IF NOT EXISTS idx_user_status ON user_logs(user_id, status);');

        return $pdo;
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

    // public function getRouteKeyName()
    // {
    //     return 'slug';
    // }
}
