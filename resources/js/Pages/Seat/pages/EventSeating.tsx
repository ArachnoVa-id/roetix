import React from 'react';
import { SeatingChart } from '../components/SeatMap/SeatingChart';
import { Seat } from '../types/seat';

const EventSeating: React.FC = () => {
    const handleSeatSelect = (seat: Seat) => {
        console.log('Selected seat:', seat);
    };

    return (
        <div className="container mx-auto py-8">
            <SeatingChart
                venueId="your-venue-id"
                eventId="your-event-id"
                onSeatSelect={handleSeatSelect}
            />
        </div>
    );
};

export default EventSeating;
