<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Crypt;

class EventVariables extends Model
{
    /** @use HasFactory<\Database\Factories\EventVariablesFactory> */
    use Notifiable;

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
        'active_users_threshold',
        'active_users_duration',
        'terms_and_conditions',
        'privacy_policy',
        'midtrans_client_key_sb',
        'midtrans_server_key_sb',
        'midtrans_client_key',
        'midtrans_server_key',
        'midtrans_is_production',
        'midtrans_use_novatix',
        'contact_person'
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
            'texture' => null,
            'favicon' => '/images/novatix-logo/favicon.ico',
            'ticket_limit' => 5,
            'active_users_threshold' => 100,
            'active_users_duration' => 10,
            'terms_and_conditions' => '',
            'privacy_policy' => '',
            'midtrans_client_key_sb' => '',
            'midtrans_server_key_sb' => '',
            'midtrans_client_key' => '',
            'midtrans_server_key' => '',
            'midtrans_is_production' => false,
            'midtrans_use_novatix' => false
        ];
        return $defaultValues;
    }

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($model) {
            if (empty($model->id)) {
                $model->id = (string) Str::uuid();
            }
        });
    }

    public function getKey(string $requestType = 'client')
    {
        $isProduction = $this->midtrans_is_production;
        $useNovatix = $this->midtrans_use_novatix;

        $nullValue = Crypt::encryptString('');
        $clientKey = Crypt::decryptString($isProduction ? ($this->midtrans_client_key ?? $nullValue) : ($this->midtrans_client_key_sb ?? $nullValue));
        $serverKey = Crypt::decryptString($isProduction ? ($this->midtrans_server_key ?? $nullValue) : ($this->midtrans_server_key_sb ?? $nullValue));

        $configKey = $isProduction
            ? config('midtrans.' . ($requestType === 'client' ? 'client_key' : 'server_key'))
            : config('midtrans.' . ($requestType === 'client' ? 'client_key_sb' : 'server_key_sb'));

        $returnVal = $requestType === 'client'
            ? (!empty($clientKey) ? $clientKey : ($useNovatix ? $configKey : null))
            : (!empty($serverKey) ? $serverKey : ($useNovatix ? $configKey : null));

        return $returnVal;
    }

    public function reconstructImgLinks()
    {
        $columns = ['logo', 'texture', 'favicon'];
        foreach ($columns as $column) {
            if (isset($this->{$column}) && !empty($this->{$column}) && !Str::startsWith($this->{$column}, '/')) {
                $this->{$column} = '/storage/' . $this->{$column};
            }
        }
    }

    public function getSecure()
    {
        return [
            'ticket_limit' => $this->ticket_limit,
            'is_locked' => $this->is_locked,
            'is_maintenance' => $this->is_maintenance,
            'maintenance_title' => $this->maintenance_title,
            'maintenance_message' => $this->maintenance_message,
            'maintenance_expected_finish' => $this->maintenance_expected_finish,
            'logo' => $this->logo,
            'logo_alt' => $this->logo_alt,
            'texture' => $this->texture,
            'favicon' => $this->favicon,
            'primary_color' => $this->primary_color,
            'secondary_color' => $this->secondary_color,
            'text_primary_color' => $this->text_primary_color,
            'text_secondary_color' => $this->text_secondary_color,
            'terms_and_conditions' => $this->terms_and_conditions,
            'privacy_policy' => $this->privacy_policy,
            'contact_person' => $this->contact_person
        ];
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class, 'event_id', 'id');
    }
}
