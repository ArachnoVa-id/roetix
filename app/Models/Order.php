<?php

namespace App\Models;

use App\Enums\OrderType;
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

    protected $primaryKey = 'order_id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'snap_token',
        'order_code',
        'event_id',
        'user_id',
        'team_id',
        'order_date',
        'total_price',
        'status'
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->order_id)) {
                $model->order_id = (string) Str::uuid();
            }
        });
    }

    public static function keyGen(OrderType $type): string
    {
        switch ($type) {
            case OrderType::AUTO:
                return 'ORD-' . time() . '-' . rand(1000, 9999);
            case OrderType::MANUAL:
                return 'MAN-' . time() . '-' . rand(1000, 9999);
            case OrderType::TRANSFER:
                return 'TRF-' . time() . '-' . rand(1000, 9999);
            default:
                return 'UNK-' . time() . '-' . rand(1000, 9999);
        }
    }

    public function tickets(): BelongsToMany
    {
        return $this->belongsToMany(Ticket::class, 'ticket_order', 'order_id', 'ticket_id');
    }

    public function ticketOrders(): HasMany
    {
        return $this->hasMany(TicketOrder::class, 'order_id', 'order_id');
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
        return $this->belongsTo(Team::class, 'team_id', 'team_id');
    }
}
