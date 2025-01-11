<?php

namespace App\Http\Controllers;

use App\Models\Seat;
use App\Services\SeatService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class SeatController extends Controller
{
    protected $seatService;

    public function __construct(SeatService $seatService)
    {
        $this->seatService = $seatService;
    }

    public function index(string $venueId): JsonResponse
    {
        $seats = $this->seatService->getVenueSeats($venueId);
        return response()->json($seats);
    }

    public function updateStatus(Request $request, string $seatId): JsonResponse
    {
        $validated = $request->validate([
            'status' => 'required|in:available,booked,reserved,in_transaction'
        ]);

        $seat = $this->seatService->updateSeatStatus($seatId, $validated['status']);
        return response()->json($seat);
    }

    public function bulkUpdate(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'seats' => 'required|array',
            'seats.*.seat_id' => 'required|uuid',
            'seats.*.status' => 'required|in:available,booked,reserved,in_transaction'
        ]);

        $seats = $this->seatService->bulkUpdateStatus($validated['seats']);
        return response()->json($seats);
    }
}