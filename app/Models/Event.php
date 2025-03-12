<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Event extends Model
{
    use HasFactory, Notifiable;

    protected $primaryKey = 'event_id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'venue_id',
        'event_variables_id',
        'name',
        'slug',
        'category',
        'start_date',
        'end_date',
        'location',
        'status',
        'team_id',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->event_id)) {
                $model->event_id = (string) Str::uuid();
            }
        });
    }

    public function tikcetcategory(): HasMany
    {
        return $this->hasMany(TicketCategory::class, 'event_id', 'event_id');
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class, 'team_id', 'team_id');
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
