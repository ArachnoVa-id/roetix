<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TrafficNumbersSlug extends Model
{
    protected $table = 'traffic_numbers_slugs';

    protected $fillable = [
        'event_id',
        'active_sessions',
    ];

    /**
     * Relasi ke model Event
     */
    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class, 'event_id');
    }
}
