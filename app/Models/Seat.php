<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Seat extends Model
{
    use HasFactory, Notifiable;

    protected $primaryKey = 'seat_id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'seat_id',
        'venue_id',
        'seat_number',
        'position',
        'status',
        'category',
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


    /**
     * Get the section that owns the seat.
     */
    // public function section(): BelongsTo
    // {
    //     return $this->belongsTo(Section::class, 'section_id', 'id');
    // }

    public function venue()
    {
        return $this->belongsTo(Venue::class, 'venue_id', 'venue_id');
    }

}
