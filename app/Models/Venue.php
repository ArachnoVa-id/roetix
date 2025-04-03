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

    protected $primaryKey = 'venue_id';
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
        'seats',
        'events',
        'events.ticketCategories',
        'events.ticketCategories.eventCategoryTimeboundPrices',
        'events.ticketCategories.eventCategoryTimeboundPrices.timelineSession',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $team = Team::find($model->team_id);

            if (empty($model->venue_id)) {
                $model->venue_id = (string) Str::uuid();
            }
        });
    }

    public function importSeats($config = null)
    {
        $success = false;
        $message = '';

        ImportSeatMap::generateFromConfig(
            config: $config,
            venueId: $this->venue_id,
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

    public function exportSeats()
    {
        // Retrieve seat data related to the model, assuming it's related by 'seats' relationship
        $seats = $this->seats()->pluck('position')->toArray();

        // Format the data
        $export = [
            'layout' => [
                'items' => $seats
            ]
        ];

        // Encode data to JSON format
        $encoded = json_encode($export, JSON_PRETTY_PRINT);

        // Define the filename
        $venueName = Str::slug($this->name);
        $fileName = "novatix-{$venueName}-seatconfig.json";

        // Return a JSON download response
        return response()->streamDownload(function () use ($encoded) {
            echo $encoded;
        }, $fileName, [
            'Content-Type' => 'application/json',
            'Content-Disposition' => "attachment; filename={$fileName}",
        ]);
    }

    public function seats(): HasMany
    {
        return $this->hasMany(Seat::class, 'venue_id', 'venue_id');
    }

    public function contactInfo(): BelongsTo
    {
        return $this->belongsTo(UserContact::class, 'contact_info', 'contact_id');
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class, 'team_id', 'team_id');
    }

    public function events(): HasMany
    {
        return $this->hasMany(Event::class, 'venue_id', 'venue_id');
    }
}
