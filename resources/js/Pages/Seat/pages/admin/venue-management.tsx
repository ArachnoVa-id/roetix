import React from 'react';
import { VenueLayout } from '../../components/VenueManager/VenueLayout';

const VenueManagement: React.FC = () => {
    return (
        <div className="container mx-auto py-8">
            <VenueLayout venueId="your-venue-id" eventId="your-event-id" />
        </div>
    );
};

export default VenueManagement;
