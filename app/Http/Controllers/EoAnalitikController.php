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
        ->select([
            'orders.order_id',
            'orders.order_date',
            'orders.status',
            'orders.total_price',
            'users.email',
            'orders.created_at',
        ])
        ->latest()
        ->get();

        $total_price = number_format($data->sum('total_price'), 2);

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
