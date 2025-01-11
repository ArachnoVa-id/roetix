<?php

namespace App\Http\Controllers;

use App\Models\Venue;
use App\Services\VenueService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class VenueController extends Controller
{
    protected $venueService;

    public function __construct(VenueService $venueService)
    {
        $this->venueService = $venueService;
    }

    public function show(string $venueId): JsonResponse
    {
        $venue = $this->venueService->getVenueWithSeats($venueId);
        return response()->json($venue);
    }

    public function updateStatus(Request $request, string $venueId): JsonResponse
    {
        $validated = $request->validate([
            'status' => 'required|in:active,inactive,under_maintenance'
        ]);

        $venue = $this->venueService->updateVenueStatus($venueId, $validated['status']);
        return response()->json($venue);
    }
}