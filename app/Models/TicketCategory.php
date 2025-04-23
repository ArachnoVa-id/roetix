<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;
use Illuminate\Notifications\Notifiable;

class TicketCategory extends Model
{
    use Notifiable;

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
            if (empty($model->id)) {
                $model->id = (string) Str::uuid();
            }
        });
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class, 'event_id', 'id');
    }

    public function eventCategoryTimeboundPrices(): HasMany
    {
        return $this
            ->hasMany(EventCategoryTimeboundPrice::class, 'ticket_category_id', 'id')
            ->join('timeline_sessions', 'event_category_timebound_prices.timeline_id', '=', 'timeline_sessions.id')
            ->orderBy('timeline_sessions.start_date')
            ->select('event_category_timebound_prices.*');
    }
}
