<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Queue extends Model
{
    // Use the SQLite connection
    protected $connection = 'sqlite';

    // Optionally specify the table name if not "queues"
    // protected $table = 'queues';

    // Define fillable fields for mass assignment
    protected $fillable = [
        'event_id',
        'user_id',
        'online',
    ];

    // Cast attributes to specific types
    protected $casts = [
        'online' => 'boolean',
    ];
}
