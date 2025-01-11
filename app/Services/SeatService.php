<?php

namespace App\Services;

use App\Models\Seat;
use App\Events\SeatStatusUpdated;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class SeatService
{
    public function getVenueSeats(string $venueId)
    {
        return Seat::where('venue_id', $venueId)
            ->orderBy('seat_number')
            ->get();
    }

    public function updateSeatStatus(string $seatId, string $status)
    {
        $seat = Seat::findOrFail($seatId);
        $seat->status = $status;
        $seat->save();

        broadcast(new SeatStatusUpdated($seat))->toOthers();

        return $seat;
    }

    public function bulkUpdateStatus(array $seatsData)
    {
        return DB::transaction(function () use ($seatsData) {
            $updatedSeats = [];

            foreach ($seatsData as $seatData) {
                $seat = Seat::findOrFail($seatData['seat_id']);
                $seat->status = $seatData['status'];
                $seat->save();

                broadcast(new SeatStatusUpdated($seat))->toOthers();
                $updatedSeats[] = $seat;
            }

            return $updatedSeats;
        });
    }
}
