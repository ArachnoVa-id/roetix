<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Traffic extends Model
{
    use HasFactory;

    protected $table = 'traffic';

    protected $fillable = [
        'user_id',
        'start_login',
        'end_login',
        'stop_at',
    ];

    public $timestamps = true;

    protected $casts = [
        'start_login' => 'datetime:H:i:s',
        'end_login'   => 'datetime:H:i:s',
        'stop_at'     => 'datetime:H:i:s',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
