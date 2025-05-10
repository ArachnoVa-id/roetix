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
use PDO;

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

    protected static function getPdo($event)
    {
        $path = storage_path("sql/events/{$event->id}.db");

        if (!file_exists($path)) {
            self::createQueueSqlite($event);
        }

        return new PDO("sqlite:" . $path, null, null, [
            PDO::ATTR_TIMEOUT => 2,
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
        ]);
    }

    public static function createQueueSqlite($event)
    {
        $path = storage_path("sql/events/{$event->id}.db");
        File::ensureDirectoryExists(dirname($path));

        $pdo = new PDO("sqlite:" . $path);

        $query = "
            CREATE TABLE IF NOT EXISTS user_logs (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id TEXT NOT NULL,
                status TEXT NULL,
                start_time DATETIME NULL,
                expected_end_time DATETIME NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )";

        $pdo->exec($query);
    }

    public static function loginUser($event, $user)
    {
        $pdo = self::getPdo($event);

        $stmt = $pdo->prepare("SELECT * FROM user_logs WHERE user_id = ?");
        $stmt->execute([$user->id]);

        if ($stmt->fetch(PDO::FETCH_ASSOC)) {
            throw new Exception('User already queued or logged in.');
        }

        $stmt = $pdo->prepare("INSERT INTO user_logs (user_id, status) VALUES (?, 'waiting')");
        $stmt->execute([$user->id]);

        session([
            'auth_user' => $user,
        ]);
        Auth::login($user);
    }

    public static function promoteUser($event, $user)
    {
        $pdo = self::getPdo($event);

        $eventVariables = $event->eventVariables;
        $duration = $eventVariables->active_users_duration;

        $start = Carbon::now();
        $end = $start->copy()->addMinutes($duration);

        $stmt = $pdo->prepare("UPDATE user_logs SET status = 'online', start_time = ?, expected_end_time = ? WHERE user_id = ?");
        $stmt->execute([$start, $end, $user->id]);
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

        $stmt = $pdo->prepare("SELECT COUNT(*) FROM user_logs WHERE status = 'waiting' AND id < (SELECT id FROM user_logs WHERE user_id = ?)");
        $stmt->execute([$user->id]);
        return $stmt->fetchColumn() + 1;
    }

    public static function countOnlineUsers($event)
    {
        $pdo = self::getPdo($event);

        $stmt = $pdo->query("SELECT COUNT(*) FROM user_logs WHERE status = 'online'");
        return $stmt->fetchColumn();
    }

    public static function logoutUserAndPromoteNext($event, $user, $mqtt)
    {
        $pdo = self::getPdo($event);

        $stmt = $pdo->prepare("DELETE FROM user_logs WHERE user_id = ?");
        $stmt->execute([$user->id]);

        $stmt = $pdo->prepare("SELECT user_id FROM user_logs WHERE status = 'waiting' ORDER BY created_at ASC LIMIT 1");
        $stmt->execute();
        $nextUser = $stmt->fetch(PDO::FETCH_ASSOC);

        $mqttData = [
            'event' => 'user_logout',
            'next_user_id' => $nextUser['user_id'] ?? '',
        ];

        $mqtt->publishMqtt($mqttData, $event->slug);
    }

    public static function removeExpiredUsers($event, $mqtt)
    {
        $pdo = self::getPdo($event);

        $eventVariables = $event->eventVariables;
        $threshold = $eventVariables->active_users_threshold;
        $stmt = $pdo->prepare("SELECT * FROM user_logs WHERE status = 'online' AND expected_end_time < ? ORDER BY created_at ASC LIMIT $threshold");
        $stmt->execute([Carbon::now()->toDateTimeString()]);
        $expiredUsers = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($expiredUsers as $expiredUser) {
            $userModel = User::find($expiredUser['user_id']);
            self::logoutUserAndPromoteNext($event, $userModel, $mqtt);
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

        $day = $this->start_date->format('j');
        $month = $months[(int)$this->start_date->format('n')];
        $year = $this->start_date->format('Y');

        return "{$day} {$month} {$year}";
    }

    public function getEventTime(): string
    {
        return $this->start_date->format('H:i') . ' WIB';
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
}
