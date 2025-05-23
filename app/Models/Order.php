<?php

namespace App\Models;

use App\Enums\OrderType;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;


class Order extends Model
{
    /** @use HasFactory<\Database\Factories\OrderFactory> */
    use HasFactory, Notifiable;

    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'accessor',
        'order_code',
        'event_id',
        'user_id',
        'team_id',
        'order_date',
        'total_price',
        'status',
        'expired_at',
    ];

    protected $with = [
        'team',
        'user'
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->id)) {
                $model->id = (string) Str::uuid();
            }
        });
    }

    public function getOrderDateTimestamp()
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

        $order_date = Carbon::parse($this->order_date);
        $day = $order_date->format('j');
        $month = $months[(int)$order_date->format('n')];
        $year = $order_date->format('Y');
        $time = $order_date->format('H:i');

        return "{$day} {$month} {$year}, {$time} WIB";
    }

    public static function keyGen(OrderType $type, Event $event): string
    {
        $eventUniq = '';
        if ($event) {
            $eventName = $event->name;
            $eventName = preg_replace('/\s+/', '', $eventName);
            $eventName = substr($eventName, 0, 3);
            $eventUniq = strtoupper($eventName);
        }

        switch ($type) {
            case OrderType::AUTO:
                return 'ORD-' . $eventUniq . '-' . time() . '-' . rand(1000, 9999);
            case OrderType::MANUAL:
                return 'MAN-' . $eventUniq . '-' . time() . '-' . rand(1000, 9999);
            case OrderType::TRANSFER:
                return 'TRF-' . $eventUniq . '-' .  time() . '-' . rand(1000, 9999);
            default:
                return 'UNK-' . $eventUniq . '-' .  time() . '-' . rand(1000, 9999);
        }
    }

    public function keyGenSelf(OrderType $type): string
    {
        // generate unique id for event parsed from event name
        $event = $this->events()->first();
        return self::keyGen($type, $event);
    }

    public function tickets(): BelongsToMany
    {
        return $this->belongsToMany(Ticket::class, 'ticket_order', 'order_id', 'ticket_id');
    }

    public function ticketOrders(): HasMany
    {
        return $this->hasMany(TicketOrder::class, 'order_id', 'id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function getSingleEvent()
    {
        return $this->events()->first();
    }

    public function events(): BelongsToMany
    {
        return $this->belongsToMany(Event::class, 'ticket_order', 'order_id', 'event_id');
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class, 'team_id', 'id');
    }
}
