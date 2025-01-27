<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\Request;
use Inertia\Inertia;

class OrderController extends Controller
{
    /**
     * Tampilkan semua data order.
     */
    public function index()
    {
        $orders = Order::query()
        ->join('users', 'users.user_id', '=', 'orders.user_id')
        ->join('tickets', 'tickets.ticket_id', '=', 'orders.ticket_id')
        ->select([
            'orders.ticket_id',
            'orders.order_date',
            'orders.status',
            'orders.total_price',
            'users.email',
            'tickets.ticket_type',
            'orders.created_at'
        ])
        ->latest()
        ->get();

        // return $orders;

        return Inertia::render('Orders/Index', [
            'orders' => $orders,
        ]);
    }

    /**
     * Tampilkan form untuk membuat order baru.
     */
    public function create()
    {
        return Inertia::render('Orders/Create');
    }

    /**
     * Simpan order baru ke database.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,user_id',
            'ticket_id' => 'required|exists:tickets,ticket_id',
            'order_date' => 'required|date',
            'total_price' => 'required|numeric|min:0',
            'status' => 'required|in:pending,completed,cancelled',
        ]);

        Order::create($validated);

        return redirect()->route('orders.index')->with('success', 'Order berhasil dibuat.');
    }

    /**
     * Tampilkan form untuk mengedit order.
     */
    public function edit(Order $order)
    {
        return Inertia::render('Orders/Edit', [
            'order' => $order->load(['user', 'ticket']),
        ]);
    }

    /**
     * Update data order di database.
     */
    public function update(Request $request, Order $order)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,user_id',
            'ticket_id' => 'required|exists:tickets,ticket_id',
            'order_date' => 'required|date',
            'total_price' => 'required|numeric|min:0',
            'status' => 'required|in:pending,completed,cancelled',
        ]);

        $order->update($validated);

        return redirect()->route('orders.index')->with('success', 'Order berhasil diperbarui.');
    }

    /**
     * Hapus order dari database.
     */
    public function destroy(Order $order)
    {
        $order->delete();

        return redirect()->route('orders.index')->with('success', 'Order berhasil dihapus.');
    }
}
