<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class TransactionLog extends Model
{
    use HasUuids;

    protected $fillable = [
        'transaction_id',
        'seat_id',
        'user_id',
        'action',
        'previous_status',
        'new_status',
        'metadata'
    ];

    protected $casts = [
        'metadata' => 'array'
    ];

    public function transaction()
    {
        return $this->belongsTo(SeatTransaction::class, 'transaction_id', 'transaction_id');
    }
}
