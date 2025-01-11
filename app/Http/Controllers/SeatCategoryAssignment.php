<?php

namespace App\Http\Controllers;

use App\Models\Seat;
use App\Models\TicketCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\JsonResponse;

class SeatAssignmentController extends Controller
{
    public function assignCategory(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'seat_ids' => 'required|array',
            'seat_ids.*' => 'required|uuid|exists:seats,seat_id',
            'ticket_category_id' => 'required|uuid|exists:ticket_categories,ticket_category_id'
        ]);

        try {
            DB::beginTransaction();

            // Get the ticket category for validation
            $category = TicketCategory::findOrFail($validated['ticket_category_id']);

            // Update all selected seats
            Seat::whereIn('seat_id', $validated['seat_ids'])
                ->update([
                    'ticket_category_id' => $category->ticket_category_id,
                    'updated_at' => now()
                ]);

            // Get updated seats
            $updatedSeats = Seat::whereIn('seat_id', $validated['seat_ids'])
                ->with('ticketCategory')
                ->get();

            DB::commit();

            return response()->json([
                'message' => 'Seats assigned successfully',
                'seats' => $updatedSeats
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'error' => 'Failed to assign category to seats',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function bulkAssignCategories(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'assignments' => 'required|array',
            'assignments.*.row' => 'required|string',
            'assignments.*.seat_range' => 'required|array',
            'assignments.*.seat_range.start' => 'required|integer',
            'assignments.*.seat_range.end' => 'required|integer|gte:assignments.*.seat_range.start',
            'assignments.*.ticket_category_id' => 'required|uuid|exists:ticket_categories,ticket_category_id'
        ]);

        try {
            DB::beginTransaction();

            foreach ($validated['assignments'] as $assignment) {
                $row = $assignment['row'];
                $start = $assignment['seat_range']['start'];
                $end = $assignment['seat_range']['end'];

                // Generate seat numbers for the range
                $seatNumbers = array_map(
                    fn($num) => $row . str_pad($num, 2, '0', STR_PAD_LEFT),
                    range($start, $end)
                );

                // Update seats in this range
                Seat::whereIn('seat_number', $seatNumbers)
                    ->update([
                        'ticket_category_id' => $assignment['ticket_category_id'],
                        'updated_at' => now()
                    ]);
            }

            DB::commit();

            return response()->json([
                'message' => 'Bulk category assignment completed successfully'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'error' => 'Failed to perform bulk category assignment',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function getAssignments(string $venueId): JsonResponse
    {
        $assignments = Seat::where('venue_id', $venueId)
            ->with('ticketCategory')
            ->orderBy('seat_number')
            ->get()
            ->groupBy(function ($seat) {
                return preg_replace('/[0-9]+/', '', $seat->seat_number);
            });

        return response()->json($assignments);
    }
}