import { Head } from '@inertiajs/react';
import React, { useEffect, useState } from 'react';
import { createRoot } from 'react-dom/client';
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

interface Timeline {
    timeline_id: string;
    name: string;
    start_date: string;
    end_date: string;
}

interface TicketCategory {
    ticket_category_id: string;
    name: string;
    color: string;
}

interface CategoryPrice {
    ticket_category_id: string;
    timeline_id: string;
    price: number;
}

interface Props {
    layout: Layout;
    event: Event;
    venue: Venue;
    ticketTypes: string[];
    categoryColors?: Record<string, string>;
    currentTimeline?: Timeline;
    ticketCategories?: TicketCategory[];
    categoryPrices?: CategoryPrice[];
}

const mountElement = document.getElementById('seat-map-editor');

const Edit: React.FC<Props> = ({
    layout,
    event,
    venue,
    ticketTypes,
    categoryColors = {},
    currentTimeline,
    ticketCategories = [],
    categoryPrices = [],
}) => {
    const [error, setError] = useState<string | null>(null);
    const [success, setSuccess] = useState<string | null>(null);
    const [currentLayout, setCurrentLayout] = useState<Layout>(layout);

    // Create a mapping of category names to prices
    const [categoryNameToPriceMap, setCategoryNameToPriceMap] = useState<
        Record<string, number>
    >({});

    // Process category prices based on current timeline and categories
    useEffect(() => {
        if (
            currentTimeline &&
            ticketCategories.length > 0 &&
            categoryPrices.length > 0
        ) {
            const priceMap: Record<string, number> = {};

            // Create a mapping of category IDs to category names
            const categoryIdToNameMap: Record<string, string> = {};
            ticketCategories.forEach((category) => {
                categoryIdToNameMap[category.ticket_category_id] =
                    category.name;
            });

            // Find prices for the current timeline
            const currentTimelinePrices = categoryPrices.filter(
                (price) => price.timeline_id === currentTimeline.timeline_id,
            );

            // Map category IDs to their prices and then to category names
            currentTimelinePrices.forEach((price) => {
                const categoryName =
                    categoryIdToNameMap[price.ticket_category_id];
                if (categoryName) {
                    priceMap[categoryName] = price.price;
                }
            });

            setCategoryNameToPriceMap(priceMap);
        }
    }, [currentTimeline, ticketCategories, categoryPrices]);

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
                seatToUpdate.price = update.price; // Update the price in UI based on what's sent
            }
        });

        setCurrentLayout(updatedLayout);

        // Log the request payload for debugging
        const requestPayload = {
            event_id: event.event_id,
            seats: updatedSeats,
        };
        console.log('Sending request payload:', requestPayload);

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
            body: JSON.stringify(requestPayload),
            credentials: 'include',
        })
            .then((response) => {
                if (!response.ok) {
                    throw new Error(
                        `Status: ${response.status}, Status Text: ${response.statusText}`,
                    );
                }
                return response.json();
            })
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
            .catch((err) => {
                // Log the error for debugging
                console.error('Error updating seats:', err);

                // Revert to original layout on error
                setCurrentLayout(layout);
                setError(
                    `Failed to update tickets. Please try again. ${err.message}`,
                );

                // Hide error message after 5 seconds
                setTimeout(() => {
                    setError(null);
                }, 5000);
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

                            {currentTimeline && (
                                <div className="mb-4">
                                    <h3 className="text-lg font-semibold">
                                        Current Timeline Period
                                    </h3>
                                    <div className="mt-2 rounded-lg bg-blue-50 p-3">
                                        <div className="font-medium">
                                            {currentTimeline.name}
                                        </div>
                                        <div className="text-sm text-gray-600">
                                            {new Date(
                                                currentTimeline.start_date,
                                            ).toLocaleDateString()}{' '}
                                            -{' '}
                                            {new Date(
                                                currentTimeline.end_date,
                                            ).toLocaleDateString()}
                                        </div>
                                        <div className="mt-2 text-xs text-blue-500">
                                            Prices are managed in the ticket
                                            category settings for this timeline.
                                        </div>
                                    </div>
                                </div>
                            )}

                            {/* Display prices for reference */}
                            {Object.keys(categoryNameToPriceMap).length > 0 && (
                                <div className="mb-4">
                                    <h3 className="text-lg font-semibold">
                                        Current Ticket Prices
                                    </h3>
                                    <div className="mt-2 grid grid-cols-3 gap-4">
                                        {Object.entries(
                                            categoryNameToPriceMap,
                                        ).map(([category, price]) => (
                                            <div
                                                key={category}
                                                className="rounded-lg border p-3"
                                                style={{
                                                    backgroundColor:
                                                        categoryColors[category]
                                                            ? categoryColors[
                                                                  category
                                                              ] + '33'
                                                            : undefined, // Add transparency
                                                    borderColor:
                                                        categoryColors[
                                                            category
                                                        ],
                                                }}
                                            >
                                                <div className="font-medium">
                                                    {category}
                                                </div>
                                                <div className="text-lg font-bold">
                                                    Rp {price.toLocaleString()}
                                                </div>
                                            </div>
                                        ))}
                                    </div>
                                </div>
                            )}

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
                                        categoryColors={categoryColors}
                                        currentTimeline={currentTimeline}
                                        categoryPrices={categoryNameToPriceMap}
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

if (mountElement) {
    const layout = JSON.parse(mountElement.dataset.layout || '{}');
    const event = JSON.parse(mountElement.dataset.event || '{}');
    const venue = JSON.parse(mountElement.dataset.venue || '{}');
    const ticketTypes = JSON.parse(mountElement.dataset.tickettypes || '[]');

    const root = createRoot(mountElement);
    root.render(
        <Edit
            layout={layout}
            event={event}
            venue={venue}
            ticketTypes={ticketTypes}
        />,
    );
}

export default Edit;
