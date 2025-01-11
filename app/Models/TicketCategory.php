<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class TicketCategory extends Model
{
    use HasUuids;

    protected $primaryKey = 'ticket_category_id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'event_id',
        'name',
        'color'
    ];

    public function timeboundPrices()
    {
        return $this->hasMany(EventCategoryTimeboundPrice::class, 'ticket_category_id', 'ticket_category_id');
    }

    public function getCurrentPrice()
    {
        return $this->timeboundPrices()
            ->where('start_date', '<=', now())
            ->where('end_date', '>=', now())
            ->first();
    }
}