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
        'locked_password',

        'is_maintenance',
        'maintenance_title',
        'maintenance_message',
        'maintenance_expected_finish',

        'logo',
        'favicon',
        'primary_color',
        'secondary_color',
        'text_primary_color',
        'text_secondary_color',
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

    public function event()
    {
        return $this->hasOne(Event::class, 'event_variables_id', 'event_variables_id');
    }
}
