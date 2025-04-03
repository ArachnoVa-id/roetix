<?php

namespace App\Exports;

use App\Models\User;
use App\Models\Event;
use App\Models\Order;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;
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
            $orders = Order::all();  // Get all orders
        } else if (
            !$user->isAdmin() && $this->eventId && !in_array(Event::find($this->eventId)->team_id, $user->teams()->pluck('user_team.team_id')->toArray())
        ) {
            $orders = collect();  // Return empty collection
        } else {
            $orders = Order::where('event_id', $this->eventId)->get();  // Get orders based on eventId
        }

        // Map over the orders and populate the additional fields (Event name, User full name, Team name)
        $data = $orders->map(function ($order) {
            return [
                'order_id' => $order->order_id,
                'order_code' => $order->order_code,
                'event_name' => $order->events ? $order->getSingleEvent()->name : null,  // Populate Event name
                'user_full_name' => $order->user ? $order->user->first_name . ' ' . $order->user->last_name : null,  // Populate User full name
                'team_name' => $order->team ? $order->team->name : null,  // Populate Team name
                'order_date' => $order->order_date,
                'total_price' => $order->total_price,
                'status' => $order->status,
                'created_at' => $order->created_at,
                'updated_at' => $order->updated_at,
            ];
        });

        return $data;
    }

    public function headings(): array
    {
        return [
            'Order ID',
            'Order Code',
            'Event Name',
            'User Full Name',
            'Team Name',
            'Order Date',
            'Total Price',
            'Status',
            'Created At',
            'Updated At',
        ];
    }

    public function title(): string
    {
        $user = session('userProps');

        if ($user->isAdmin() && empty($this->eventId)) {
            return 'NovaTix: All Orders';
        } else {
            $event = Event::find($this->eventId);
            $slug = Str::slug($event->name);
            return 'NovaTix_' .  $slug . '_Orders';
        }
    }
}
