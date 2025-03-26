<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;


class Team extends Model
{
    use HasFactory;

    protected $primaryKey = 'team_id';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'name',
        'code',
        'vendor_quota',
        'event_quota',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->team_id)) {
                $model->team_id = (string) Str::uuid();
            }
        });
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_team', 'team_id', 'id');
    }

    public function events(): HasMany
    {
        return $this->hasMany(Event::class, 'team_id', 'team_id');
    }

    public function venues(): HasMany
    {
        return $this->hasMany(Venue::class, 'team_id', 'team_id');
    }

    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class, 'team_id', 'team_id');
    }
}
