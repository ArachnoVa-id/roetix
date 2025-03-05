<?php

namespace App\Http\Controllers;

use App\Models\Seat;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class UserPageController extends Controller
{
    public function landing(string $client = '')
    {
        if (Auth::check()) {
            // Get seats data
            $seats = Seat::orderBy('row')->orderBy('column')->get();

            // Format data for the layout prop
            $layout = [
                'totalRows' => count(array_unique($seats->pluck('row')->toArray())),
                'totalColumns' => $seats->max('column'),
                'items' => $seats->map(function ($seat) {
                    return [
                        'type' => 'seat',
                        'seat_id' => $seat->seat_id,
                        'seat_number' => $seat->seat_number,
                        'row' => $seat->row,
                        'column' => $seat->column,
                        'status' => $seat->status,
                        'category' => $seat->category,
                        'price' => $seat->price
                    ];
                })->values()
            ];

            // Add stage label
            $layout['items'][] = [
                'type' => 'label',
                'row' => $layout['totalRows'],
                'column' => floor($layout['totalColumns'] / 2),
                'text' => 'STAGE'
            ];

            return Inertia::render('User/Landing', [
                'client' => $client,
                'layout' => $layout // Now including the layout prop
            ]);
        } else {
            return Inertia::render('User/Auth', [
                'client' => $client
            ]);
        }
    }

    public function my_tickets(string $client = '')
    {
        return Inertia::render('User/MyTickets', [
            'client' => $client
        ]);
    }
}
