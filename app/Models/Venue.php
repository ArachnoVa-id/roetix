<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Venue extends Model
{
    use HasUuids;

    protected $primaryKey = 'venue_id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'name',
        'location',
        'capacity',
        'contact_info',
        'status'
    ];

    protected $casts = [
        'capacity' => 'integer',
        'status' => 'string'
    ];

    public function seats()
    {
        return $this->hasMany(Seat::class, 'venue_id', 'venue_id');
    }
}