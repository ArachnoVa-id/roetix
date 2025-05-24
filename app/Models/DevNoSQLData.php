<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class DevNoSQLData extends Model
{
    protected $table = 'dev_nosql_data';

    public $incrementing = false; // UUIDs are not auto-incrementing
    protected $keyType = 'string'; // UUID is a string key

    protected $fillable = [
        'id',
        'collection',
        'data',
    ];

    protected $casts = [
        'data' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();

        // Automatically generate UUID for primary key if not set
        static::creating(function ($model) {
            if (empty($model->id)) {
                $model->id = (string) Str::uuid();
            }
        });
    }
}
