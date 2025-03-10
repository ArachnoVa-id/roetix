<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\Ticket;
use App\Models\Seat;
use App\Models\Venue;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Illuminate\Support\Facades\Log;

class UserPageController extends Controller
{
    public function landing(Request $request, string $client = '')
    {
        if (Auth::check()) {
            try {
                // Get the event and associated venue
                $event = Event::where('slug', $client)
                    ->first();
                // $event = Event::where('event_id', '')
                //     ->first();
                $venue = Venue::findOrFail($event->venue_id);

                // Get all tickets for this event
                $tickets = Ticket::where('event_id', $event->event_id)
                    ->get();

                // Get all seats for this venue
                $seats = Seat::where('venue_id', $venue->venue_id)
                    ->orderBy('row')
                    ->orderBy('column')
                    ->get();

                // Create a map of tickets by seat_id for easy lookup
                $ticketsBySeatId = $tickets->keyBy('seat_id');

                // Format data for the frontend
                $layout = [
                    'totalRows' => count(array_unique($seats->pluck('row')->toArray())),
                    'totalColumns' => $seats->max('column'),
                    'items' => $seats->map(function ($seat) use ($ticketsBySeatId) {
                        $ticket = $ticketsBySeatId->get($seat->seat_id);

                        if ($ticket) {
                            return [
                                'type' => 'seat',
                                'seat_id' => $seat->seat_id,
                                'seat_number' => $seat->seat_number,
                                'row' => $seat->row,
                                'column' => $seat->column,
                                'status' => $ticket->status,
                                'ticket_type' => $ticket->ticket_type,
                                'price' => $ticket->price,
                                'category' => $ticket->ticket_type // Map ticket_type to category for display
                            ];
                        } else {
                            // Fallback for seats without tickets
                            return [
                                'type' => 'seat',
                                'seat_id' => $seat->seat_id,
                                'seat_number' => $seat->seat_number,
                                'row' => $seat->row,
                                'column' => $seat->column,
                                'status' => 'reserved',
                                'ticket_type' => 'standard',
                                'price' => 0,
                                'category' => 'standard'
                            ];
                        }
                    })->values()
                ];

                // Add stage label
                $layout['items'][] = [
                    'type' => 'label',
                    'row' => $layout['totalRows'],
                    'column' => floor($layout['totalColumns'] / 2),
                    'text' => 'STAGE'
                ];

                // Get available ticket types from data
                $ticketTypes = $tickets->pluck('ticket_type')->unique()->values()->all();

                // If no ticket types found, provide defaults
                if (empty($ticketTypes)) {
                    $ticketTypes = ['standard', 'VIP', 'VVIP', 'Regular'];
                }

                return Inertia::render('User/Landing', [
                    'client' => $client,
                    'layout' => $layout,
                    'event' => $event,
                    'venue' => $venue,
                    'ticketTypes' => $ticketTypes
                ]);
            } catch (\Exception $e) {
                Log::error('Error in landing method: ' . $e->getMessage());
                return Inertia::render('User/Landing', [
                    'client' => $client,
                    'error' => 'Failed to load event data: ' . $e->getMessage()
                ]);
            }
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
