<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\Order;
use App\Models\User;
use App\Models\Ticket;
use Inertia\Inertia;

class EoTicketController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $data = Order::query()
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

        return $data;
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
