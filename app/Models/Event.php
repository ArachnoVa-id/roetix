<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\Request;

class Event extends Model
{
    use HasFactory, Notifiable;
    protected $primaryKey = 'event_id';
    public $incrementing = false;
    protected $keyType = 'string';
    protected $fillable = [
        'venue_id',
        'name',
        'slug',
        'category',
        'start_date',
        'event_date',
        'location',
        'status',
        'team_id',
    ];
    protected $casts = [
        'start_date' => 'datetime',
        'end_date' => 'datetime',
        'event_date' => 'datetime', // Added datetime cast for event_date
    ];
    protected static function boot()
    {
        parent::boot();
        static::creating(function ($model) {
            if (empty($model->event_id)) {
                $model->event_id = (string) Str::uuid();
            }
        });
        static::saving(function ($model) {
            // launch on edit only
            if (!$model->exists) return;
            $request = Request::all();
            // check if components key exists
            if (!isset($request['components'][0]['updates'][0])) return;
            $updates = $request['components'][0]['updates'];
            // $dataArray = json_decode($formData, true)['data']['data'][0];
            $cleanedChanges = [];
            foreach ($updates as $key => $value) {
                $cleanedKey = str_replace("data.", "", $key);
                $cleanedChanges[$cleanedKey] = $value;
            }
            $eventVariables = $model->eventVariables;
            // loop the keys of eventVariables and update the model if the updated key exist in cleanChanges
            foreach ($eventVariables->getAttributes() as $key => $value) {
                if (array_key_exists($key, $cleanedChanges)) {
                    $eventVariables->$key = $cleanedChanges[$key];
                }
            }
            // save
            $eventVariables->save();
        });
    }
    public function timelineSessions(): HasMany
    {
        return $this->hasMany(TimelineSession::class, 'event_id', 'event_id')->orderBy('start_date');
    }
    public function ticketCategories(): HasMany
    {
        return $this->hasMany(TicketCategory::class, 'event_id', 'event_id')
            ->join('event_category_timebound_prices', 'ticket_categories.ticket_category_id', '=', 'event_category_timebound_prices.ticket_category_id')
            ->join('timeline_sessions', 'event_category_timebound_prices.timeline_id', '=', 'timeline_sessions.timeline_id')
            ->orderBy('timeline_sessions.created_at');
    }
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class, 'team_id', 'team_id');
    }
    public function eventVariables(): HasOne
    {
        return $this->hasOne(EventVariables::class, 'event_id', 'event_id');
    }
    public function venue(): BelongsTo
    {
        return $this->belongsTo(Venue::class, 'venue_id', 'venue_id');
    }
    public function orders(): BelongsToMany
    {
        return $this->belongsToMany(Order::class, 'ticket_order', 'event_id', 'order_id');
    }
}
