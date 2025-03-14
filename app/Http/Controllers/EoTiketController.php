<?php

namespace App\Http\Controllers;

use App\Models\Seat;
use App\Models\Event;
use App\Models\Ticket;
use App\Models\Venue;
use App\Models\TicketCategory;
use App\Models\TimelineSession;
use App\Models\EventCategoryTimeboundPrice;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

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
            // If eventId is not provided, try to get it from the request
            if (!$eventId) {
                $eventId = $request->event_id;
            }

            if (!$eventId) {
                return Inertia::render('Landing', [
                    'client' => $request->client,
                    'error' => 'Event ID is required',
                    'props' => $this->getDefaultProperties()
                ]);
            }

            // Get event and venue information
            $event = Event::findOrFail($eventId);
            $venue = Venue::findOrFail($event->venue_id);

            // Get all seats for this venue
            $seats = Seat::where('venue_id', $venue->venue_id)
                ->orderBy('row')
                ->orderBy('column')
                ->get();

            // Get tickets for this event
            $tickets = Ticket::where('event_id', $eventId)->get()->keyBy('seat_id');

            // Get ticket categories for this event
            $ticketCategories = TicketCategory::where('event_id', $eventId)->get();

            // Get current timeline session (the one we're currently in based on date)
            $currentDate = Carbon::now();
            $currentTimeline = TimelineSession::where('event_id', $eventId)
                ->where('start_date', '<=', $currentDate)
                ->where('end_date', '>=', $currentDate)
                ->first();

            // If no current timeline found, get the first upcoming one
            if (!$currentTimeline) {
                $currentTimeline = TimelineSession::where('event_id', $eventId)
                    ->where('start_date', '>', $currentDate)
                    ->orderBy('start_date', 'asc')
                    ->first();
            }

            // If still no timeline found, get the most recent past one
            if (!$currentTimeline) {
                $currentTimeline = TimelineSession::where('event_id', $eventId)
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

            // Create layout data for the frontend
            $layout = [
                'totalRows' => count(array_unique($seats->pluck('row')->toArray())),
                'totalColumns' => $seats->max('column'),
                'items' => $seats->map(function ($seat) use ($tickets, $ticketCategories, $currentTimeline, $categoryPrices) {
                    $ticket = $tickets->get($seat->seat_id);

                    // Base seat data
                    $seatData = [
                        'type' => 'seat',
                        'seat_id' => $seat->seat_id,
                        'seat_number' => $seat->seat_number,
                        'row' => $seat->row,
                        'column' => $seat->column
                    ];

                    // Add ticket data if it exists
                    if ($ticket) {
                        $seatData['status'] = $ticket->status;

                        // Use linked ticket category if available
                        if ($ticket->ticket_category_id) {
                            $category = $ticketCategories->firstWhere('ticket_category_id', $ticket->ticket_category_id);
                            if ($category) {
                                $seatData['ticket_type'] = $category->name;

                                // Use price from timebound prices if available and we have a current timeline
                                if ($currentTimeline) {
                                    $price = $categoryPrices->first(function ($p) use ($ticket, $currentTimeline) {
                                        return $p->ticket_category_id === $ticket->ticket_category_id &&
                                            $p->timeline_id === $currentTimeline->timeline_id;
                                    });

                                    if ($price) {
                                        $seatData['price'] = $price->price;
                                    } else {
                                        $seatData['price'] = $ticket->price ?? 0;
                                    }
                                } else {
                                    $seatData['price'] = $ticket->price ?? 0;
                                }
                            } else {
                                // Fallback to stored ticket type
                                $seatData['ticket_type'] = $ticket->ticket_type ?? 'standard';
                                $seatData['price'] = $ticket->price ?? 0;
                            }
                        } else {
                            // Fallback to stored ticket type
                            $seatData['ticket_type'] = $ticket->ticket_type ?? 'standard';
                            $seatData['price'] = $ticket->price ?? 0;
                        }
                    } else {
                        // Default values for seats without tickets
                        $seatData['status'] = 'reserved';
                        $seatData['ticket_type'] = 'standard';
                        $seatData['price'] = 0;
                    }

                    return $seatData;
                })->values()
            ];

            // Add stage label
            $layout['items'][] = [
                'type' => 'label',
                'row' => $layout['totalRows'],
                'column' => floor($layout['totalColumns'] / 2),
                'text' => 'STAGE'
            ];

            return Inertia::render('Landing', [
                'client' => $request->client,
                'layout' => $layout,
                'event' => $event,
                'venue' => $venue,
                'ticketCategories' => $ticketCategories,
                'currentTimeline' => $currentTimeline, // Only pass the current timeline
                'categoryPrices' => $categoryPrices,
                'props' => $this->getDefaultProperties()
            ]);
        } catch (\Exception $e) {
            Log::error('Error in EoTiketController@show: ' . $e->getMessage());
            Log::error($e->getTraceAsString());

            return Inertia::render('Landing', [
                'client' => $request->client,
                'error' => 'Failed to load event: ' . $e->getMessage(),
                'props' => $this->getDefaultProperties()
            ]);
        }
    }

    private function getDefaultProperties()
    {
        // Default theme properties
        return [
            'primary_color' => '#ffffff',
            'secondary_color' => '#f3f4f6',
            'text_primary_color' => '#111827',
            'text_secondary_color' => '#4b5563'
        ];
    }
}
