<?php

namespace App\Services;

use App\Models\Venue;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class VenueService
{
    public function getVenueWithSeats(string $venueId)
    {
        $venue = Venue::with([
            'seats' => function ($query) {
                $query->orderBy('seat_number');
            }
        ])->findOrFail($venueId);

        return $venue;
    }

    public function updateVenueStatus(string $venueId, string $status)
    {
        $venue = Venue::findOrFail($venueId);
        $venue->status = $status;
        $venue->save();

        return $venue;
    }
}