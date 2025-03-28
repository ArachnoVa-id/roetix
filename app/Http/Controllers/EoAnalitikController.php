<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\User;
use App\Models\Ticket;
use Inertia\Inertia;

use Illuminate\Http\Request;

use function Laravel\Prompts\select;

class EoAnalitikController extends Controller
{
    public function analitikpenjualan()
    {
        $data = Order::query()
            ->join('users', 'users.id', '=', 'orders.id')
            ->select([
                'orders.order_id',
                'orders.order_date',
                'orders.status',
                'orders.total_price',
                'orders.ticket_id',
                'orders.created_at',
            ])
            ->latest()
            ->get();

        // return $data;

        $total_price = number_format($data->sum('total_price'), 2);

        return Inertia::render('EventOrganizer/EoAnalitik/Index', [
            'title' => 'Penjualan',
            'subtitle' => 'Terbeli',
            'orders' => $data,
            'total' => $total_price,
        ]);
    }

    public function riwayatacara()
    {
        return Inertia::render('EventOrganizer/EoAnalitik/Riwayat', [
            'title' => 'Riwayat',
            'subtitle' => 'Acara',
        ]);
    }

    public function penjualan($orderId)
    {
        $orders = Order::where('orders.order_id', $orderId)
            ->join('users', 'users.id', '=', 'orders.id')
            ->select([
                'users.email',
                'users.first_name',
                'users.last_name',
                'orders.ticket_id',
                'orders.order_date',
                'orders.total_price',
            ])
            ->get();

        $orders->transform(function ($order) {
            $order->ticket_id = json_decode($order->ticket_id, true);
            return $order;
        });

        $ticketIds = $orders->pluck('ticket_id')->flatten()->unique()->toArray();

        $tickets = Ticket::whereIn('ticket_id', $ticketIds)
            ->join('events', 'events.event_id', '=', 'tickets.event_id')
            ->join('seats', 'seats.seat_id', '=', 'tickets.seat_id')
            ->select([
                'tickets.ticket_id',
                'tickets.ticket_type',
                'tickets.price',
                'seats.seat_number',
                'seats.status',
                'events.name',
                'events.location',
            ])
            ->get();

        // return $tickets;

        return Inertia::render('EventOrganizer/EoAnalitik/Penjualan', [
            'orderId' => $orderId,
            'orders' => $orders,
            'tickets' => $tickets,
        ]);
    }
}
