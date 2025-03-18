<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TimelineSession extends Model
{
    use HasFactory;

    protected $primaryKey = 'timeline_id';
    public $incrementing = false;
    protected $keyType = 'string';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'event_id',
        'name',
        'start_date',
        'end_date',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'start_date' => 'datetime',
        'end_date' => 'datetime',
    ];

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();
        static::creating(function ($model) {
            if (empty($model->timeline_id)) {
                $model->timeline_id = (string) Str::uuid();
            }
        });
    }

    /**
     * Get the event that owns the timeline session.
     */
    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class, 'event_id', 'event_id');
    }

    public function eventCategoryTimeboundPrices(): HasMany
    {
        return $this->hasMany(EventCategoryTimeboundPrice::class, 'timeline_id', 'timeline_id')
            ->join('timeline_sessions', 'event_category_timebound_prices.timeline_id', '=', 'timeline_sessions.timeline_id')
            ->orderBy('timeline_sessions.created_at');
    }
}
