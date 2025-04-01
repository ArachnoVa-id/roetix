<?php

namespace App\Models;

use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;
use Illuminate\Notifications\Notifiable;


class Ticket extends Model
{
    use Notifiable;

    protected $primaryKey = 'ticket_id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'status',
        'event_id',
        'seat_id',
        'ticket_type',
        'ticket_category_id', // Add this if it's missing
        'price',
        'team_id'
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->ticket_id)) {
                $model->ticket_id = (string) Str::uuid();
            }

            if (empty($model->ticket_code)) {
                // Ambil event jika ada
                if (!empty($model->event_id)) {
                    $event = Event::find($model->event_id);

                    if ($event) {
                        $model->team_id = $event->team_id;

                        // Dapatkan kode event dari slug
                        $slug = strtoupper(Str::slug($event->name));
                        $codeEvent = substr($slug, 0, 4);

                        // Jika kurang dari 4 karakter, tambahkan 0
                        $codeEvent = str_pad($codeEvent, 4, '0');

                        // Buat 8 karakter alfanumerik acak
                        $randomCode = Str::random(8);

                        // Kombinasikan
                        $model->ticket_code = $codeEvent . $randomCode;
                    }
                }
            }
        });
    }

    public function getQRCode()
    {
        // Generate QR Code
        $renderer = new ImageRenderer(
            new RendererStyle(300, 0),
            new SvgImageBackEnd()
        );

        $writer = new Writer($renderer);
        $qrCode = base64_encode($writer->writeString($this->ticket_id));

        return $qrCode;
    }

    public function getTicketPDFTitle(): string
    {
        $event = $this->event;
        return strtoupper($event->slug) . '-' . $event->eventYear() . '-' . strtoupper($this->ticket_id) . '-TICKET' . '.pdf';
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class, 'event_id', 'event_id');
    }

    public function seat(): BelongsTo
    {
        return $this->belongsTo(Seat::class, 'seat_id', 'seat_id');
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class, 'team_id', 'team_id');
    }

    public function ticketOrders(): HasMany
    {
        return $this->hasMany(TicketOrder::class, 'ticket_id', 'ticket_id');
    }

    public function orders(): BelongsToMany
    {
        return $this->belongsToMany(Order::class, 'ticket_order', 'ticket_id', 'order_id');
    }

    public function ticketCategory(): BelongsTo
    {
        return $this->belongsTo(TicketCategory::class, 'ticket_category_id', 'ticket_category_id');
    }
}
