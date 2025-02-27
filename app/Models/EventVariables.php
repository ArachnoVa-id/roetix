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
        'event_variables_id',
        'is_locked',
        'is_maintenance',
        'var_title',
        'expected_finish',
        'password'
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->event_variables_id)) {
                $model->event_variables_id = (string) Str::uuid();
            }
        });
    }
}
