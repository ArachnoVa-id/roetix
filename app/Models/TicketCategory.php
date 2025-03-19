<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;
use Illuminate\Notifications\Notifiable;

class TicketCategory extends Model
{
    use HasFactory, Notifiable;

    protected $primaryKey = 'ticket_category_id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'event_id',
        'name',
        'color',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->ticket_category_id)) {
                $model->ticket_category_id = (string) Str::uuid();
            }
        });
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class, 'event_id', 'event_id');
    }

    public function eventCategoryTimeboundPrices(): HasMany
    {
        return $this
            ->hasMany(EventCategoryTimeboundPrice::class, 'ticket_category_id', 'ticket_category_id')
            ->join('timeline_sessions', 'event_category_timebound_prices.timeline_id', '=', 'timeline_sessions.timeline_id')
            ->orderBy('timeline_sessions.start_date');
    }
}
