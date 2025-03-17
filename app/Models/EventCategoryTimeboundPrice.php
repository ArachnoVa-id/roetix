<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class EventCategoryTimeboundPrice extends Model
{
    use HasFactory, HasUuids;
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'ticket_category_id',
        'timeline_id',
        'price',
        'is_active',
    ];
    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'price' => 'decimal:2',
    ];

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->timebound_price_id)) {
                $model->timebound_price_id = (string) Str::uuid();
            }
        });
    }

    /**
     * Get the ticket category that owns the timebound price.
     */
    public function ticketCategories(): BelongsTo
    {
        return $this->belongsTo(TicketCategory::class, 'ticket_category_id', 'ticket_category_id');
    }

    /**
     * Get the timeline session that owns the timebound price.
     */
    // public function timelineSession(): BelongsTo
    // {
    //     return $this->belongsTo(TimelineSession::class, 'timeline_id', 'timeline_id');
    // }

    /**
     * Indicates if the model's ID is auto-incrementing.
     *
     * @var bool
     */
    public $incrementing = false;

    /**
     * The data type of the auto-incrementing ID.
     *
     * @var string
     */
    protected $keyType = 'string';

    /**
     * The primary key for the model.
     *
     * @var string
     */
    protected $primaryKey = 'timebound_price_id';
}
