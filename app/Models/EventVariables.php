<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Illuminate\Notifications\Notifiable;


class EventVariables extends Model
{
    /** @use HasFactory<\Database\Factories\EventVariablesFactory> */
    use HasFactory, Notifiable;

    protected $primaryKey = 'event_variables_id';
    protected $keyType = 'string';
    public $incrementing = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'is_locked',
        'is_maintenance',
        'var_a',
        'var_b',
        'var_c'
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            // Automatically set the `event_variables_id` to a UUID if it's not provided
            if (empty($model->event_variables_id)) {
                $model->event_variables_id = (string) Str::uuid();
            }
        });
    }
}
