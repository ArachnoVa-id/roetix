<?php

namespace App\Http\Controllers;
use App\Models\Order;
use App\Models\User;
use App\Models\Ticket;
use Inertia\Inertia;

use Illuminate\Http\Request;

class EoAnalitikController extends Controller
{
    public function analitikpenjualan()
    {
        $data = Order::query()
        ->join('users', 'users.user_id', '=', 'orders.user_id')
        ->join('tickets', 'tickets.ticket_id', '=', 'orders.ticket_id')
        ->join('seats', 'seats.seat_id', '=', 'tickets.seat_id')
        ->join('venues', 'venues.venue_id', '=', 'seats.venue_id')
        ->select([
            'orders.ticket_id',
            'seats.seat_number',
            'venues.name',
            'orders.order_date',
            'orders.status',
            'orders.total_price',
            'users.email',
            'tickets.ticket_type',
            'orders.created_at',
        ])
        ->latest()
        ->get();
        $total_price = $data->sum('total_price');

        return Inertia::render('EventOrganizer/EoAnalitik/Index', [
            'title' => 'Penjualan',
            'subtitle' => 'Terbeli',
            'tickets' => $data,
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
}
