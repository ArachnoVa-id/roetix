<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;

class UserContact extends Model
{
    /** @use HasFactory<\Database\Factories\UserContactFactory> */
    use HasFactory, Notifiable;

    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'nickname',
        'fullname',
        'avatar',
        'phone_number',
        'email',
        'whatsapp_number',
        'instagram',
        'birth_date',
        'gender',
        'address',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->id)) {
                $model->id = (string) Str::uuid();
            }
        });
    }

    public function user(): HasOne
    {
        return $this->hasOne(User::class, 'contact_info', 'id');
    }

    public function venue(): HasOne
    {
        return $this->hasOne(Venue::class, 'contact_info', 'id');
    }
}
