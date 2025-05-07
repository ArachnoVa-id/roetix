<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class EventLog extends Model
{
    protected $connection = 'sqlite';
    protected $table = 'event_logs';

    protected $fillable = [
        'user_id',
        'event_id',
        'start_login',
        'end_at',
    ];

    public $timestamps = true;
}
