<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\EventVariables;
use App\Models\Ticket;
use App\Models\Seat;
use App\Models\Venue;
use App\Models\TimelineSession;
use App\Models\TicketCategory;
use App\Models\EventCategoryTimeboundPrice;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Support\Facades\Hash;

class UserPageController extends Controller
{
    private function getDefaultValue()
    {
        $defaultValues = [
            'primary_color' => '#FFF',
            'secondary_color' => '#9FF',
            'text_primary_color' => '#000000',
            'text_secondary_color' => '#000000',
            'ticket_limit' => 5,
            'is_maintenance' => false,
            'maintenance_title' => '',
            'maintenance_message' => '',
            'maintenance_expected_finish' => now(),
            'is_locked' => false,
            'locked_password' => '',
            'logo' => '/images/novatix-logo-white/android-chrome-192x192.png',
            'logo_alt' => 'Novatix Logo',
            'favicon' => '/images/novatix-logo-white/favicon.ico',
        ];

        return $defaultValues;
    }
    public function landing(Request $request, string $client = '')
    {
        // Get the event and associated venue
        $event = Event::where('slug', $client)
            ->first();
        if (!$event) {
            return Inertia::render('User/Landing', [
                'client' => $client,
                'error' => 'Event not found.',
                'props' => EventVariables::getDefaultValue()
            ]);
        }

        $props = $event->eventVariables;

        if (!$props) {
            return Inertia::render('User/Landing', [
                'client' => $client,
                'error' => 'Event variables not found for ' . $event->name . '.',
                'props' => EventVariables::getDefaultValue()
            ]);
        }

        // Check if the event is in maintenance mode
        if ($props->is_maintenance) {
            return Inertia::render('User/Maintenance', [
                'client' => $client,
                'event' => [
                    'name' => $event->name,
                    'slug' => $event->slug
                ],
                'maintenance' => [
                    'title' => $props->maintenance_title ?: 'Site Under Maintenance',
                    'message' => $props->maintenance_message ?: 'We are currently performing maintenance on our system. Please check back later.',
                    'expected_finish' => $props->maintenance_expected_finish ? Carbon::parse($props->maintenance_expected_finish)->format('F j, Y, g:i a') : null,
                ],
                'props' => $props
            ]);
        }

        // Check if the event is locked
        $isAuthenticated = false;
        $hasAttemptedLogin = false;
        $loginError = null;

        if ($props->is_locked) {
            // Check if the user has provided a password
            if ($request->has('event_password')) {
                $hasAttemptedLogin = true;

                // Verify the password
                if (
                    Hash::check($request->event_password, $props->locked_password) ||
                    $request->event_password === $props->locked_password
                ) { // Allow plaintext comparison as fallback
                    // Password is correct, store in session
                    $request->session()->put("event_auth_{$event->event_id}", true);
                    $isAuthenticated = true;
                } else {
                    // Password is incorrect
                    $loginError = 'The password you entered is incorrect.';
                }
            } else {
                // Check if user is already authenticated for this event
                $isAuthenticated = $request->session()->get("event_auth_{$event->event_id}", false);
            }

            // If not authenticated, show the lock screen
            if (!$isAuthenticated) {
                return Inertia::render('User/LockedEvent', [
                    'client' => $client,
                    'event' => [
                        'name' => $event->name,
                        'slug' => $event->slug
                    ],
                    'hasAttemptedLogin' => $hasAttemptedLogin,
                    'loginError' => $loginError,
                    'props' => $props
                ]);
            }
        }

        try {
            // Get the venue for this event
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
                            'category' => $ticket->ticket_type,
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
                'event' => [
                    'event_id' => $event->event_id,
                    'name' => $event->name,
                    'date' => $event->date,
                    'event_date' => $event->event_date ?? $event->date, // Use event_date or fall back to date
                    'venue_id' => $event->venue_id,
                    'status' => $event->status,
                    'slug' => $event->slug
                ],
                'venue' => $venue,
                'ticketCategories' => $ticketCategories,
                'currentTimeline' => $currentTimeline,
                'categoryPrices' => $categoryPrices,
                'props' => $props
            ]);
        } catch (\Exception $e) {
            return Inertia::render('User/Landing', [
                'client' => $client,
                'error' => 'Failed to load event data: ' . $e->getMessage(),
                'props' => $props
            ]);
        }
    }

    public function my_tickets(string $client = '')
    {
        try {
            // Get the event by slug
            $event = Event::where('slug', $client)->first();

            if (!$event) {
                Log::error('Event not found for slug: ' . $client);
                return redirect()->route('login');
            }

            // Gunakan try-catch untuk mendapatkan EventVariables atau gunakan default
            try {
                $props = EventVariables::findOrFail($event->event_variables_id);
            } catch (\Exception $e) {
                // Jika event_variables tidak ditemukan, gunakan nilai default
                Log::warning('EventVariables not found for event: ' . $event->event_id . '. Using default values.');
                $props = new EventVariables($this->getDefaultValue());
            }

            // Check if user is authenticated
            if (!Auth::check()) {
                return redirect()->route('client.login', ['client' => $client]);
            }

            // Dapatkan order_id untuk user yang sedang login
            $userOrderIds = Order::where('user_id', Auth::id())->pluck('order_id')->toArray();

            // Struktur query join berdasarkan struktur database Anda
            // Ini adalah contoh struktur, sesuaikan dengan struktur database yang sebenarnya
            $userTickets = DB::table('tickets')
                ->join('ticket_order', 'tickets.ticket_id', '=', 'ticket_order.ticket_id')
                ->join('orders', 'ticket_order.order_id', '=', 'orders.order_id')
                ->where('tickets.event_id', $event->event_id)
                ->where('tickets.status', 'booked')
                ->whereIn('orders.order_id', $userOrderIds)
                ->whereIn('ticket_order.status', ['enabled', 'scanned']) // Only get enabled or scanned tickets (exclude deactivated)
                ->select('tickets.*', 'orders.order_date', 'ticket_order.status as ticket_order_status')
                ->get();

            // Untuk debug
            Log::info('Found ' . count($userTickets) . ' tickets for user ' . Auth::id() . ' in event ' . $event->event_id);

            // Load seat data for each ticket
            $tickets = [];
            foreach ($userTickets as $ticketData) {
                $ticket = Ticket::with('seat')->find($ticketData->ticket_id);
                if ($ticket) {
                    $tickets[] = $ticket;
                    // Attach order_date and ticket_order_status to ticket object
                    $ticket->order_date = $ticketData->order_date;
                    $ticket->ticket_order_status = $ticketData->ticket_order_status; // Add this line
                }
            }

            // Format tickets for display
            $formattedTickets = collect($tickets)->map(function ($ticket) use ($client) {
                // Generate a ticket URL for the QR code
                $ticketUrl = route('client.my_tickets', ['client' => $client]) . '?ticket=' . $ticket->ticket_id;

                // Use order date or created_at for ticket date
                $ticketDate = property_exists($ticket, 'order_date') && $ticket->order_date
                    ? Carbon::parse($ticket->order_date)
                    : Carbon::parse($ticket->created_at);

                // Determine ticket type
                $typeName = $ticket->ticket_type ? ucfirst($ticket->ticket_type) : 'Standard';

                // Add the ticket status from ticket_order
                $ticketStatus = $ticket->ticket_order_status ?? 'enabled';

                // // Special handling for VIP tickets
                // if (strtolower($typeName) === 'vip') {
                //     $typeName = 'VIP+';
                // }

                return [
                    'id' => $ticket->ticket_id,
                    'ticketType' => $typeName,
                    'ticketCode' => $ticket->ticket_id,
                    'ticketURL' => $ticketUrl,
                    'status' => $ticketStatus, // Include the status
                    'ticketData' => [
                        'date' => $ticketDate->format('d F Y, H:i'),
                        'type' => $typeName,
                        'seat' => $ticket->seat ? $ticket->seat->seat_number : 'N/A',
                        'price' => 'Rp' . number_format($ticket->price, 0, ',', '.'),
                    ]
                ];
            });

            return Inertia::render('User/MyTickets', [
                'client' => $client,
                'props' => $props,
                'tickets' => $formattedTickets,
                'event' => [
                    'event_id' => $event->event_id,
                    'name' => $event->name,
                    'slug' => $event->slug,
                    'description' => $event->description ?? '',
                    'venue_id' => $event->venue_id,
                    'event_variables_id' => $event->event_variables_id,
                ]
            ]);
        } catch (\Exception $e) {
            // Log the error for debugging
            Log::error('Failed to load ticket data: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());

            return redirect()->route('client.home', ['client' => $client])
                ->with('error', 'Failed to load ticket data: ' . $e->getMessage());
        }
    }

    public function privacyPolicy(string $client = '')
    {
        try {
            // Get the event by slug
            $event = Event::where('slug', $client)->first();

            if (!$event) {
                Log::error('Event not found for slug: ' . $client);
                return redirect()->route('login');
            }

            // Try to get EventVariables or use default values
            try {
                $props = EventVariables::findOrFail($event->event_variables_id);
            } catch (\Exception $e) {
                // If event_variables not found, use default values
                Log::warning('EventVariables not found for event: ' . $event->event_id . '. Using default values.');
                $props = new EventVariables($this->getDefaultValue());
            }

            return Inertia::render('Legality/privacypolicy/PrivacyPolicy', [
                'client' => $client,
                'props' => $props,
                'event' => [
                    'event_id' => $event->event_id,
                    'name' => $event->name,
                    'slug' => $event->slug,
                ]
            ]);
        } catch (\Exception $e) {
            // Log the error for debugging
            Log::error('Failed to load privacy policy: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());

            return redirect()->route('client.home', ['client' => $client])
                ->with('error', 'Failed to load privacy policy: ' . $e->getMessage());
        }
    }

    public function termCondition(string $client = '')
    {
        try {
            // Get the event by slug
            $event = Event::where('slug', $client)->first();

            if (!$event) {
                Log::error('Event not found for slug: ' . $client);
                return redirect()->route('login');
            }

            // Try to get EventVariables or use default values
            try {
                $props = EventVariables::findOrFail($event->event_variables_id);
            } catch (\Exception $e) {
                // If event_variables not found, use default values
                Log::warning('EventVariables not found for event: ' . $event->event_id . '. Using default values.');
                $props = new EventVariables($this->getDefaultValue());
            }

            return Inertia::render('Legality/termcondition/TermCondition', [
                'client' => $client,
                'props' => $props,
                'event' => [
                    'event_id' => $event->event_id,
                    'name' => $event->name,
                    'slug' => $event->slug,
                ]
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
        // Get the event
        $event = Event::where('slug', $client)->first();
        if (!$event || !$event->eventVariables) {
            return redirect()->route('client.home', ['client' => $client])
                ->with('error', 'Event not found or configuration missing.');
        }

        $props = $event->eventVariables;

        // Verify the password
        if ($props->is_locked) {
            if ($request->has('event_password')) {
                // First, try direct comparison (for plaintext passwords)
                if ($request->event_password === $props->locked_password) {
                    // Password is correct, store in session
                    $request->session()->put("event_auth_{$event->event_id}", true);
                    return redirect()->route('client.home', ['client' => $client]);
                }

                // Only try bcrypt check if the password looks like a bcrypt hash
                // Bcrypt hashes start with $2y$ or $2a$
                if (
                    str_starts_with($props->locked_password, '$2y$') ||
                    str_starts_with($props->locked_password, '$2a$')
                ) {
                    try {
                        if (Hash::check($request->event_password, $props->locked_password)) {
                            // Password is correct, store in session
                            $request->session()->put("event_auth_{$event->event_id}", true);
                            return redirect()->route('client.home', ['client' => $client]);
                        }
                    } catch (\Exception $e) {
                        Log::error('Hash comparison failed: ' . $e->getMessage());
                        // Continue to error message below
                    }
                }

                // If we get here, password is incorrect
                return redirect()->route('client.home', ['client' => $client])
                    ->with('error', 'The password you entered is incorrect.');
            }
        }

        // If we get here, something went wrong
        return redirect()->route('client.home', ['client' => $client]);
    }
}
