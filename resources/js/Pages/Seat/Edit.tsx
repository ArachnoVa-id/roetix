import { Head, router } from '@inertiajs/react';
import React, { useState } from 'react';
import SeatMapEditor, { UpdatedSeats } from './SeatMapEditor';
import { Layout } from './types';

interface Event {
    event_id: string;
    name: string;
    venue_id: string;
    team_id: string;
}

interface Venue {
    venue_id: string;
    name: string;
}

interface Props {
    layout: Layout;
    event: Event;
    venue: Venue;
    ticketTypes: string[];
}

const Edit: React.FC<Props> = ({ layout, event, venue, ticketTypes }) => {
    const [error, setError] = useState<string | null>(null);
    const [success, setSuccess] = useState<string | null>(null);

    const handleSave = (updatedSeats: UpdatedSeats[]) => {
        setError(null);
        setSuccess(null);

        // Add visitOptions for ensuring credentials are sent
        const visitOptions = {
            preserveScroll: true,
            preserveState: true,
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
            },
            onBefore: () => {
                console.log('Starting request with seats:', updatedSeats);
            },
            onSuccess: () => {
                console.log('Update successful');
                setSuccess('Tickets updated successfully');
            },
            onError: (errors: unknown) => {
                if (errors instanceof Error) {
                    console.error('Update failed:', errors.message);
                } else {
                    console.error('Update failed:', errors);
                }
                setError('Failed to update tickets. Please try again.');
            },
            onFinish: () => {},
        };

        router.post(
            '/seats/update-event-seats',
            {
                event_id: event.event_id,
                seats: updatedSeats.map((seat) => ({ ...seat })),
            },
            visitOptions,
        );
    };

    return (
        <>
            <Head title="Configure Event Seats" />
            <div className="py-12">
                <div className="w-full px-4">
                    <div className="overflow-hidden bg-white shadow-xl sm:rounded-lg">
                        <div className="p-6">
                            <h2 className="mb-4 text-2xl font-bold">
                                Configure Seats for "{event.name}"
                            </h2>
                            <p className="mb-4 text-gray-600">
                                Venue: {venue.name} | Event ID: {event.event_id}
                            </p>

                            {error && (
                                <div className="mb-4 rounded bg-red-100 p-4 text-red-700">
                                    {error}
                                </div>
                            )}

                            {success && (
                                <div className="mb-4 rounded bg-green-100 p-4 text-green-700">
                                    {success}
                                </div>
                            )}

                            <div className="overflow-x-auto">
                                <div className="min-w-max">
                                    <SeatMapEditor
                                        layout={layout}
                                        onSave={handleSave}
                                        ticketTypes={ticketTypes}
                                    />
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </>
    );
};

export default Edit;
