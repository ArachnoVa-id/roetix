<?php

namespace App\Http\Controllers;

use App\Models\Seat;
use App\Models\Section;
use App\Models\Event;
use App\Models\Venue;
use App\Models\Ticket;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;


class SeatController extends Controller
{
    public function index()
    {
        $seats = Seat::orderBy('row')->orderBy('column')->get();

        $layout = [
            'totalRows' => count(array_unique($seats->pluck('row')->toArray())),
            'totalColumns' => $seats->max('column'),
            'items' => $seats->map(function ($seat) {
                return [
                    'type' => 'seat',
                    'seat_id' => $seat->seat_id,
                    'seat_number' => $seat->seat_number, // Tambahkan ini
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

            // Format data for the frontend, prioritizing ticket data
            $layout = [
                'totalRows' => count(array_unique($seats->pluck('row')->toArray())),
                'totalColumns' => $seats->max('column'),
                'items' => $seats->map(function ($seat) use ($existingTickets) {
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
                        $seatData['ticket_type'] = $ticket->ticket_type;
                        $seatData['price'] = $ticket->price;
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

            // Get available ticket types for dropdown
            $ticketTypes = ['standard', 'VIP'];

            return Inertia::render('Seat/Edit', [
                'layout' => $layout,
                'event' => $event,
                'venue' => $venue,
                'ticketTypes' => $ticketTypes
            ]);
        } catch (\Exception $e) {
            Log::error('Error in edit method: ' . $e->getMessage());
            return redirect()->back()->withErrors(['error' => 'Failed to load seat map: ' . $e->getMessage()]);
        }
    }

    public function updateEventSeats(Request $request)
    {
        Log::info('Request headers:', $request->headers->all());
        Log::info('Request body:', $request->all());

        $validated = $request->validate([
            'event_id' => 'required|string',
            'seats' => 'required|array',
            'seats.*.seat_id' => 'required|string',
            'seats.*.status' => 'required|string|in:available,booked,in_transaction,not_available,reserved',
            'seats.*.ticket_type' => 'required|string',
            'seats.*.price' => 'required|numeric|max:10000000' // Adding max value constraint
        ]);

        try {
            DB::beginTransaction();

            $eventId = $validated['event_id'];
            $event = Event::findOrFail($eventId);

            // Get all seats for this venue
            $venueId = $event->venue_id;
            $allSeats = Seat::where('venue_id', $venueId)->get();

            // Get existing tickets for this event
            $existingTickets = Ticket::where('event_id', $eventId)->get()->keyBy('seat_id');

            // Get the selected seats from the request
            $selectedSeatIds = collect($validated['seats'])->pluck('seat_id')->toArray();
            $selectedSeatsData = collect($validated['seats'])->keyBy('seat_id');

            // Process only the selected seats
            foreach ($selectedSeatIds as $seatId) {
                $seatData = $selectedSeatsData[$seatId];

                Ticket::updateOrCreate(
                    [
                        'event_id' => $eventId,
                        'seat_id' => $seatId
                    ],
                    [
                        'status' => $seatData['status'],
                        'ticket_type' => $seatData['ticket_type'],
                        'price' => $seatData['price'],
                        'team_id' => $event->team_id
                    ]
                );
            }

            // For any seats that don't have tickets yet, create them with default values
            foreach ($allSeats as $seat) {
                if (!isset($existingTickets[$seat->seat_id]) && !in_array($seat->seat_id, $selectedSeatIds)) {
                    Ticket::create([
                        'event_id' => $eventId,
                        'seat_id' => $seat->seat_id,
                        'status' => 'reserved',
                        'ticket_type' => 'standard',
                        'price' => 0,
                        'team_id' => $event->team_id
                    ]);
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Tickets updated successfully'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error updating event seats: ' . $e->getMessage());
            Log::error('Error trace: ' . $e->getTraceAsString());
            return response()->json([
                'success' => false,
                'error' => 'Failed to update tickets: ' . $e->getMessage()
            ], 422);
        }
    }

    // SeatController.php
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

    public function spreadsheet()
    {
        try {
            $seats = Seat::orderBy('row')->orderBy('column')->get();

            $layout = [
                'totalRows' => count(array_unique($seats->pluck('row')->toArray())),
                'totalColumns' => $seats->max('column'),
                'items' => $seats->map(function ($seat) {
                    return [
                        'type' => 'seat',
                        'seat_id' => $seat->seat_id,
                        'seat_number' => $seat->seat_number, // Tambahkan ini
                        'row' => $seat->row,
                        'column' => $seat->column,
                        'status' => $seat->status,
                        'category' => $seat->category,
                        'price' => $seat->price
                    ];
                })->values()
            ];

            return Inertia::render('Seat/Spreadsheet', [
                'layout' => $layout
            ]);
        } catch (\Exception $e) {
            Log::error('Error in spreadsheet method: ' . $e->getMessage());
            return redirect()->back()->withErrors(['error' => 'Failed to load seat spreadsheet']);
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
            //         'error' => 'Failed to save grid layout: ' . $e->getMessage()
            //     ], 500);
            // }

            return redirect()->back()->withErrors(['error' => 'Failed to save grid layout: ' . $e->getMessage()]);
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
