import axios from 'axios';
import React, { useCallback, useEffect, useState } from 'react';
import { useWebSocket } from '../../hooks/useWebSocket';
import { Seat } from '../../types/seat';
import { SeatTransaction } from '../../types/transaction';

interface SeatTransactionManagerProps {
    venueId: string;
    userId: string;
    onTransactionComplete?: (seats: Seat[]) => void;
}

export const SeatTransactionManager: React.FC<SeatTransactionManagerProps> = ({
    venueId,
    userId,
    onTransactionComplete,
}) => {
    const [selectedSeats, setSelectedSeats] = useState<Seat[]>([]);
    const [transactions, setTransactions] = useState<SeatTransaction[]>([]);
    const [loading, setLoading] = useState(false);
    const [error, setError] = useState<string | null>(null);

    // Listen for real-time transaction updates
    useWebSocket(`transactions.${venueId}`, (data) => {
        handleTransactionUpdate(data);
    });

    const handleTransactionUpdate = useCallback((data: any) => {
        const { transaction, seat } = data;

        // Update transaction list
        setTransactions((prev) => {
            const index = prev.findIndex(
                (t) => t.transaction_id === transaction.transaction_id,
            );
            if (index === -1) {
                return [...prev, transaction];
            }
            const newTransactions = [...prev];
            newTransactions[index] = transaction;
            return newTransactions;
        });

        // Update selected seats if necessary
        if (seat) {
            setSelectedSeats((prev) => {
                const index = prev.findIndex((s) => s.seat_id === seat.seat_id);
                if (index === -1) return prev;
                const newSeats = [...prev];
                newSeats[index] = seat;
                return newSeats;
            });
        }
    }, []);

    const handleSeatSelection = async (seat: Seat) => {
        try {
            setError(null);
            setLoading(true);

            // Check if seat is already selected
            if (selectedSeats.some((s) => s.seat_id === seat.seat_id)) {
                await releaseSeat(seat.seat_id);
                setSelectedSeats((prev) =>
                    prev.filter((s) => s.seat_id !== seat.seat_id),
                );
            } else {
                // Check maximum selection limit
                if (selectedSeats.length >= 5) {
                    setError('Maximum 5 seats can be selected');
                    return;
                }

                // Create new transaction
                const response = await axios.post('/api/seat-transactions', {
                    seat_id: seat.seat_id,
                    user_id: userId,
                });

                setSelectedSeats((prev) => [
                    ...prev,
                    { ...seat, status: 'in_transaction' },
                ]);
                setTransactions((prev) => [...prev, response.data]);
            }
        } catch (error) {
            setError('Failed to process seat selection');
            console.error('Seat selection error:', error);
        } finally {
            setLoading(false);
        }
    };

    const releaseSeat = async (seatId: string) => {
        try {
            await axios.post(`/api/seat-transactions/${seatId}/release`, {
                user_id: userId,
            });
        } catch (error) {
            console.error('Error releasing seat:', error);
            throw error;
        }
    };

    const handleConfirmSelection = async () => {
        if (selectedSeats.length === 0) return;

        try {
            setLoading(true);
            setError(null);

            const response = await axios.post(
                '/api/seat-transactions/confirm',
                {
                    user_id: userId,
                    seat_ids: selectedSeats.map((seat) => seat.seat_id),
                },
            );

            onTransactionComplete?.(response.data.seats);
            setSelectedSeats([]);
        } catch (error) {
            setError('Failed to confirm seat selection');
            console.error('Confirmation error:', error);
        } finally {
            setLoading(false);
        }
    };

    const handleCancelSelection = async () => {
        try {
            setLoading(true);
            setError(null);

            for (const seat of selectedSeats) {
                await releaseSeat(seat.seat_id);
            }

            setSelectedSeats([]);
        } catch (error) {
            setError('Failed to cancel selection');
            console.error('Cancellation error:', error);
        } finally {
            setLoading(false);
        }
    };

    return (
        <div className="space-y-4">
            {/* Selected Seats Summary */}
            {selectedSeats.length > 0 && (
                <div className="rounded-lg bg-white p-4 shadow">
                    <h3 className="mb-2 text-lg font-semibold">
                        Selected Seats
                    </h3>
                    <div className="mb-4 flex flex-wrap gap-2">
                        {selectedSeats.map((seat) => (
                            <div
                                key={seat.seat_id}
                                className="flex items-center gap-2 rounded-full bg-blue-100 px-3 py-1"
                            >
                                <span>{seat.seat_number}</span>
                                <button
                                    onClick={() => handleSeatSelection(seat)}
                                    className="text-red-500 hover:text-red-700"
                                >
                                    ×
                                </button>
                            </div>
                        ))}
                    </div>
                    <div className="flex gap-3">
                        <button
                            onClick={handleConfirmSelection}
                            disabled={loading || selectedSeats.length === 0}
                            className={`rounded px-4 py-2 text-white ${
                                loading || selectedSeats.length === 0
                                    ? 'bg-gray-400'
                                    : 'bg-green-500 hover:bg-green-600'
                            } `}
                        >
                            {loading ? 'Processing...' : 'Confirm Selection'}
                        </button>
                        <button
                            onClick={handleCancelSelection}
                            disabled={loading}
                            className="rounded bg-red-500 px-4 py-2 text-white hover:bg-red-600 disabled:bg-gray-400"
                        >
                            Cancel
                        </button>
                    </div>
                </div>
            )}

            {/* Error Display */}
            {error && (
                <div className="rounded border border-red-400 bg-red-100 px-4 py-3 text-red-700">
                    {error}
                </div>
            )}

            {/* Transaction Timer */}
            {selectedSeats.length > 0 && (
                <TransactionTimer
                    transactions={transactions}
                    onExpiry={handleCancelSelection}
                />
            )}
        </div>
    );
};

// Transaction Timer Component
interface TransactionTimerProps {
    transactions: SeatTransaction[];
    onExpiry: () => void;
}

const TransactionTimer: React.FC<TransactionTimerProps> = ({
    transactions,
    onExpiry,
}) => {
    const [timeLeft, setTimeLeft] = useState<number>(0);

    useEffect(() => {
        if (transactions.length === 0) return;

        // Find the earliest expiry time
        const earliestExpiry = Math.min(
            ...transactions.map((t) => new Date(t.expiry_time).getTime()),
        );

        const updateTimer = () => {
            const now = new Date().getTime();
            const difference = earliestExpiry - now;

            if (difference <= 0) {
                onExpiry();
                return;
            }

            setTimeLeft(Math.floor(difference / 1000));
        };

        updateTimer();
        const interval = setInterval(updateTimer, 1000);

        return () => clearInterval(interval);
    }, [transactions, onExpiry]);

    if (timeLeft <= 0) return null;

    const minutes = Math.floor(timeLeft / 60);
    const seconds = timeLeft % 60;

    return (
        <div className="text-center">
            <p className="text-sm text-gray-600">
                Time remaining: {minutes}:{seconds.toString().padStart(2, '0')}
            </p>
        </div>
    );
};
