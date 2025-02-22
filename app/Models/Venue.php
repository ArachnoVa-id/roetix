<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;

class Venue extends Model
{
    /** @use HasFactory<\Database\Factories\VenueFactory> */
    use HasFactory, Notifiable;

    protected $primaryKey = 'venue_id';
    public $incrementing = false;
    protected $keyType = 'string';

    
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->venue_id)) {
                $model->venue_id = (string) Str::uuid();
            }
        });
    }

    public function contactinfo()
    {
        return $this->belongsTo(UserContact::class, 'contact_info', 'contact_id');
    }
}
