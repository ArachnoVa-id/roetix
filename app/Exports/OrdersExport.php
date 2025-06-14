<?php

namespace App\Exports;

use App\Models\DevNoSQLData;
use App\Models\User;
use App\Models\Event;
use App\Models\Order;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\FromCollection;

class OrdersExport implements FromCollection, WithHeadings, WithTitle
{
    protected $eventId;
    protected $user;

    // Constructor to accept the ID
    public function __construct($id, $user)
    {
        $this->eventId = $id;
        $this->user = $user;
    }

    public function collection()
    {
        $user = User::find($this->user->id);
        $data = null;

        // Check if the user is admin and if eventId is not provided
        if ($user->isAdmin() && empty($this->eventId)) {
            $orders = Order::with(['ticketOrders.ticket.seat'])->get();  // Get all orders with seat relation
        } else if (
            !$user->isAdmin() && $this->eventId && !in_array(Event::find($this->eventId)->team_id, $user->teams()->pluck('user_team.team_id')->toArray())
        ) {
            $orders = collect();  // Return empty collection
        } else {
            $orders = Order::with(['ticketOrders.ticket.seat'])->where('event_id', $this->eventId)->get();  // Get orders based on eventId with seat relation
        }

        // Map over the orders and populate the additional fields
        $data = $orders->map(function ($order) {
            // Get seat numbers for this order
            $seatNumbers = $order->ticketOrders->map(function ($ticketOrder) {
                return $ticketOrder->ticket && $ticketOrder->ticket->seat
                    ? $ticketOrder->ticket->seat->seat_number
                    : 'N/A';
            })->filter(function ($seatNumber) {
                return $seatNumber !== 'N/A';
            })->unique()->implode(', ');

            // Count total tickets for this order
            $totalTickets = $order->ticketOrders->count();

            $body = [
                'order_id' => $order->id,
                'order_code' => $order->order_code,
                'order_date' => $order->order_date,
                'status' => $order->status,
                'event_name' => $order->events ? $order->getSingleEvent()->name : null,  // Populate Event name
                'team_name' => $order->team ? $order->team->name : null,  // Populate Team name
                'user_email' => null,  // Will be populated from NoSQL data
                'user_email_name' => $order->user ? $order->user->getFilamentName() : null,  // User's email name (from user table)
                'user_full_name' => null,  // Will be populated from NoSQL data (real full name)
                'user_id_no' => null,  // Will be populated from NoSQL data
                'user_phone_num' => null,  // Will be populated from NoSQL data
                'user_address' => null,  // Will be populated from NoSQL data
                'user_sizes' => null,  // Will be populated from NoSQL data
                'seat_numbers' => $seatNumbers ?: 'N/A',  // Seat numbers separated by comma
                'jumlah_ticket' => $totalTickets,  // Total number of tickets
                'total_price' => $order->total_price,
                'created_at' => $order->created_at ? $order->created_at->format('Y-m-d H:i:s') : null,
                'updated_at' => $order->updated_at ? $order->updated_at->format('Y-m-d H:i:s') : null,
            ];

            // Link to DevNoSQL by order accessor
            $accessor = $order->accessor;

            // Find if there's DevNoSQL with collection
            $devNoSQLData = DevNoSQLData::where('collection', 'roetixUserData')
                ->where('data->accessor', $accessor)
                ->first();

            // If found, split the data and add to the body
            if ($devNoSQLData) {
                // Remove json_decode() since $devNoSQLData->data is already an array
                $noSQLData = $devNoSQLData->data;

                $body['user_email'] = $noSQLData['user_email'] ?? null;
                $body['user_full_name'] = $noSQLData['user_full_name'] ?? null;  // Real full name from NoSQL
                $body['user_id_no'] = $noSQLData['user_id_no'] ?? null;
                $body['user_sizes'] = isset($noSQLData['user_sizes']) ? implode(', ', $noSQLData['user_sizes']) : null;
                $body['user_address'] = $noSQLData['user_address'] ?? null;
                $body['user_phone_num'] = $noSQLData['user_phone_num'] ?? null;
            }

            return $body;
        });

        return $data;
    }

    public function headings(): array
    {
        return [
            'Order ID',
            'Order Code',
            'Order Date',
            'Status',
            'Event Name',
            'Team Name',
            'User Email',
            'User Email Name',
            'User Full Name',
            'User ID No',
            'User Phone Number',
            'User Address',
            'User Sizes',
            'Seat Numbers',
            'Jumlah Ticket',
            'Total Price',
            'Created At',
            'Updated At',
        ];
    }

    public function title(): string
    {
        $user = session('auth_user');

        if ($user->isAdmin() && empty($this->eventId)) {
            return 'NovaTix: All Orders';
        } else {
            $event = Event::find($this->eventId);
            $slug = Str::slug($event->name);
            return 'NovaTix_' .  $slug . '_Orders';
        }
    }
}
