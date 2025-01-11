import axios from 'axios';
import React, { useEffect, useState } from 'react';
import { DndProvider } from 'react-dnd';
import { HTML5Backend } from 'react-dnd-html5-backend';
import { Seat } from '../../types/seat';
import { TicketCategory } from '../../types/ticketCategory';

interface SeatEditorProps {
    venueId: string;
    eventId: string;
}

export const SeatEditor: React.FC<SeatEditorProps> = ({ venueId, eventId }) => {
    const [seats, setSeats] = useState<Seat[]>([]);
    const [categories, setCategories] = useState<TicketCategory[]>([]);
    const [selectedSeats, setSelectedSeats] = useState<string[]>([]);
    const [editMode, setEditMode] = useState<
        'category' | 'status' | 'position'
    >('category');

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

    const handleSeatSelect = (seatId: string) => {
        setSelectedSeats((prev) =>
            prev.includes(seatId)
                ? prev.filter((id) => id !== seatId)
                : [...prev, seatId],
        );
    };

    const handleStatusChange = async (status: Seat['status']) => {
        try {
            const updates = selectedSeats.map((seatId) => ({
                seat_id: seatId,
                status,
            }));

            await axios.post('/api/seats/bulk-update', { seats: updates });

            // Refresh seats after update
            const response = await axios.get(`/api/venues/${venueId}/seats`);
            setSeats(response.data);
            setSelectedSeats([]);
        } catch (error) {
            console.error('Error updating seats:', error);
        }
    };

    const handlePositionUpdate = async (seatId: string, position: string) => {
        try {
            await axios.patch(`/api/seats/${seatId}`, { position });

            // Refresh seats after update
            const response = await axios.get(`/api/venues/${venueId}/seats`);
            setSeats(response.data);
        } catch (error) {
            console.error('Error updating seat position:', error);
        }
    };

    return (
        <DndProvider backend={HTML5Backend}>
            <div className="rounded-lg bg-white p-6 shadow-lg">
                <div className="mb-6 flex gap-4">
                    <button
                        className={`rounded-lg px-4 py-2 ${
                            editMode === 'category'
                                ? 'bg-blue-500 text-white'
                                : 'bg-gray-200'
                        }`}
                        onClick={() => setEditMode('category')}
                    >
                        Category Mode
                    </button>
                    <button
                        className={`rounded-lg px-4 py-2 ${
                            editMode === 'status'
                                ? 'bg-blue-500 text-white'
                                : 'bg-gray-200'
                        }`}
                        onClick={() => setEditMode('status')}
                    >
                        Status Mode
                    </button>
                    <button
                        className={`rounded-lg px-4 py-2 ${
                            editMode === 'position'
                                ? 'bg-blue-500 text-white'
                                : 'bg-gray-200'
                        }`}
                        onClick={() => setEditMode('position')}
                    >
                        Position Mode
                    </button>
                </div>

                {editMode === 'status' && (
                    <div className="mb-6 flex gap-4">
                        <button
                            className="rounded-lg bg-green-500 px-4 py-2 text-white"
                            onClick={() => handleStatusChange('available')}
                        >
                            Set Available
                        </button>
                        <button
                            className="rounded-lg bg-yellow-500 px-4 py-2 text-white"
                            onClick={() => handleStatusChange('reserved')}
                        >
                            Set Reserved
                        </button>
                        <button
                            className="rounded-lg bg-red-500 px-4 py-2 text-white"
                            onClick={() => handleStatusChange('booked')}
                        >
                            Set Booked
                        </button>
                    </div>
                )}

                {editMode === 'category' && categories.length > 0 && (
                    <div className="mb-6 flex flex-wrap gap-4">
                        {categories.map((category) => (
                            <button
                                key={category.ticket_category_id}
                                className="rounded-lg px-4 py-2 text-white"
                                style={{ backgroundColor: category.color }}
                                onClick={() => {
                                    // Handle category assignment
                                }}
                            >
                                {category.name}
                            </button>
                        ))}
                    </div>
                )}

                <div className="grid w-full max-w-6xl grid-cols-[repeat(auto-fit,minmax(2rem,1fr))] gap-2">
                    {seats.map((seat) => (
                        <div
                            key={seat.seat_id}
                            className={`relative ${selectedSeats.includes(seat.seat_id) ? 'ring-2 ring-blue-500' : ''} `}
                        >
                            {editMode === 'position' ? (
                                <DraggableSeat
                                    seat={seat}
                                    onPositionChange={(newPosition) =>
                                        handlePositionUpdate(
                                            seat.seat_id,
                                            newPosition,
                                        )
                                    }
                                />
                            ) : (
                                <button
                                    className={`h-8 w-8 rounded-md ${getSeatStatusColor(seat.status)} flex items-center justify-center text-sm font-medium text-white transition-opacity duration-200 hover:opacity-80`}
                                    onClick={() =>
                                        handleSeatSelect(seat.seat_id)
                                    }
                                >
                                    {seat.seat_number}
                                </button>
                            )}
                        </div>
                    ))}
                </div>
            </div>
        </DndProvider>
    );
};

// Components for drag and drop functionality
interface DraggableSeatProps {
    seat: Seat;
    onPositionChange: (newPosition: string) => void;
}

const DraggableSeat: React.FC<DraggableSeatProps> = ({
    seat,
    onPositionChange,
}) => {
    const [{ isDragging }, drag] = useDrag({
        type: 'SEAT',
        item: { id: seat.seat_id, position: seat.position },
        collect: (monitor) => ({
            isDragging: monitor.isDragging(),
        }),
    });

    return (
        <div
            ref={drag}
            style={{ opacity: isDragging ? 0.5 : 1 }}
            className={`h-8 w-8 cursor-move rounded-md ${getSeatStatusColor(seat.status)} flex items-center justify-center text-sm font-medium text-white`}
        >
            {seat.seat_number}
        </div>
    );
};

// Utility function for seat status colors
const getSeatStatusColor = (status: Seat['status']): string => {
    switch (status) {
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
