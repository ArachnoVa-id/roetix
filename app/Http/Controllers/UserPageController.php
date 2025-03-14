<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\EventVariables;
use App\Models\Ticket;
use App\Models\Seat;
use App\Models\Venue;
use App\Models\TimelineSession;
use App\Models\TicketCategory;;

use App\Models\EventCategoryTimeboundPrice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class UserPageController extends Controller
{
    public function landing(Request $request, string $client = '')
    {
        // Get the event and associated venue
        $event = Event::where('slug', $client)
            ->first();

        if (!$event) {
            return Inertia::render('User/Landing', [
                'client' => $client,
                'error' => 'Event not found.',
                'props' => new \stdClass() // Empty props as fallback
            ]);
        }

        $props = EventVariables::findOrFail($event->event_variables_id);

        try {
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
                            'category' => $ticket->ticket_type, // Map ticket_type to category for display
                            'ticket_category_id' => $ticket->ticket_category_id,
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

            // Get ticket categories for this specific event
            $ticketCategories = TicketCategory::where('event_id', $event->event_id)->get();

            // If no ticket categories found, create default ones
            if ($ticketCategories->isEmpty()) {
                $ticketCategories = collect([
                    (object)['ticket_category_id' => 'standard', 'name' => 'standard', 'color' => '#4AEDC4'],
                    (object)['ticket_category_id' => 'vip', 'name' => 'VIP', 'color' => '#F9A825']
                ]);
            }

            // Find current timeline based on current date
            $currentDate = Carbon::now();
            $currentTimeline = TimelineSession::where('event_id', $event->event_id)
                ->where('start_date', '<=', $currentDate)
                ->where('end_date', '>=', $currentDate)
                ->first();

            // If no current timeline found, get the first upcoming one
            if (!$currentTimeline) {
                $currentTimeline = TimelineSession::where('event_id', $event->event_id)
                    ->where('start_date', '>', $currentDate)
                    ->orderBy('start_date', 'asc')
                    ->first();
            }

            // If still no timeline found, get the most recent past one
            if (!$currentTimeline) {
                $currentTimeline = TimelineSession::where('event_id', $event->event_id)
                    ->where('end_date', '<', $currentDate)
                    ->orderBy('end_date', 'desc')
                    ->first();
            }

            // Get category prices for the current timeline
            $categoryPrices = [];
            if ($currentTimeline) {
                $categoryPrices = EventCategoryTimeboundPrice::where('timeline_id', $currentTimeline->timeline_id)
                    ->get();
            }

            return Inertia::render('User/Landing', [
                'client' => $client,
                'layout' => $layout,
                'event' => $event,
                'venue' => $venue,
                'ticketCategories' => $ticketCategories,
                'currentTimeline' => $currentTimeline, // Only passing the current timeline, not all
                'categoryPrices' => $categoryPrices,
                'props' => $props
            ]);
        } catch (\Exception $e) {
            Log::error('Error in landing method: ' . $e->getMessage());
            return Inertia::render('User/Landing', [
                'client' => $client,
                'error' => 'Failed to load event data: ' . $e->getMessage(),
                'props' => $props
            ]);
        }
    }

    public function my_tickets(string $client = '')
    {
        // Get the event and associated venue
        $event = Event::where('slug', $client)
            ->first();

        if (!$event) {
            return redirect()->route('login');
        }

        $props = EventVariables::findOrFail($event->event_variables_id);

        if (Auth::check()) {
            try {
                // Get user tickets
                $userTickets = Ticket::where('event_id', $event->event_id)
                    ->where('user_id', Auth::id())
                    ->with(['seat'])
                    ->get();

                return Inertia::render('User/MyTickets', [
                    'client' => $client,
                    'props' => $props,
                    'tickets' => $userTickets,
                    'event' => $event
                ]);
            } catch (\Exception $e) {
                Log::error('Error in my_tickets method: ' . $e->getMessage());
                // throw back to login page
                return Inertia::render('User/Auth', [
                    'client' => $client,
                    'error' => 'Failed to load ticket data: ' . $e->getMessage(),
                    'props' => $props
                ]);
            }
        } else {
            // Redirect to login if not authenticated
            return redirect()->route('login', ['client' => $client]);
        }
    }
}
