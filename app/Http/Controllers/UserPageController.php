<?php

namespace App\Http\Controllers;

use App\Enums\OrderStatus;
use Carbon\Carbon;
use App\Models\Seat;
use Inertia\Inertia;
use App\Models\Order;
use App\Models\Venue;
use App\Models\Ticket;
use Illuminate\Http\Request;
use App\Models\TicketCategory;
use App\Models\TimelineSession;
use App\Enums\TicketOrderStatus;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Models\EventCategoryTimeboundPrice;
use App\Models\User;

class UserPageController extends Controller
{
    public function landing(Request $request, string $client = '')
    {
        $event = $request->get('event');
        $props = $request->get('props');

        try {
            // Get the venue for this event
            $venue = Venue::find($event->venue_id);

            if (!$venue) {
                throw new \Exception('Venue not found for event.');
            }

            // Get all tickets for this event
            $tickets = Ticket::where('event_id', $event->id)
                ->get();

            // Get all seats for this venue
            $seats = Seat::where('venue_id', $venue->id)
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
                    $ticket = $ticketsBySeatId->get($seat->id);

                    if ($ticket) {
                        return [
                            'seat_id' => $seat->id,
                            'seat_number' => $seat->seat_number,
                            'row' => $seat->row,
                            'column' => $seat->column,
                            'status' => $ticket->status,
                            'ticket_type' => $ticket->ticket_type,
                            'price' => $ticket->price,
                            'category' => $ticket->ticket_type,
                            'ticket_category_id' => $ticket->ticket_category_id,
                        ];
                    } else {
                        // Fallback for seats without tickets
                        return [
                            'seat_id' => $seat->id,
                            'seat_number' => $seat->seat_number,
                            'row' => $seat->row,
                            'column' => $seat->column,
                            'status' => 'unset',
                            'ticket_type' => 'unset',
                            'price' => 0,
                            'category' => 'unset',
                            'ticket_category_id' => 'unset',
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
            $ticketCategories = TicketCategory::where('event_id', $event->id)->get();

            // If no ticket categories found, create default ones
            if ($ticketCategories->isEmpty()) {
                $ticketCategories = collect([
                    (object)['ticket_category_id' => 'unset', 'name' => 'Unset', 'color' => '#FFFFFF'],
                ]);
            }

            // Find current timeline based on current date
            $currentDate = Carbon::now();
            $currentTimeline = TimelineSession::where('event_id', $event->id)
                ->where('start_date', '<=', $currentDate)
                ->where('end_date', '>=', $currentDate)
                ->first();

            // If no current timeline found, get the first upcoming one
            if (!$currentTimeline) {
                $currentTimeline = TimelineSession::where('event_id', $event->id)
                    ->where('start_date', '>', $currentDate)
                    ->orderBy('start_date', 'asc')
                    ->first();
            }

            // If still no timeline found, get the most recent past one
            if (!$currentTimeline) {
                $currentTimeline = TimelineSession::where('event_id', $event->id)
                    ->where('end_date', '<', $currentDate)
                    ->orderBy('end_date', 'desc')
                    ->first();
            }

            // Get category prices for the current timeline
            $categoryPrices = [];
            if ($currentTimeline) {
                $categoryPrices = EventCategoryTimeboundPrice::where('timeline_id', $currentTimeline->id)
                    ->get();
            }

            // Get owned ticket count
            $ownedTicketCount = Order::where('user_id', Auth::id())
                ->where('status', OrderStatus::COMPLETED) // Filter orders that are completed
                ->whereHas('tickets', function ($query) use ($event) {
                    $query->where('tickets.event_id', $event->id)
                        ->whereIn('ticket_order.status', [TicketOrderStatus::ENABLED, TicketOrderStatus::SCANNED]); // Count only enabled tickets
                })
                ->count();

            return Inertia::render('User/Landing', [
                'client' => $client,
                'layout' => $layout,
                'event' => [
                    'event_id' => $event->event_id,
                    'name' => $event->name,
                    'date' => $event->date,
                    'event_date' => $event->event_date ?? $event->date,
                    'venue_id' => $event->venue_id,
                    'status' => $event->status,
                    'slug' => $event->slug
                ],
                'venue' => $venue,
                'ticketCategories' => $ticketCategories,
                'currentTimeline' => $currentTimeline,
                'categoryPrices' => $categoryPrices,
                'props' => $props,
                'ownedTicketCount' => $ownedTicketCount,
            ]);
        } catch (\Exception $e) {
            return Inertia::render('User/Landing', [
                'client' => $client,
                'error' => 'Failed to load event data: ' . $e->getMessage(),
                'props' => $props
            ]);
        }
    }

    public function my_tickets(Request $request, string $client = '')
    {
        try {
            $event = $request->get('event');
            $props = $request->get('props');

            // Get order_ids for the logged-in user
            $userOrderIds = Order::where('user_id', Auth::id())
                ->where('event_id', $event->id)
                ->pluck('id')->toArray();

            // Get all ticket categories for this event
            $ticketCategories = TicketCategory::where('event_id', $event->id)
                ->get()
                ->keyBy('ticket_category_id'); // Create a map of categories by ID for lookup

            // Query user tickets with status filtering
            $userTickets = Ticket::select(
                'tickets.*',
                'orders.order_date',
                'orders.status as order_status',
                'ticket_order.status as ticket_order_status',
                'ticket_categories.color as category_color' // Add the category color to the query
            )
                ->join('ticket_order', 'tickets.id', '=', 'ticket_order.ticket_id')
                ->join('orders', 'ticket_order.order_id', '=', 'orders.id')
                ->leftJoin('ticket_categories', 'tickets.ticket_category_id', '=', 'ticket_categories.id') // Left join to get category colors
                ->where('tickets.event_id', $event->id)
                ->whereIn('orders.status', [OrderStatus::COMPLETED])
                ->whereIn('ticket_order.order_id', $userOrderIds)
                ->whereIn('ticket_order.status', [TicketOrderStatus::ENABLED, TicketOrderStatus::SCANNED])
                ->orderBy('ticket_categories.created_at', 'desc')
                ->get();

            // Load seat data for each ticket
            $tickets = [];
            foreach ($userTickets as $ticketData) {
                $ticket = Ticket::with('seat')->find($ticketData->id);
                if ($ticket) {
                    $tickets[] = $ticket;
                    // Attach order_date, ticket_order_status, and category color to ticket object
                    $ticket->order_date = $ticketData->order_date;
                    $ticket->ticket_order_status = $ticketData->ticket_order_status;
                    $ticket->category_color = $ticketData->category_color; // Add the category color

                    // If category color is not available from the join, try to get it from our categories map
                    if (empty($ticket->category_color) && $ticket->ticket_category_id && isset($ticketCategories[$ticket->ticket_category_id])) {
                        $ticket->category_color = $ticketCategories[$ticket->ticket_category_id]->color;
                    }
                }
            }

            // Format tickets for display
            $formattedTickets = collect($tickets)->map(function ($ticket) use ($client) {
                // Use order date or created_at for ticket date
                $ticketDate = property_exists($ticket, 'order_date') && $ticket->order_date
                    ? Carbon::parse($ticket->order_date)
                    : Carbon::parse($ticket->created_at);

                // Determine ticket type
                $typeName = $ticket->ticket_type ? ucfirst($ticket->ticket_type) : 'Unset';

                // Add the ticket status from ticket_order
                $ticketStatus = $ticket->ticket_order_status ?? TicketOrderStatus::ENABLED->value;

                return [
                    'id' => $ticket->id,
                    'type' => $typeName,
                    'code' => $ticket->ticket_code,
                    'qrStr' => $ticket->getQRCode(),
                    'status' => $ticketStatus, // Include the status
                    'categoryColor' => $ticket->category_color ?? null, // Include the category color
                    'data' => [
                        'date' => $ticketDate->format('d F Y, H:i'),
                        'type' => $typeName,
                        'seat' => $ticket->seat ? $ticket->seat->seat_number : 'N/A',
                        'price' => 'Rp' . number_format($ticket->price, 0, ',', '.'),
                    ]
                ];
            });

            // Get ticket categories for this specific event
            $ticketCategories = TicketCategory::where('event_id', $event->id)->get();

            // If no ticket categories found, create default ones
            if ($ticketCategories->isEmpty()) {
                $ticketCategories = collect([
                    (object)['ticket_category_id' => 'unset', 'name' => 'Unset', 'color' => '#FFFFFF'],
                ]);
            }

            return Inertia::render('User/MyTickets', [
                'client' => $client,
                'props' => $props,
                'tickets' => $formattedTickets,
                'ticketCategories' => $ticketCategories,
                'event' => [
                    'event_id' => $event->id,
                    'name' => $event->name,
                    'slug' => $event->slug,
                    'description' => $event->description ?? '',
                    'venue_id' => $event->venue_id,
                    'event_variables_id' => $event->event_variables_id,
                ]
            ]);
        } catch (\Exception $e) {
            return redirect()->route('client.home', ['client' => $client])
                ->with('error', 'Failed to load ticket data: ' . $e->getMessage());
        }
    }

    public function privacyPolicy(Request $request, string $client = '')
    {
        try {
            $event = $request->get('event');
            $props = $request->get('props');

            $dbContent = $props->privacy_policy ?? null;

            return Inertia::render('Legality/privacypolicy/PrivacyPolicy', [
                'client' => $client,
                'props' => $props,
                'event' => [
                    'event_id' => $event->id,
                    'name' => $event->name,
                    'slug' => $event->slug,
                ],
                'user' => Auth::user(),
                'dbContent' => $dbContent,
            ]);
        } catch (\Exception $e) {
            // Log the error for debugging
            Log::error('Failed to load privacy policy: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());

            return redirect()->route('client.home', ['client' => $client])
                ->with('error', 'Failed to load privacy policy: ' . $e->getMessage());
        }
    }

    public function termCondition(Request $request, string $client = '')
    {
        try {
            $event = $request->get('event');
            $props = $request->get('props');

            $dbContent = $props->terms_and_conditions ?? null;

            return Inertia::render('Legality/termcondition/TermCondition', [
                'client' => $client,
                'props' => $props,
                'event' => [
                    'event_id' => $event->id,
                    'name' => $event->name,
                    'slug' => $event->slug,
                ],
                'user' => Auth::user(),
                'dbContent' => $dbContent,
            ]);
        } catch (\Exception $e) {
            // Log the error for debugging
            Log::error('Failed to load terms and conditions: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());

            return redirect()->route('client.home', ['client' => $client])
                ->with('error', 'Failed to load terms and conditions: ' . $e->getMessage());
        }
    }

    public function verifyEventPassword(Request $request, string $client = '')
    {
        $event = $request->get('event');
        $props = $request->get('props');

        // Validator
        $validator = validator($request->all(), [
            'event_password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return Inertia::render('User/LockedEvent', [
                'client' => $client,
                'event' => $event,
                'props' => $props,
            ])->with([
                'errors' => $validator->errors()->toArray()
            ]);
        }

        // Check if event is locked
        if ($props->is_locked) {
            if ($request->has('event_password')) {
                $passwordInput = $request->event_password;
                $storedPassword = $props->locked_password;

                // Direct comparison (plain text)
                if ($passwordInput === $storedPassword) {
                    $request->session()->put("event_auth_{$event->id}", true);
                    return Inertia::location(route('client.home', ['client' => $client]));
                }

                // Check if the password is hashed (bcrypt)
                if (
                    str_starts_with($storedPassword, '$2y$') ||
                    str_starts_with($storedPassword, '$2a$')
                ) {
                    if (Hash::check($passwordInput, $storedPassword)) {
                        $request->session()->put("event_auth_{$event->id}", true);
                        return Inertia::location(route('client.home', ['client' => $client]));
                    }
                }

                // When pass incorrect
                return Inertia::render('User/LockedEvent', [
                    'client' => $client,
                    'event' => $event,
                    'props' => $props,
                ])->with([
                    'errors' => ['event_password' => 'The password you entered is incorrect.']
                ]);
            }
        }

        // If the event is not locked or password is not required, proceed
        return Inertia::location(route('client.home', ['client' => $client]));
    }
}
