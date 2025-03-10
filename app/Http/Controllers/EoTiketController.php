<?php

namespace App\Http\Controllers;

use App\Models\Seat;
use App\Models\Event;
use App\Models\Ticket;
use App\Models\Venue;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class EoTiketController extends Controller
{
    public function pengaturan()
    {
        return Inertia::render('EventOrganizer/EoTiket/PengaturanTiker', [
            'title' => 'Tiket',
            'subtitle' => 'Pengatauran',
        ]);
    }

    public function harga()
    {
        return Inertia::render('EventOrganizer/EoTiket/PengaturanTiker', [
            'title' => 'Tiket',
            'subtitle' => 'harga',
        ]);
    }

    public function verifikasi()
    {
        return Inertia::render('EventOrganizer/EoTiket/PengaturanTiker', [
            'title' => 'Tiket',
            'subtitle' => 'verivikasi',
        ]);
    }

    public function show(Request $request, $eventId = null)
    {
        try {
            // Use provided event_id or default to the one in the request
            $eventId = $eventId ?: $request->event_id;

            // Sample event_id if none provided
            if (!$eventId) {
                $eventId = '181c1c9e-d4af-4a64-b056-8b3f3adca688';
            }

            // Get the event and associated venue
            $event = Event::findOrFail($eventId);
            $venue = Venue::findOrFail($event->venue_id);

            // Get all tickets for this event
            $tickets = Ticket::where('event_id', $eventId)
                ->get();

            // Get all seats for this venue to establish the layout
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
                        // Fallback for seats without tickets (shouldn't happen with your implementation)
                        return [
                            'type' => 'seat',
                            'seat_id' => $seat->seat_id,
                            'seat_number' => $seat->seat_number,
                            'row' => $seat->row,
                            'column' => $seat->column,
                            'status' => 'not_available',
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
                $ticketTypes = ['standard', 'VIP'];
            }

            return Inertia::render('User/MyTickets', [
                'layout' => $layout,
                'event' => $event,
                'venue' => $venue,
                'ticketTypes' => $ticketTypes
            ]);
        } catch (\Exception $e) {
            Log::error('Error in show method: ' . $e->getMessage());
            return redirect()->back()->withErrors(['error' => 'Failed to load event tickets: ' . $e->getMessage()]);
        }
    }
}
