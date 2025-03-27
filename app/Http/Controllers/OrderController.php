<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Venue;
use Illuminate\Http\Request;
use Inertia\Inertia;

class OrderController extends Controller
{
    /**
     * Tampilkan semua data order.
     */
    public function index()
    {
        // $data = Venue::query()
        // ->join('user_contacts', 'contact_id', '=', 'venues.contact_info')
        // ->select([
        //     'venues.venue_id',
        //     'venues.name',
        //     'venues.location',
        //     'venues.capacity',
        //     'venues.status',
        //     'user_contacts.phone_number',
        //     'user_contacts.email',
        //     'user_contacts.whatsapp_number',
        //     'user_contacts.instagram',
        //     'venues.created_at',
        // ])
        // ->latest()
        // ->get();

        $data = Venue::with('contactInfo')->get();

        return $data;
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
            'id' => 'required|exists:users,id',
            'evemt_id' => 'required|exists:events,event_id',
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
            'id' => 'required|exists:users,id',
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
