<?php

namespace App\Models;

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

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $team = Team::find($model->team_id);

            if (empty($model->venue_id)) {
                $model->venue_id = (string) Str::uuid();
            }
            
            // protect from model
            if ($team && $team->venues()->count() >= $team->vendor_quota) {
                throw new \Exception("This team has reached its venue limit.");
            }
        });
    }

    public function capacity(): int
    {
        return $this->seats()->count();
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
