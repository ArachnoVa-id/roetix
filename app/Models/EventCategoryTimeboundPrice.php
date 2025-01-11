<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class EventCategoryTimeboundPrice extends Model
{
    use HasUuids;

    protected $primaryKey = 'timebound_price_id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'ticket_category_id',
        'start_date',
        'end_date',
        'price'
    ];

    protected $casts = [
        'start_date' => 'datetime',
        'end_date' => 'datetime',
        'price' => 'decimal:2'
    ];

    public function ticketCategory()
    {
        return $this->belongsTo(TicketCategory::class, 'ticket_category_id', 'ticket_category_id');
    }
}