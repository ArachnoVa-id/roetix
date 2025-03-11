import { Head } from '@inertiajs/react';
import React, { useState } from 'react';
import SeatMapEditor, { UpdatedSeats } from './SeatMapEditor';
import { Layout, SeatItem } from './types';

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
    // We don't need to check authorization here anymore since middleware handles it

    const [error, setError] = useState<string | null>(null);
    const [success, setSuccess] = useState<string | null>(null);
    const [currentLayout, setCurrentLayout] = useState<Layout>(layout);

    const handleSave = (updatedSeats: UpdatedSeats[]) => {
        setError(null);
        setSuccess(null);

        // Optimistically update the UI immediately
        const updatedLayout = { ...currentLayout };
        updatedSeats.forEach((update) => {
            const seatToUpdate = updatedLayout.items.find(
                (item) =>
                    item.type === 'seat' &&
                    (item as SeatItem).seat_id === update.seat_id,
            ) as SeatItem | undefined;

            if (seatToUpdate) {
                seatToUpdate.status = update.status;
                seatToUpdate.ticket_type = update.ticket_type;
                seatToUpdate.price = update.price;
            }
        });

        setCurrentLayout(updatedLayout);

        // Use fetch directly to avoid Inertia JSON response error
        fetch('/seats/update-event-seats', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN':
                    document
                        .querySelector('meta[name="csrf-token"]')
                        ?.getAttribute('content') || '',
            },
            body: JSON.stringify({
                event_id: event.event_id,
                seats: updatedSeats.map((seat) => ({ ...seat })),
            }),
            credentials: 'include',
        })
            .then((response) => response.json())
            .then((data) => {
                if (data.success) {
                    setSuccess('Tickets updated successfully');
                    // Success already reflected in UI

                    // Hide success message after 2 seconds
                    setTimeout(() => {
                        setSuccess(null);
                    }, 2000);
                } else {
                    throw new Error(data.message || 'Unknown error occurred');
                }
            })
            .catch(() => {
                // Revert to original layout on error
                setCurrentLayout(layout);
                setError('Failed to update tickets. Please try again.');

                // Hide error message after 3 seconds
                setTimeout(() => {
                    setError(null);
                }, 3000);
            });
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
                                        layout={currentLayout}
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
