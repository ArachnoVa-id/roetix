<?php

namespace App\Http\Controllers;

use App\Models\SeatTransaction;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class TransactionHistoryController extends Controller
{
    public function getUserTransactions(string $userId): JsonResponse
    {
        $transactions = SeatTransaction::where('user_id', $userId)
            ->with(['seat'])
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($transactions);
    }

    public function getVenueTransactions(string $venueId): JsonResponse
    {
        $transactions = SeatTransaction::whereHas('seat', function ($query) use ($venueId) {
            $query->where('venue_id', $venueId);
        })
            ->with(['seat'])
            ->where('status', 'pending')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($transactions);
    }

    public function getTransactionStatistics(string $venueId): JsonResponse
    {
        $stats = [
            'total' => SeatTransaction::whereHas('seat', function ($query) use ($venueId) {
                $query->where('venue_id', $venueId);
            })->count(),

            'completed' => SeatTransaction::whereHas('seat', function ($query) use ($venueId) {
                $query->where('venue_id', $venueId);
            })->where('status', 'completed')->count(),

            'cancelled' => SeatTransaction::whereHas('seat', function ($query) use ($venueId) {
                $query->where('venue_id', $venueId);
            })->where('status', 'cancelled')->count(),

            'expired' => SeatTransaction::whereHas('seat', function ($query) use ($venueId) {
                $query->where('venue_id', $venueId);
            })->where('status', 'expired')->count(),

            'pending' => SeatTransaction::whereHas('seat', function ($query) use ($venueId) {
                $query->where('venue_id', $venueId);
            })->where('status', 'pending')->count()
        ];

        return response()->json($stats);
    }
}
