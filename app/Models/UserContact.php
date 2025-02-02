<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;

class UserContact extends Model
{
    /** @use HasFactory<\Database\Factories\UserContactFactory> */
    use HasFactory, Notifiable;

    protected $primaryKey = 'contact_id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->contact_id)) {
                $model->contact_id = (string) Str::uuid();
            }
        });
    }
}
