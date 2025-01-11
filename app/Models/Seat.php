<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Seat extends Model
{
    use HasUuids;

    protected $primaryKey = 'seat_id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'venue_id',
        'seat_number',
        'position',
        'status'
    ];

    protected $casts = [
        'status' => 'string'
    ];

    public function venue()
    {
        return $this->belongsTo(Venue::class, 'venue_id', 'venue_id');
    }
}