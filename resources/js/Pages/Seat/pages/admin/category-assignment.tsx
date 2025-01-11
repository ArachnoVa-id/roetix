import React from 'react';
import { SeatCategoryAssignment } from '../../components/SeatMap/SeatCategoryAssignment';

const CategoryAssignmentPage: React.FC = () => {
    return (
        <div className="container mx-auto py-8">
            <SeatCategoryAssignment
                venueId="your-venue-id"
                eventId="your-event-id"
            />
        </div>
    );
};

export default CategoryAssignmentPage;
