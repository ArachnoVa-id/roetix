<?php

namespace App\Models;

use App\Enums\OrderType;
use App\Services\ResendMailer;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;


class Order extends Model
{
    /** @use HasFactory<\Database\Factories\OrderFactory> */
    use HasFactory, Notifiable;

    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'accessor',
        'order_code',
        'event_id',
        'user_id',
        'team_id',
        'order_date',
        'total_price',
        'status',
        'payment_gateway',
        'expired_at',
    ];

    protected $with = [
        'team',
        'user'
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->id)) {
                $model->id = (string) Str::uuid();
            }
        });
    }

    public function getOrderDateTimestamp()
    {
        $months = [
            1 => 'Januari',
            2 => 'Februari',
            3 => 'Maret',
            4 => 'April',
            5 => 'Mei',
            6 => 'Juni',
            7 => 'Juli',
            8 => 'Agustus',
            9 => 'September',
            10 => 'Oktober',
            11 => 'November',
            12 => 'Desember'
        ];

        $order_date = Carbon::parse($this->order_date);
        $day = $order_date->format('j');
        $month = $months[(int)$order_date->format('n')];
        $year = $order_date->format('Y');
        $time = $order_date->format('H:i');

        return "{$day} {$month} {$year}, {$time} WIB";
    }

    public static function keyGen(OrderType $type, Event $event): string
    {
        $eventUniq = '';
        if ($event) {
            $eventName = $event->name;
            $eventName = preg_replace('/\s+/', '', $eventName);
            $eventName = substr($eventName, 0, 3);
            $eventUniq = strtoupper($eventName);
        }

        switch ($type) {
            case OrderType::AUTO:
                return 'ORD-' . $eventUniq . '-' . time() . '-' . rand(1000, 9999);
            case OrderType::MANUAL:
                return 'MAN-' . $eventUniq . '-' . time() . '-' . rand(1000, 9999);
            case OrderType::TRANSFER:
                return 'TRF-' . $eventUniq . '-' .  time() . '-' . rand(1000, 9999);
            default:
                return 'UNK-' . $eventUniq . '-' .  time() . '-' . rand(1000, 9999);
        }
    }

    public function keyGenSelf(OrderType $type): string
    {
        // generate unique id for event parsed from event name
        $event = $this->events()->first();
        return self::keyGen($type, $event);
    }

    public function sendConfirmationEmail(?string $customEmail = null, bool $isTest = false)
    {
        // Load required relationships
        $this->load(['ticketOrders.ticket.seat', 'ticketOrders.ticket.ticketCategory']);

        $user = $this->user;
        $userContact = UserContact::find($user->contact_info);
        $event = $this->getSingleEvent();
        $tickets = $this->getOrderTickets();

        if (!$userContact) {
            throw new \Exception('User contact information not found');
        }

        // Check if the name exist in devnoSQLData
        $devData = $this->devNoSQLUserData();
        if ($devData && isset($devData->data['user_full_name'])) {
            $userContact->fullname = $devData->data['user_full_name'];
        }

        $emailHtml = $this->renderConfirmationEmail($event, $tickets, $user, $userContact);

        $subject = $isTest
            ? "🎫 TEST - Your Tickets are Confirmed - Order #{$this->order_code}"
            : "🎫 Your Tickets are Confirmed - Order #{$this->order_code}";

        $resendMailer = new ResendMailer();

        return $resendMailer->send(
            to: $customEmail ?: $userContact->email,
            subject: $subject,
            html: $emailHtml
        );
    }

    /**
     * Get all tickets for this order
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getOrderTickets()
    {
        $ticketOrders = TicketOrder::where('order_id', $this->id)->get();
        $ticketIds = $ticketOrders->pluck('ticket_id');

        return Ticket::whereIn('id', $ticketIds)->get();
    }

    /**
     * Render the order confirmation email template
     *
     * @param mixed $event
     * @param \Illuminate\Database\Eloquent\Collection $tickets
     * @param User $user
     * @param UserContact $userContact
     * @return string
     */
    public function renderConfirmationEmail($event, $tickets, User $user, UserContact $userContact): string
    {
        return view('emails.order-confirmation', [
            'order' => $this,
            'event' => $event,
            'tickets' => $tickets,
            'user' => $user,
            'userContact' => $userContact
        ])->render();
    }

    /**
     * Create a mock ticket for testing purposes
     *
     * @param int $id
     * @param string $seatNumber
     * @param mixed $ticketCategory
     * @param float $price
     * @return Ticket
     */
    public static function createMockTicket(int $id, string $seatNumber, $ticketCategory, float $price): Ticket
    {
        $ticket = new Ticket();
        $ticket->id = $id;
        $ticket->ticket_code = 'TICK-' . str_pad($id, 6, '0', STR_PAD_LEFT);
        $ticket->ticket_type = $ticketCategory->name;
        $ticket->price = $price;
        $ticket->order_date = now()->format('M d, Y');

        // Mock seat relationship
        $seat = new \stdClass();
        $seat->seat_number = $seatNumber;
        $ticket->setRelation('seat', $seat);

        // Mock ticketCategory relationship
        $ticket->setRelation('ticketCategory', $ticketCategory);

        // Add methods that the blade template expects
        $ticket->getColor = function () {
            return '#667eea'; // Return primary color
        };

        $ticket->getQRCode = function () {
            // Return a simple base64 encoded SVG QR code placeholder
            $qrSvg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100">
                <rect width="100" height="100" fill="white"/>
                <rect x="10" y="10" width="10" height="10" fill="black"/>
                <rect x="30" y="10" width="10" height="10" fill="black"/>
                <rect x="50" y="10" width="10" height="10" fill="black"/>
                <rect x="70" y="10" width="10" height="10" fill="black"/>
                <rect x="10" y="30" width="10" height="10" fill="black"/>
                <rect x="50" y="30" width="10" height="10" fill="black"/>
                <rect x="10" y="50" width="10" height="10" fill="black"/>
                <rect x="30" y="50" width="10" height="10" fill="black"/>
                <rect x="70" y="50" width="10" height="10" fill="black"/>
                <rect x="10" y="70" width="10" height="10" fill="black"/>
                <rect x="30" y="70" width="10" height="10" fill="black"/>
                <rect x="50" y="70" width="10" height="10" fill="black"/>
                <rect x="70" y="70" width="10" height="10" fill="black"/>
                <text x="50" y="95" text-anchor="middle" font-size="8" fill="black">TEST QR</text>
            </svg>';
            return base64_encode($qrSvg);
        };

        return $ticket;
    }

    /**
     * Get the DevNoSQLData for this order's user from roetixUserData collection
     * This matches the accessor field (checkout URL) in Order with the accessor in DevNoSQLData
     */
    public function devNoSQLUserData()
    {
        return DevNoSQLData::where('collection', 'roetixUserData')
            ->where('data->accessor', $this->accessor)
            ->first();
    }

    /**
     * Get user phone number from DevNoSQLData
     */
    public function getUserPhoneAttribute()
    {
        $devData = $this->devNoSQLUserData();
        return $devData?->data['user_phone_num'] ?? null;
    }

    /**
     * Get user full name from DevNoSQLData
     */
    public function getUserFullNameAttribute()
    {
        $devData = $this->devNoSQLUserData();
        return $devData?->data['user_full_name'] ?? null;
    }

    /**
     * Get user email from DevNoSQLData
     */
    public function getUserEmailFromDevDataAttribute()
    {
        $devData = $this->devNoSQLUserData();
        return $devData?->data['user_email'] ?? null;
    }

    /**
     * Get user ID number from DevNoSQLData
     */
    public function getUserIdNoAttribute()
    {
        $devData = $this->devNoSQLUserData();
        return $devData?->data['user_id_no'] ?? null;
    }

    public function tickets(): BelongsToMany
    {
        return $this->belongsToMany(Ticket::class, 'ticket_order', 'order_id', 'ticket_id');
    }

    public function ticketOrders(): HasMany
    {
        return $this->hasMany(TicketOrder::class, 'order_id', 'id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function getSingleEvent()
    {
        return $this->events()->first();
    }

    public function events(): BelongsToMany
    {
        return $this->belongsToMany(Event::class, 'ticket_order', 'order_id', 'event_id');
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class, 'team_id', 'id');
    }
}
