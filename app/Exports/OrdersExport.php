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
            $body = [
                'order_id' => $order->id,
                'order_code' => $order->order_code,
                'event_name' => $order->events ? $order->getSingleEvent()->name : null,  // Populate Event name
                'user_full_name' => $order->user ? $order->user->first_name . ' ' . $order->user->last_name : null,  // Populate User full name
                'team_name' => $order->team ? $order->team->name : null,  // Populate Team name
                'order_date' => $order->order_date,
                'total_price' => $order->total_price,
                'status' => $order->status,
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
                $body['user_id_no'] = $noSQLData['user_id_no'] ?? null;
                $body['user_sizes'] = isset($noSQLData['user_sizes']) ? implode(', ', $noSQLData['user_sizes']) : null;
                $body['user_address'] = $noSQLData['user_address'] ?? null;
                $body['user_phone_num'] = $noSQLData['user_phone_num'] ?? null;
            } else {
                // If not found, set these fields to null
                $body['user_email'] = null;
                $body['user_id_no'] = null;
                $body['user_sizes'] = null;
                $body['user_address'] = null;
                $body['user_phone_num'] = null;
            }

            // Add created_at and updated_at timestamps
            $body['created_at'] = $order->created_at ? $order->created_at->format('Y-m-d H:i:s') : null;
            $body['updated_at'] = $order->updated_at ? $order->updated_at->format('Y-m-d H:i:s') : null;

            return $body;
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
            'User Email',
            'User ID No',
            'User Sizes',
            'User Address',
            'User Phone Number',
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
