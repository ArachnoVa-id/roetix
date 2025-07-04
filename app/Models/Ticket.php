<?php

namespace App\Models;

use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;
use Illuminate\Notifications\Notifiable;


class Ticket extends Model
{
    use Notifiable;

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

    protected $with = [
        'team',
        'ticketOrders',
        'ticketOrders.order',
        'ticketOrders.order.user',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->id)) {
                $model->id = (string) Str::uuid();
            }

            if (empty($model->ticket_code)) {
                // Retrieve event if available
                if (!empty($model->event_id)) {
                    $event = Event::find($model->event_id);

                    if ($event) {
                        $model->team_id = $event->team_id;

                        $initials = '';
                        // Get first 8 characters of the event name
                        if (isset($event->name)) {
                            $initials = preg_replace('/[^A-Za-z0-9]/', '', substr($event->name, 0, 8));
                        }

                        // Ensure initials are 8 characters long and pad with random alphanumeric characters if necessary
                        $firstPart = '';
                        if (strlen($initials) < 8) {
                            $firstPart = str_pad($initials, 8, Str::random(4));
                        } elseif (strlen($initials) > 8) {
                            $firstPart = substr($initials, 0, 8);
                        }

                        // Generate the remaining parts of the ticket code
                        $secondPart = Str::random(8);
                        $thirdPart = Str::random(8);
                        $fourthPart = Str::random(8);

                        // Combine all parts into the final ticket code
                        $model->ticket_code = "{$firstPart}-{$secondPart}-{$thirdPart}-{$fourthPart}";
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
        $qrCode = base64_encode($writer->writeString($this->ticket_code));

        return $qrCode;
    }

    public function getQRCodeUrl()
    {
        // Using QR Server API (free service)
        $baseUrl = 'https://api.qrserver.com/v1/create-qr-code/';

        $params = [
            'size' => '300x300',
            'data' => $this->ticket_code,
            'format' => 'png',
            'margin' => '10',
            'qzone' => '1',
            'bgcolor' => 'ffffff',
            'color' => '000000'
        ];

        $queryString = http_build_query($params);
        $qrCodeUrl = $baseUrl . '?' . $queryString;

        return $qrCodeUrl;
    }

    // Alternative function using QuickChart API
    public function getQRCodeUrlQuickChart()
    {
        $baseUrl = 'https://quickchart.io/qr';

        $params = [
            'text' => $this->ticket_code,
            'size' => '300',
            'format' => 'png',
            'margin' => '10'
        ];

        $queryString = http_build_query($params);
        $qrCodeUrl = $baseUrl . '?' . $queryString;

        return $qrCodeUrl;
    }

    // Alternative function using QRCode Monkey API (requires API key for commercial use)
    public function getQRCodeUrlMonkey()
    {
        $baseUrl = 'https://api.qrcode-monkey.com/qr/custom';

        $data = [
            'data' => $this->ticket_code,
            'config' => [
                'body' => 'square',
                'eye' => 'frame0',
                'eyeBall' => 'ball0',
                'erf1' => [],
                'erf2' => [],
                'erf3' => [],
                'brf1' => [],
                'brf2' => [],
                'brf3' => [],
                'bodyColor' => '#000000',
                'bgColor' => '#FFFFFF',
                'eye1Color' => '#000000',
                'eye2Color' => '#000000',
                'eye3Color' => '#000000',
                'eyeBall1Color' => '#000000',
                'eyeBall2Color' => '#000000',
                'eyeBall3Color' => '#000000',
                'gradientColor1' => '',
                'gradientColor2' => '',
                'gradientType' => 'linear',
                'gradientOnEyes' => 'true',
                'logo' => '',
                'logoMode' => 'default'
            ],
            'size' => 300,
            'download' => 'imageUrl',
            'file' => 'png'
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $baseUrl);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Accept: application/json'
        ]);

        $response = curl_exec($ch);
        curl_close($ch);

        $result = json_decode($response, true);
        return $result['imageUrl'] ?? null;
    }

    public function getColor()
    {
        $category = $this->ticketCategory;
        return $category->color;
    }

    public function getTicketPDFTitle(): string
    {
        $event = $this->event;
        return strtoupper($event->slug) . '-' . $event->eventYear() . '-' . strtoupper($this->id) . '-TICKET' . '.pdf';
    }

    public function getLatestOwner()
    {
        $latestTicketOrder = $this->latestTicketOrder;

        if ($latestTicketOrder && $latestTicketOrder->relationLoaded('order') && $latestTicketOrder->order->relationLoaded('user')) {
            return $latestTicketOrder->order->user->email ?? null;
        }

        return null;
    }

    public function latestTicketOrder(): HasOne
    {
        return $this->hasOne(TicketOrder::class, 'ticket_id', 'id')->latestOfMany();
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class, 'event_id', 'id');
    }

    public function seat(): BelongsTo
    {
        return $this->belongsTo(Seat::class, 'seat_id', 'id');
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class, 'team_id', 'id');
    }

    public function ticketOrders(): HasMany
    {
        return $this->hasMany(TicketOrder::class, 'ticket_id', 'id');
    }

    public function orders(): BelongsToMany
    {
        return $this->belongsToMany(Order::class, 'ticket_order', 'ticket_id', 'order_id');
    }

    public function timelineSessions(): HasMany
    {
        return $this->hasMany(TimelineSession::class, 'event_id', 'id');
    }

    public function ticketCategory(): BelongsTo
    {
        return $this->belongsTo(TicketCategory::class, 'ticket_category_id', 'id');
    }
}
