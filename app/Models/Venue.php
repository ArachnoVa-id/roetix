<?php

namespace App\Models;

use App\Console\Commands\ImportSeatMap;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;

class Venue extends Model
{
    /** @use HasFactory<\Database\Factories\VenueFactory> */
    use HasFactory, Notifiable;

    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'team_id',
        'name',
        'location',
        'contact_info',
        'status'
    ];

    protected $with = [
        'team'
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

    public function importSeats($config = null)
    {
        $success = false;
        $message = '';

        ImportSeatMap::generateFromConfig(
            config: $config,
            venueId: $this->id,
            successLineCallback: fn() => null,
            successCallback: function ($msg) use (&$success, &$message) {
                $success = true;
                $message = $msg;
            },
            failedCallback: function ($msg) use (&$message) {
                $message = $msg;
            }
        );

        return [$success, $message];
    }

    public function seats(): HasMany
    {
        return $this->hasMany(Seat::class, 'venue_id', 'id');
    }

    public function contactInfo(): BelongsTo
    {
        return $this->belongsTo(UserContact::class, 'contact_info', 'id');
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class, 'team_id', 'id');
    }

    public function events(): HasMany
    {
        return $this->hasMany(Event::class, 'venue_id', 'id');
    }
}
