import axios from 'axios';
import React, { useEffect, useMemo, useState } from 'react';
import { Seat } from '../../types/seat';
import { TicketCategory } from '../../types/ticketCategory';

interface SeatCategoryAssignmentProps {
    venueId: string;
    eventId: string;
}

export const SeatCategoryAssignment: React.FC<SeatCategoryAssignmentProps> = ({
    venueId,
    eventId,
}) => {
    const [seats, setSeats] = useState<Seat[]>([]);
    const [categories, setCategories] = useState<TicketCategory[]>([]);
    const [selectedSeats, setSelectedSeats] = useState<string[]>([]);
    const [selectedCategory, setSelectedCategory] = useState<string>('');
    const [loading, setLoading] = useState(false);

    // Group seats by row for better organization
    const seatsByRow = useMemo(() => {
        const grouped = seats.reduce((acc: { [key: string]: Seat[] }, seat) => {
            const row = seat.seat_number.match(/[A-Z]+/)?.[0] || '';
            if (!acc[row]) {
                acc[row] = [];
            }
            acc[row].push(seat);
            return acc;
        }, {});

        // Sort rows alphabetically
        return Object.fromEntries(
            Object.entries(grouped).sort(([a], [b]) => a.localeCompare(b)),
        );
    }, [seats]);

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

    const handleSeatClick = (seatId: string) => {
        setSelectedSeats((prev) =>
            prev.includes(seatId)
                ? prev.filter((id) => id !== seatId)
                : [...prev, seatId],
        );
    };

    const handleRowSelect = (row: string) => {
        const rowSeats = seatsByRow[row];
        const allRowSeatIds = rowSeats.map((seat) => seat.seat_id);

        // If all seats in row are selected, deselect them
        const allSelected = allRowSeatIds.every((id) =>
            selectedSeats.includes(id),
        );

        if (allSelected) {
            setSelectedSeats((prev) =>
                prev.filter((id) => !allRowSeatIds.includes(id)),
            );
        } else {
            setSelectedSeats((prev) => [
                ...new Set([...prev, ...allRowSeatIds]),
            ]);
        }
    };

    const handleApplyCategory = async () => {
        if (!selectedCategory || selectedSeats.length === 0) return;

        setLoading(true);
        try {
            await axios.post('/api/seats/assign-category', {
                seat_ids: selectedSeats,
                ticket_category_id: selectedCategory,
            });

            // Refresh seats data
            const response = await axios.get(`/api/venues/${venueId}/seats`);
            setSeats(response.data);
            setSelectedSeats([]);
        } catch (error) {
            console.error('Error assigning category:', error);
        } finally {
            setLoading(false);
        }
    };

    const getTicketCategoryColor = (categoryId: string) => {
        return (
            categories.find((cat) => cat.ticket_category_id === categoryId)
                ?.color || '#gray'
        );
    };

    return (
        <div className="rounded-lg bg-white p-6 shadow-lg">
            <h2 className="mb-4 text-xl font-bold">
                Assign Categories to Seats
            </h2>

            {/* Category Selection */}
            <div className="mb-6">
                <h3 className="mb-2 text-lg font-semibold">Select Category:</h3>
                <div className="flex gap-4">
                    {categories.map((category) => (
                        <button
                            key={category.ticket_category_id}
                            onClick={() =>
                                setSelectedCategory(category.ticket_category_id)
                            }
                            className={`rounded px-4 py-2 ${
                                selectedCategory === category.ticket_category_id
                                    ? 'ring-2 ring-blue-500 ring-offset-2'
                                    : ''
                            } `}
                            style={{
                                backgroundColor: category.color,
                                color: 'white',
                            }}
                        >
                            {category.name}
                        </button>
                    ))}
                </div>
            </div>

            {/* Seat Grid */}
            <div className="space-y-4">
                {Object.entries(seatsByRow).map(([row, rowSeats]) => (
                    <div key={row} className="rounded border p-4">
                        <div className="mb-2 flex items-center">
                            <button
                                onClick={() => handleRowSelect(row)}
                                className="mr-4 rounded bg-gray-200 px-3 py-1 hover:bg-gray-300"
                            >
                                Row {row}
                            </button>
                            <div className="flex flex-wrap gap-2">
                                {rowSeats.map((seat) => (
                                    <button
                                        key={seat.seat_id}
                                        onClick={() =>
                                            handleSeatClick(seat.seat_id)
                                        }
                                        className={`h-8 w-8 rounded ${
                                            selectedSeats.includes(seat.seat_id)
                                                ? 'ring-2 ring-blue-500'
                                                : ''
                                        } `}
                                        style={{
                                            backgroundColor:
                                                getTicketCategoryColor(
                                                    seat.ticket_category_id,
                                                ),
                                        }}
                                        title={seat.seat_number}
                                    >
                                        {seat.seat_number.replace(row, '')}
                                    </button>
                                ))}
                            </div>
                        </div>
                    </div>
                ))}
            </div>

            {/* Apply Button */}
            <div className="mt-6">
                <button
                    onClick={handleApplyCategory}
                    disabled={
                        loading ||
                        !selectedCategory ||
                        selectedSeats.length === 0
                    }
                    className={`rounded px-4 py-2 text-white ${
                        loading ||
                        !selectedCategory ||
                        selectedSeats.length === 0
                            ? 'bg-gray-400'
                            : 'bg-blue-500 hover:bg-blue-600'
                    } `}
                >
                    {loading ? 'Applying...' : 'Apply Category'}
                </button>
                {selectedSeats.length > 0 && (
                    <span className="ml-4 text-gray-600">
                        {selectedSeats.length} seats selected
                    </span>
                )}
            </div>
        </div>
    );
};
