<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;
use Illuminate\Notifications\Notifiable;

class EventVariables extends Model
{
    /** @use HasFactory<\Database\Factories\EventVariablesFactory> */
    use Notifiable;
    protected $primaryKey = 'event_variables_id';
    protected $keyType = 'string';
    public $incrementing = false;
    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'event_id',
        'is_locked',
        'locked_password',
        'is_maintenance',
        'maintenance_title',
        'maintenance_message',
        'maintenance_expected_finish',
        'logo',
        'logo_alt',
        'texture',
        'favicon',
        'primary_color',
        'secondary_color',
        'text_primary_color',
        'text_secondary_color',
        'ticket_limit',
    ];
    public static function getDefaultValue()
    {
        $defaultValues = [
            'primary_color' => '#CCC',
            'secondary_color' => '#FFF',
            'text_primary_color' => '#000',
            'text_secondary_color' => '#000',
            'is_maintenance' => false,
            'maintenance_title' => '',
            'maintenance_message' => '',
            'maintenance_expected_finish' => now(),
            'is_locked' => false,
            'locked_password' => '',
            'logo' => '/images/novatix-logo/android-chrome-192x192.png',
            'logo_alt' => 'Novatix Logo',
            'texture' => '/images/default-texture/Texturelabs_Sky_152S.jpg',
            'favicon' => '/images/novatix-logo/favicon.ico',
            'ticket_limit' => 5,
        ];
        return $defaultValues;
    }
    protected static function boot()
    {
        parent::boot();
        static::creating(function ($model) {
            if (empty($model->event_variables_id)) {
                $model->event_variables_id = (string) Str::uuid();
            }
        });
    }
    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class, 'event_id', 'event_id');
    }
}
