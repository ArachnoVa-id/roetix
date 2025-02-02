<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;

class Seat extends Model
{
    /** @use HasFactory<\Database\Factories\SeatFactory> */
    use HasFactory, Notifiable;

    protected $primaryKey = 'seat_id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'seat_number',
        'position',
        'status',
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

    public function vanue(){
        return $this->belongsTo(Vanue::class, 'venue_id', 'venue_id');
    }
}
