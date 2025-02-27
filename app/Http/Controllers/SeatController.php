<?php

namespace App\Http\Controllers;

use App\Models\Seat;
use App\Models\Section;
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
            'items' => $seats->map(function($seat) {
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

    public function edit()
{
    try {
        // Ambil semua kursi
        $seats = Seat::orderBy('row')->orderBy('column')->get();
        
        // Format data seperti di method index()
        $layout = [
            'totalRows' => count(array_unique($seats->pluck('row')->toArray())),
            'totalColumns' => $seats->max('column'),
            'items' => $seats->map(function($seat) {
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

        return Inertia::render('Seat/Edit', [
            'layout' => $layout
        ]);

    } catch (\Exception $e) {
        Log::error('Error in edit method: ' . $e->getMessage());
        return redirect()->back()->withErrors(['error' => 'Failed to load seat map']);
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
            'items' => $seats->map(function($seat) {
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
            'items.*.seat_id' => 'required_if:items.*.type,seat|string|max:36',
            'items.*.seat_number' => 'required_if:items.*.type,seat|string',
            'items.*.status' => 'required_if:items.*.type,seat|string|in:available,booked,in_transaction,not_available',
            'items.*.category' => 'required_if:items.*.type,seat|string|in:diamond,gold,silver',
            'items.*.position' => 'required_if:items.*.type,seat|string',
        ]);

        DB::beginTransaction();

        $existingSeats = Seat::where('venue_id', $validated['venue_id'])
            ->pluck('seat_id')
            ->toArray();
            
        $seatItems = collect($validated['items'])->filter(function ($item) {
            return $item['type'] === 'seat';
        });
        
        $updatedSeatIds = $seatItems->pluck('seat_id')->toArray();

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
                'status' => $item['status'],
                'category' => $item['category']
            ];

            // For new seats, generate a unique ID
            if (empty($item['seat_id'])) {
                $item['seat_id'] = $this->generateUniqueSeatId();
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

            $seat->seat_number = $seat->row . $seatCounters[$seat->row];
            $seat->save();

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
        
        $layout = [
            'totalRows' => count(array_unique($seats->pluck('row')->toArray())),
            'totalColumns' => $seats->max('column'),
            'items' => $seats->map(function($seat) {
                return [
                    'type' => 'seat',
                    'seat_id' => $seat->seat_id,
                    'seat_number' => $seat->seat_number,
                    'row' => $seat->row,
                    'column' => $seat->column,
                    'status' => $seat->status,
                    'category' => $seat->category
                ];
            })->values()
        ];

        return Inertia::render('Seat/GridEdit', [
            'layout' => $layout,
            'venue_id' => $venueId
        ]);

    } catch (\Exception $e) {
        Log::error('Error loading grid edit: ' . $e->getMessage());
        return redirect()->back()->withErrors(['error' => 'Failed to load seat layout']);
    }
}

private function generateUniqueSeatId(): string 
{
    do {
        $seatId = 'ST-' . str_pad(rand(1, 99999), 5, '0', STR_PAD_LEFT);
    } while (Seat::where('seat_id', $seatId)->exists());
    
    return $seatId;
}
}