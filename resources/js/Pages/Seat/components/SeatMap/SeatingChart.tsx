import React from 'react';
import { Seat } from '../../types/seat';
import { SeatGrid } from './SeatGrid';

interface SeatingChartProps {
    venueId: string;
    eventId: string;
    onSeatSelect?: (seat: Seat) => void;
    readOnly?: boolean;
}

export const SeatingChart: React.FC<SeatingChartProps> = ({
    venueId,
    eventId,
    onSeatSelect,
    readOnly,
}) => {
    return (
        <div className="rounded-lg bg-white p-6 shadow-lg">
            <h2 className="mb-6 text-2xl font-bold">Select Your Seats</h2>
            <SeatGrid
                venueId={venueId}
                eventId={eventId}
                onSeatSelect={onSeatSelect}
                readOnly={readOnly}
            />
        </div>
    );
};
