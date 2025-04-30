<?php

namespace App\Http\Controllers;

use App\Models\Seat;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class SeatGridController extends Controller
{
    public function index(Request $request)
    {
        $venueId = $request->query('venue_id');

        if (!$venueId) {
            return redirect()->back()->withErrors(['error' => 'Venue ID is required']);
        }

        try {
            // Get existing seats for the venue
            $seats = Seat::where('venue_id', $venueId)
                ->orderBy('row')
                ->orderBy('column')
                ->get();

            // Calculate grid dimensions
            $layout = [
                'totalRows' => $seats->isEmpty() ? 10 : count(array_unique($seats->pluck('row')->toArray())),
                'totalColumns' => $seats->isEmpty() ? 15 : $seats->max('column'),
                'items' => $seats->map(function ($seat) {
                    return [
                        'type' => 'seat',
                        'seat_id' => $seat->id,
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
            Log::error('Error in grid edit: ' . $e->getMessage());
            return redirect()->back()->withErrors(['error' => 'Failed to load seat layout']);
        }
    }

    public function updateLayout(Request $request)
    {
        $validated = $request->validate([
            'venue_id' => 'required|string|max:36',
            'totalRows' => 'required|integer|min:1|max:26',
            'totalColumns' => 'required|integer|min:1|max:50',
            'items' => 'required|array',
            'items.*.type' => 'required|string|in:seat,label',
            'items.*.row' => 'required|string|size:1',
            'items.*.column' => 'required|integer|min:1',
            // Validasi khusus untuk item bertipe seat
            'items.*.seat_id' => 'required_if:items.*.type,seat|string|max:36',
            'items.*.seat_number' => 'required_if:items.*.type,seat|string',
            'items.*.status' => 'required_if:items.*.type,seat|string|in:available,booked,in_transaction,not_available',
            'items.*.category' => 'required_if:items.*.type,seat|string|in:diamond,gold,silver'
        ]);

        try {
            DB::beginTransaction();

            // Get existing seats
            $existingSeats = Seat::where('venue_id', $validated['venue_id'])
                ->pluck('id')
                ->toArray();

            // Filter seat items only
            $seatItems = collect($validated['items'])->filter(function ($item) {
                return $item['type'] === 'seat';
            });

            $updatedSeatIds = $seatItems->pluck('seat_id')->toArray();

            // Delete seats that are no longer in the layout
            Seat::where('venue_id', $validated['venue_id'])
                ->whereNotIn('id', $updatedSeatIds)
                ->delete();

            // Update or create seats
            foreach ($seatItems as $item) {
                // Generate seat_id for new seats
                if (empty($item['seat_id']) || !in_array($item['seat_id'], $existingSeats)) {
                    $item['seat_id'] = $this->generateUniqueSeatId();
                }

                // Create position string (e.g., "A1", "B2")
                $position = $item['row'] . $item['column'];

                Seat::updateOrCreate(
                    [
                        'id' => $item['seat_id'],
                        'venue_id' => $validated['venue_id']
                    ],
                    [
                        'seat_number' => $item['seat_number'],
                        'position' => $position,
                        'row' => $item['row'],
                        'column' => $item['column'],
                        'status' => $item['status'],
                        'category' => $item['category']
                    ]
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

            return response()->json([
                'message' => 'Layout updated successfully',
                'seats' => $seats
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error updating layout: ' . $e->getMessage());

            return response()->json([
                'error' => 'Failed to update layout: ' . $e->getMessage()
            ], 500);
        }
    }

    private function generateUniqueSeatId(): string
    {
        do {
            $seatId = 'ST-' . str_pad(rand(1, 99999), 5, '0', STR_PAD_LEFT);
        } while (Seat::where('id', $seatId)->exists());

        return $seatId;
    }
}
