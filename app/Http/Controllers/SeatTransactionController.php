<?php

namespace App\Http\Controllers;

use App\Models\Seat;
use App\Models\SeatTransaction;
use App\Events\TransactionUpdated;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\JsonResponse;

class SeatTransactionController extends Controller
{
    // Transaction timeout in minutes
    const TRANSACTION_TIMEOUT = 10;

    public function create(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'seat_id' => 'required|uuid|exists:seats,seat_id',
            'user_id' => 'required|uuid'
        ]);

        try {
            DB::beginTransaction();

            // Check if seat is available
            $seat = Seat::lockForUpdate()->findOrFail($validated['seat_id']);

            if ($seat->status !== 'available') {
                throw new \Exception('Seat is not available');
            }

            // Create transaction
            $transaction = SeatTransaction::create([
                'seat_id' => $validated['seat_id'],
                'user_id' => $validated['user_id'],
                'status' => 'pending',
                'reservation_time' => now(),
                'expiry_time' => now()->addMinutes(self::TRANSACTION_TIMEOUT)
            ]);

            // Update seat status
            $seat->update(['status' => 'in_transaction']);

            DB::commit();

            // Broadcast update
            broadcast(new TransactionUpdated($transaction, $seat))->toOthers();

            return response()->json([
                'transaction' => $transaction,
                'seat' => $seat
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'error' => 'Failed to create transaction',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function release(Request $request, string $seatId): JsonResponse
    {
        $validated = $request->validate([
            'user_id' => 'required|uuid'
        ]);

        try {
            DB::beginTransaction();

            $transaction = SeatTransaction::where('seat_id', $seatId)
                ->where('user_id', $validated['user_id'])
                ->where('status', 'pending')
                ->firstOrFail();

            // Update transaction status
            $transaction->update(['status' => 'cancelled']);

            // Update seat status
            $seat = Seat::findOrFail($seatId);
            $seat->update(['status' => 'available']);

            DB::commit();

            // Broadcast update
            broadcast(new TransactionUpdated($transaction, $seat))->toOthers();

            return response()->json([
                'message' => 'Seat released successfully',
                'transaction' => $transaction,
                'seat' => $seat
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'error' => 'Failed to release seat',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function confirm(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'user_id' => 'required|uuid',
            'seat_ids' => 'required|array',
            'seat_ids.*' => 'required|uuid|exists:seats,seat_id'
        ]);

        try {
            DB::beginTransaction();

            $updatedSeats = [];
            $updatedTransactions = [];

            foreach ($validated['seat_ids'] as $seatId) {
                // Find and validate transaction
                $transaction = SeatTransaction::where('seat_id', $seatId)
                    ->where('user_id', $validated['user_id'])
                    ->where('status', 'pending')
                    ->firstOrFail();

                if (Carbon::parse($transaction->expiry_time)->isPast()) {
                    throw new \Exception('Transaction has expired');
                }

                // Update transaction status
                $transaction->update(['status' => 'completed']);
                $updatedTransactions[] = $transaction;

                // Update seat status
                $seat = Seat::findOrFail($seatId);
                $seat->update(['status' => 'booked']);
                $updatedSeats[] = $seat;

                // Broadcast updates
                broadcast(new TransactionUpdated($transaction, $seat))->toOthers();
            }

            DB::commit();

            return response()->json([
                'message' => 'Seats booked successfully',
                'transactions' => $updatedTransactions,
                'seats' => $updatedSeats
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'error' => 'Failed to confirm booking',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function clearExpiredTransactions(): JsonResponse
    {
        try {
            DB::beginTransaction();

            // Find expired transactions
            $expiredTransactions = SeatTransaction::where('status', 'pending')
                ->where('expiry_time', '<', now())
                ->get();

            foreach ($expiredTransactions as $transaction) {
                // Update transaction status
                $transaction->update(['status' => 'expired']);

                // Release seat
                $seat = Seat::find($transaction->seat_id);
                if ($seat) {
                    $seat->update(['status' => 'available']);
                    broadcast(new TransactionUpdated($transaction, $seat))->toOthers();
                }
            }

            DB::commit();

            return response()->json([
                'message' => 'Expired transactions cleared',
                'count' => count($expiredTransactions)
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'error' => 'Failed to clear expired transactions',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}