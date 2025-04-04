<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Models\Seat;
use Inertia\Inertia;
use App\Models\Event;
use App\Models\Venue;
use App\Models\Ticket;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Models\TicketCategory;
use App\Models\TimelineSession;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\EventCategoryTimeboundPrice;
use Illuminate\Validation\ValidationException;

class SeatController extends Controller
{
    public function index()
    {
        // Original method unchanged
        $seats = Seat::orderBy('row')->orderBy('column')->get();

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

        return Inertia::render('Seat/Index', [
            'layout' => $layout
        ]);
    }

    public function importMap(Request $request)
    {
        $request->validate([
            'config' => 'required|file'
        ]);

        $json = json_decode(file_get_contents($request->file('config')->path()), true);

        try {
            DB::beginTransaction();

            foreach ($json['items'] as $item) {
                if ($item['type'] === 'seat') {
                    Seat::create([
                        'seat_id' => $item['seat_id'],
                        'row' => $item['row'],
                        'column' => $item['column'],
                        'status' => $item['status'],
                        'category' => $item['category'],
                        'price' => $item['price']
                    ]);
                }
            }

            DB::commit();
            return back()->with('message', 'Seat map imported successfully');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Failed to import seat map']);
        }
    }

    public function exportMap()
    {
        try {
            // Retrieve seat data related to the model, assuming it's related by 'seats' relationship
            $venue = Venue::find(request()->route('venue'));
            $seats = $venue->seats()->get()->map(function ($seat) {
                return [
                    'position' => $seat->position,
                ];
            })->toArray();

            // Format the data
            $export = [
                'layout' => [
                    'items' => $seats
                ]
            ];

            // Encode data to JSON format
            $encoded = json_encode($export, JSON_PRETTY_PRINT);

            // Define the filename
            $venueName = Str::slug($venue->name);
            $fileName = "novatix-{$venueName}-seatconfig.json";

            // Return a JSON download response using response()->make()
            return response()->make($encoded, 200, [
                'Content-Type' => 'application/json',
                'Content-Disposition' => "attachment; filename=\"{$fileName}\"",
            ]);
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Failed to export seat map']);
        }
    }

    public function edit(Request $request)
    {
        try {
            // Get event_id from request
            $eventId = $request->event_id;

            if (!$eventId) {
                return redirect()->back()->withErrors(['error' => 'Event ID is required']);
            }

            // Get the event and associated venue
            $event = Event::findOrFail($eventId);
            $venue = Venue::findOrFail($event->venue_id);

            // Get all seats for this venue
            $seats = Seat::where('venue_id', $venue->venue_id)
                ->orderBy('row')
                ->orderBy('column')
                ->get();

            // Get existing tickets for this event
            $existingTickets = Ticket::where('event_id', $eventId)
                ->get()
                ->keyBy('seat_id');

            // Get ticket categories for this event
            $ticketCategories = TicketCategory::where('event_id', $eventId)->get();
            $allTimelines = TimelineSession::where('event_id', $eventId)->get();

            // Get current timeline session
            $currentDate = Carbon::now();
            $currentTimeline = TimelineSession::where('event_id', $eventId)
                ->where('start_date', '<=', $currentDate)
                ->where('end_date', '>=', $currentDate)
                ->first();

            // If no current timeline, use the next upcoming one or most recent one
            if (!$currentTimeline && $allTimelines->isNotEmpty()) {
                // Find the next upcoming timeline
                $currentTimeline = TimelineSession::where('event_id', $eventId)
                    ->where('start_date', '>', $currentDate)
                    ->orderBy('start_date')
                    ->first();

                // If no upcoming timeline exists, then fall back to the most recent one
                if (!$currentTimeline) {
                    $currentTimeline = TimelineSession::where('event_id', $eventId)
                        ->where('end_date', '<', $currentDate)
                        ->orderBy('end_date', 'desc')
                        ->first();
                }
            }

            // Get all timebound prices for this event
            $allPrices = [];
            if ($currentTimeline) {
                // Get all timeline IDs for this event
                $timelineIds = $allTimelines->pluck('timeline_id')->toArray();

                // Get all category IDs for this event
                $categoryIds = $ticketCategories->pluck('ticket_category_id')->toArray();

                // Get all prices for these categories and timelines
                $allPrices = EventCategoryTimeboundPrice::whereIn('timeline_id', $timelineIds)
                    ->whereIn('ticket_category_id', $categoryIds)
                    ->get();
            }

            // Get prices for the current timeline
            $prices = [];
            if ($currentTimeline) {
                $priceData = EventCategoryTimeboundPrice::where('timeline_id', $currentTimeline->timeline_id)->get();
                foreach ($priceData as $price) {
                    $prices[$price->ticket_category_id] = $price->price;
                }
            }

            // Format data for the frontend, prioritizing ticket data
            $layout = [
                'totalRows' => count(array_unique($seats->pluck('row')->toArray())),
                'totalColumns' => $seats->max('column'),
                'items' => $seats->map(function ($seat) use ($existingTickets, $ticketCategories, $prices) {
                    $ticket = $existingTickets->get($seat->seat_id);

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
                        if ($ticket->ticket_category_id && $categoryObj = $ticketCategories->firstWhere('ticket_category_id', $ticket->ticket_category_id)) {
                            $seatData['ticket_type'] = $categoryObj->name;

                            // Use price from timebound prices if available
                            if (isset($prices[$ticket->ticket_category_id])) {
                                $seatData['price'] = $prices[$ticket->ticket_category_id];
                            } else {
                                $seatData['price'] = $ticket->price ?? 0;
                            }
                        } else {
                            // Fallback to stored ticket type
                            $seatData['ticket_type'] = $ticket->ticket_type ?? 'unset';
                            $seatData['price'] = $ticket->price ?? 0;
                        }
                    } else {
                        // Default values for seats without tickets
                        $seatData['status'] = 'unset';
                        $seatData['ticket_type'] = 'unset';
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

            // Get available ticket types from TicketCategory
            $ticketTypes = $ticketCategories->pluck('name')->toArray();

            // If no categories are defined, use default
            if (empty($ticketTypes)) {
                $ticketTypes = ['unset'];
            }

            // Category colors for UI
            $categoryColors = $ticketCategories->pluck('color', 'name')->toArray();

            return Inertia::render('Seat/Edit', [
                'layout' => $layout,
                'event' => $event,
                'venue' => $venue,
                'ticketTypes' => $ticketTypes,
                'categoryColors' => $categoryColors,
                'currentTimeline' => $currentTimeline,
                'allTimelines' => $allTimelines,
                'ticketCategories' => $ticketCategories,
                'categoryPrices' => $allPrices
            ]);
        } catch (\Exception $e) {
            Log::error('Error in edit method: ' . $e->getMessage());
            return redirect()->back()->withErrors(['error' => 'Failed to load seat map: ' . $e->getMessage()]);
        }
    }

    public function updateEventSeats(Request $request)
    {
        $validated = $request->validate([
            'event_id' => 'required|string',
            'seats' => 'required|array',
            'seats.*.seat_id' => 'required|string',
            'seats.*.status' => 'required|string|in:available,booked,in_transaction,not_available,reserved',
            'seats.*.ticket_type' => 'required|string',
        ]);

        try {
            DB::beginTransaction();

            $eventId = $validated['event_id'];
            $event = Event::findOrFail($eventId);

            // Get ticket categories for this event
            $ticketCategories = TicketCategory::where('event_id', $eventId)
                ->get()
                ->keyBy('name');

            // Find current active timeline
            $currentDate = Carbon::now();
            $activeTimeline = TimelineSession::where('event_id', $eventId)
                ->where('start_date', '<=', $currentDate)
                ->where('end_date', '>=', $currentDate)
                ->first();

            if (!$activeTimeline) {
                $activeTimeline = TimelineSession::where('event_id', $eventId)
                    ->where('start_date', '>', $currentDate)
                    ->orderBy('start_date')
                    ->first();
            }

            // Get category prices based on active timeline
            $categoryPrices = [];
            if ($activeTimeline) {
                $priceData = EventCategoryTimeboundPrice::where('timeline_id', $activeTimeline->timeline_id)
                    ->get();
                foreach ($priceData as $price) {
                    $categoryPrices[$price->ticket_category_id] = $price->price;
                }
            }

            foreach ($validated['seats'] as $seatData) {
                $ticketCategoryId = null;
                $price = 0;

                // Find category ID and price based on ticket type
                if (isset($ticketCategories[$seatData['ticket_type']])) {
                    $category = $ticketCategories[$seatData['ticket_type']];
                    $ticketCategoryId = $category->ticket_category_id;

                    // Get price from timebound prices if available
                    if (isset($categoryPrices[$ticketCategoryId])) {
                        $price = $categoryPrices[$ticketCategoryId];
                    }
                }

                Ticket::updateOrCreate(
                    [
                        'event_id' => $eventId,
                        'seat_id' => $seatData['seat_id']
                    ],
                    [
                        'status' => $seatData['status'],
                        'ticket_type' => $seatData['ticket_type'],
                        'ticket_category_id' => $ticketCategoryId,
                        'price' => $price,
                        'team_id' => $event->team_id
                    ]
                );
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Tickets updated successfully'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error updating event seats: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => 'Failed to update tickets: ' . $e->getMessage()
            ], 422);
        }
    }

    public function update(Request $request)
    {
        Log::info('Request headers:', $request->headers->all());
        Log::info('Request body:', $request->all());

        $validated = $request->validate([
            'seats' => 'required|array',
            'seats.*.seat_id' => 'required|string',
            'seats.*.status' => 'required|string|in:available,booked,in_transaction,not_available',
        ]);

        try {
            DB::beginTransaction();

            foreach ($validated['seats'] as $seatData) {
                $seat = Seat::where('seat_id', $seatData['seat_id'])->first();

                if ($seat && $seat->status === 'booked' && $seatData['status'] !== 'booked') {
                    continue;
                }

                Seat::where('seat_id', $seatData['seat_id'])
                    ->update([
                        'status' => $seatData['status']
                    ]);
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error updating seats: ' . $e->getMessage());
            return response()->json([
                'error' => 'Failed to update seats'
            ], 422);
        }
    }

    public function updateLayout(Request $request)
    {
        Log::info('Received update layout request:', $request->all());

        try {
            $validated = $request->validate([
                'venue_id' => 'required|string|max:36',
                'totalRows' => 'required|integer|min:1',
                'totalColumns' => 'required|integer|min:1',
                'items' => 'required|array',
                'items.*.type' => 'required|string|in:seat,label',
                'items.*.row' => 'required|string', // Removed size:1 restriction
                'items.*.column' => 'required|integer|min:1',
                'items.*.seat_id' => 'nullable|string|max:36',
                'items.*.seat_number' => 'required_if:items.*.type,seat|string',
                'items.*.position' => 'required_if:items.*.type,seat|string',
            ]);

            DB::beginTransaction();

            $existingSeats = Seat::where('venue_id', $validated['venue_id'])
                ->pluck('seat_id')
                ->toArray();

            $seatItems = collect($validated['items'])->filter(function ($item) {
                return $item['type'] === 'seat';
            });

            $updatedSeatIds = $seatItems->filter(function ($item) {
                return !empty($item['seat_id']);
            })->pluck('seat_id')->toArray();

            // Delete seats that are no longer in the layout
            Seat::where('venue_id', $validated['venue_id'])
                ->whereNotIn('seat_id', $updatedSeatIds)
                ->delete();

            // Process seat items
            foreach ($seatItems as $item) {
                $seatData = [
                    'seat_number' => $item['seat_number'],
                    'position' => $item['position'],
                    'row' => $item['row'],
                    'column' => $item['column'],
                ];

                // For new seats or seats with empty seat_id, generate a new ID from venue_id and seat_number
                if (empty($item['seat_id'])) {
                    $item['seat_id'] = $this->generateUniqueSeatId($validated['venue_id'], $item['seat_number']);
                }

                Seat::updateOrCreate(
                    [
                        'seat_id' => $item['seat_id'],
                        'venue_id' => $validated['venue_id']
                    ],
                    $seatData
                );
            }

            // Reorder seat numbers
            $seats = Seat::where('venue_id', $validated['venue_id'])
                ->orderBy('row')
                ->orderBy('column')
                ->get();

            $seatCounters = [];

            foreach ($seats as $seat) {
                if (!isset($seatCounters[$seat->row])) {
                    $seatCounters[$seat->row] = 1;
                }

                $newSeatNumber = $seat->row . $seatCounters[$seat->row];

                // Update seat_id jika seat_number berubah
                if ($seat->seat_number != $newSeatNumber) {
                    $seat->seat_id = $this->generateUniqueSeatId($validated['venue_id'], $newSeatNumber);
                    $seat->seat_number = $newSeatNumber;
                    $seat->save();
                }

                $seatCounters[$seat->row]++;
            }

            DB::commit();

            return redirect()->back()->with('success', 'Layout updated successfully');
        } catch (ValidationException $e) {
            return redirect()->back()->withErrors($e->errors());
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error updating layout:', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return redirect()->back()->withErrors(['error' => 'Failed to update layout: ' . $e->getMessage()]);
        }
    }

    public function gridEdit(Request $request)
    {
        try {
            // Get venue_id from request
            $venueId = $request->venue_id;

            if (!$venueId) {
                return redirect()->back()->withErrors(['error' => 'Venue ID is required']);
            }

            $seats = Seat::where('venue_id', $venueId)
                ->orderBy('row')
                ->orderBy('column')
                ->get();

            // Jika tidak ada seat, tampilkan layout kosong
            if ($seats->isEmpty()) {
                $layout = [
                    'totalRows' => 0,
                    'totalColumns' => 0,
                    'items' => []
                ];
            } else {
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
                            'position' => $seat->position
                        ];
                    })->values()
                ];
            }

            return Inertia::render('Seat/GridEdit', [
                'layout' => $layout,
                'venue_id' => $venueId
            ]);
        } catch (\Exception $e) {
            Log::error('Error loading grid edit: ' . $e->getMessage());
            return redirect()->back()->withErrors(['error' => 'Failed to load seat layout']);
        }
    }

    public function saveGridLayout(Request $request)
    {
        Log::info('Received save grid layout request:', $request->all());

        try {
            $validated = $request->validate([
                'venue_id' => 'required|string|max:64',
                'totalRows' => 'required|integer|min:1',
                'totalColumns' => 'required|integer|min:1',
                'items' => 'required|array',
                'items.*.type' => 'required|string|in:seat,label',
                'items.*.row' => 'required|string',
                'items.*.column' => 'required|integer|min:1',
                'items.*.seat_id' => 'nullable|string|max:64',
                'items.*.seat_number' => 'required_if:items.*.type,seat|string',
                'items.*.position' => 'nullable|string',
            ]);

            DB::beginTransaction();

            $venueId = $validated['venue_id'];

            // Get existing seat IDs for this venue
            $existingSeats = Seat::where('venue_id', $venueId)
                ->pluck('seat_id')
                ->toArray();

            $seatItems = collect($validated['items'])->filter(function ($item) {
                return $item['type'] === 'seat';
            });

            $updatedSeatIds = $seatItems->filter(function ($item) {
                return !empty($item['seat_id']);
            })->pluck('seat_id')->toArray();

            // Delete seats that are no longer in the layout
            Seat::where('venue_id', $venueId)
                ->whereNotIn('seat_id', $updatedSeatIds)
                ->delete();

            // Process seat items
            foreach ($seatItems as $item) {
                // Generate position if not provided
                if (empty($item['position'])) {
                    $item['position'] = $item['row'] . $item['column'];
                }

                $seatData = [
                    'seat_number' => $item['seat_number'],
                    'position' => $item['position'],
                    'row' => $item['row'],
                    'column' => $item['column'],
                ];

                // For new seats, generate a seat_id from venue_id and seat_number
                if (empty($item['seat_id'])) {
                    $item['seat_id'] = $this->generateUniqueSeatId($venueId, $item['seat_number']);
                }

                Seat::updateOrCreate(
                    [
                        'seat_id' => $item['seat_id'],
                        'venue_id' => $venueId
                    ],
                    $seatData
                );
            }

            // Reorder seat numbers
            $seats = Seat::where('venue_id', $venueId)
                ->orderBy('row')
                ->orderBy('column')
                ->get();

            $seatCounters = [];

            foreach ($seats as $seat) {
                if (!isset($seatCounters[$seat->row])) {
                    $seatCounters[$seat->row] = 1;
                }

                $newSeatNumber = $seat->row . $seatCounters[$seat->row];

                // Update seat_id if seat_number changes
                if ($seat->seat_number != $newSeatNumber) {
                    $seat->seat_id = $this->generateUniqueSeatId($venueId, $newSeatNumber);
                    $seat->seat_number = $newSeatNumber;
                    $seat->save();
                }

                $seatCounters[$seat->row]++;
            }

            DB::commit();

            // // Check if it's an XHR/AJAX request
            // if ($request->expectsJson() || $request->ajax() || $request->wantsJson()) {
            //     return response()->json([
            //         'success' => true,
            //         'message' => 'Grid layout updated successfully'
            //     ]);
            // }

            // // Return Inertia redirect for normal requests
            // return redirect()->back()->with('success', 'Grid layout updated successfully');
        } catch (ValidationException $e) {
            DB::rollBack();

            if ($request->expectsJson() || $request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'errors' => $e->errors()
                ], 422);
            }

            return redirect()->back()->withErrors($e->errors());
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error saving grid layout:', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            // if ($request->expectsJson() || $request->ajax() || $request->wantsJson()) {
            //     return response()->json([
            //         'success' => false,
            //         'error' => 'Failed to Save grid layout: ' . $e->getMessage()
            //     ], 500);
            // }

            return redirect()->back()->withErrors(['error' => 'Failed to Save grid layout: ' . $e->getMessage()]);
        }
    }

    private function generateUniqueSeatId(string $venueId, string $seatNumber): string
    {
        // Gunakan seluruh venue_id, bukan hanya 8 karakter pertama
        $seatId = $venueId . '-' . $seatNumber;

        // Cek apakah ID sudah ada, jika ada tambahkan suffix
        $counter = 1;
        $originalSeatId = $seatId;
        while (Seat::where('seat_id', $seatId)->exists()) {
            $seatId = $originalSeatId . '-' . $counter;
            $counter++;
        }

        return $seatId;
    }
}
