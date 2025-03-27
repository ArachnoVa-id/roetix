<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Seat extends Model
{
    use HasFactory, Notifiable;

    protected $primaryKey = 'seat_id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'venue_id',
        'seat_number',
        'position',
        'row',
        'column'
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->seat_id)) {
                $model->seat_id = (string) Str::uuid();
            }
        });
    }

    protected $casts = [
        'column' => 'integer'
    ];

    public function venue(): BelongsTo
    {
        return $this->belongsTo(Venue::class, 'venue_id', 'venue_id');
    }

    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class, 'seat_id', 'seat_id');
    }
}
