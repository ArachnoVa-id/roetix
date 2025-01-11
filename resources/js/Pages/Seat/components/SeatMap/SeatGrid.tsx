import axios from 'axios';
import React, { useEffect, useState } from 'react';
import { useWebSocket } from '../../hooks/useWebSocket';
import { Seat } from '../../types/seat';
import { TicketCategory } from '../../types/ticketCategory';

interface SeatGridProps {
    venueId: string;
    eventId: string;
    onSeatSelect?: (seat: Seat) => void;
    readOnly?: boolean;
}

export const SeatGrid: React.FC<SeatGridProps> = ({
    venueId,
    eventId,
    onSeatSelect,
    readOnly = false,
}) => {
    const [seats, setSeats] = useState<Seat[]>([]);
    const [categories, setCategories] = useState<TicketCategory[]>([]);
    const [selectedSeats, setSelectedSeats] = useState<Seat[]>([]);

    // Connect to WebSocket for real-time updates
    useWebSocket(`seats.${venueId}`, (data) => {
        const updatedSeat = data.seat as Seat;
        setSeats((prevSeats) =>
            prevSeats.map((seat) =>
                seat.seat_id === updatedSeat.seat_id ? updatedSeat : seat,
            ),
        );
    });

    useEffect(() => {
        const fetchData = async () => {
            try {
                const [seatsResponse, categoriesResponse] = await Promise.all([
                    axios.get(`/api/venues/${venueId}/seats`),
                    axios.get(`/api/events/${eventId}/ticket-categories`),
                ]);

                setSeats(seatsResponse.data);
                setCategories(categoriesResponse.data);
            } catch (error) {
                console.error('Error fetching data:', error);
            }
        };

        fetchData();
    }, [venueId, eventId]);

    const handleSeatClick = async (seat: Seat) => {
        if (readOnly || !onSeatSelect) return;

        try {
            if (seat.status === 'available') {
                const response = await axios.patch(
                    `/api/seats/${seat.seat_id}/status`,
                    {
                        status: 'in_transaction',
                    },
                );

                setSelectedSeats((prev) => [...prev, response.data]);
                onSeatSelect(response.data);
            } else if (seat.status === 'in_transaction') {
                const response = await axios.patch(
                    `/api/seats/${seat.seat_id}/status`,
                    {
                        status: 'available',
                    },
                );

                setSelectedSeats((prev) =>
                    prev.filter((s) => s.seat_id !== seat.seat_id),
                );
                onSeatSelect(response.data);
            }
        } catch (error) {
            console.error('Error updating seat status:', error);
        }
    };

    const getSeatColor = (seat: Seat): string => {
        switch (seat.status) {
            case 'available':
                return 'bg-green-500';
            case 'booked':
                return 'bg-red-500';
            case 'reserved':
                return 'bg-yellow-500';
            case 'in_transaction':
                return 'bg-blue-500';
            default:
                return 'bg-gray-500';
        }
    };

    return (
        <div className="flex flex-col items-center p-4">
            <div className="grid w-full max-w-4xl grid-cols-[repeat(auto-fit,minmax(2rem,1fr))] gap-2">
                {seats.map((seat) => (
                    <button
                        key={seat.seat_id}
                        className={`h-8 w-8 rounded-md ${getSeatColor(seat)} ${!readOnly && seat.status === 'available' ? 'hover:opacity-80' : ''} flex items-center justify-center text-sm font-medium text-white transition-opacity duration-200`}
                        onClick={() => handleSeatClick(seat)}
                        disabled={
                            readOnly ||
                            seat.status === 'booked' ||
                            seat.status === 'reserved'
                        }
                        title={`${seat.seat_number} - ${seat.status}`}
                    >
                        {seat.seat_number}
                    </button>
                ))}
            </div>

            {/* Legend */}
            <div className="mt-6 flex gap-4">
                <div className="flex items-center gap-2">
                    <div className="h-4 w-4 rounded bg-green-500" />
                    <span>Available</span>
                </div>
                <div className="flex items-center gap-2">
                    <div className="h-4 w-4 rounded bg-red-500" />
                    <span>Booked</span>
                </div>
                <div className="flex items-center gap-2">
                    <div className="h-4 w-4 rounded bg-yellow-500" />
                    <span>Reserved</span>
                </div>
                <div className="flex items-center gap-2">
                    <div className="h-4 w-4 rounded bg-blue-500" />
                    <span>In Transaction</span>
                </div>
            </div>

            {/* Selected Seats Summary */}
            {selectedSeats.length > 0 && (
                <div className="mt-6 w-full max-w-4xl rounded-lg bg-gray-100 p-4">
                    <h3 className="mb-2 font-semibold">Selected Seats:</h3>
                    <div className="flex flex-wrap gap-2">
                        {selectedSeats.map((seat) => (
                            <div
                                key={seat.seat_id}
                                className="rounded-full bg-white px-3 py-1 shadow-sm"
                            >
                                {seat.seat_number}
                            </div>
                        ))}
                    </div>
                </div>
            )}
        </div>
    );
};
