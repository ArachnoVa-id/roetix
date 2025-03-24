import Toaster from '@/Components/novatix/Toaster';
import useToaster from '@/hooks/useToaster';
import { Head } from '@inertiajs/react';
import React, { useEffect, useState } from 'react';
import SeatMapEditor, { UpdatedSeats } from './SeatMapEditor';
import { Layout, SeatItem } from './types';

declare global {
    interface Window {
        eventTimelines?: Timeline[];
    }
}

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

const Edit: React.FC<Props> = ({
    layout,
    event,
    venue,
    ticketTypes,
    categoryColors = {},
    currentTimeline, // This will be overridden by automatic timeline selection
    ticketCategories = [],
    categoryPrices = [],
}) => {
    const { toasterState, showSuccess, showError, hideToaster } = useToaster();
    const [currentLayout, setCurrentLayout] = useState<Layout>(layout);
    const [categoryNameToPriceMap, setCategoryNameToPriceMap] = useState<
        Record<string, number>
    >({});
    const [categoryColorMap, setCategoryColorMap] = useState<
        Record<string, string>
    >({});

    // Add new state for automatically determined timeline
    const [activeTimeline, setActiveTimeline] = useState<Timeline | undefined>(
        currentTimeline,
    );

    // Function to generate styles for category displays
    const getCategoryStyle = (category: string) => {
        return {
            backgroundColor: categoryColors[category]
                ? categoryColors[category] + '33'
                : undefined,
            borderColor: categoryColors[category],
        };
    };

    // Process timeline data directly from props rather than from API
    useEffect(() => {
        // Get current date with Jakarta timezone
        const formatJakartaTime = (date = new Date()) => {
            return new Date(date).toLocaleString('en-US', {
                timeZone: 'Asia/Jakarta',
                hour12: false,
            });
        };

        // Function to determine which timeline is active based on current date
        const determineActiveTimeline = (
            timelines: Timeline[] = [],
        ): Timeline | undefined => {
            const now = new Date(
                new Date().toLocaleString('en-US', {
                    timeZone: 'Asia/Jakarta',
                }),
            );

            return (
                timelines.find(
                    (t) =>
                        now >= new Date(t.start_date) &&
                        now <= new Date(t.end_date),
                ) ||
                timelines
                    .filter((t) => new Date(t.start_date) > now)
                    .sort(
                        (a, b) =>
                            new Date(a.start_date).getTime() -
                            new Date(b.start_date).getTime(),
                    )[0]
            );
        };

        // If we have timeline data in props, use that
        if (
            ticketCategories.length > 0 &&
            categoryPrices.length > 0 &&
            currentTimeline
        ) {
            // Get all unique timeline IDs from category prices
            const timelineIds = Array.from(
                new Set(categoryPrices.map((price) => price.timeline_id)),
            );

            // Extract all timelines from the current timeline and categoryPrices
            const allTimelines: Timeline[] = [];

            // Add current timeline to the list
            allTimelines.push(currentTimeline);

            // Add any additional timelines that might be referenced in categoryPrices
            // You may need to add these directly if they're available in props

            // Determine active timeline from available data
            const active = determineActiveTimeline(allTimelines);

            if (active) {
                setActiveTimeline(active);
                console.log(
                    `[${formatJakartaTime()}] Active timeline: ${active.name} (${active.start_date} to ${active.end_date})`,
                );
            } else {
                // If no active timeline found, keep using the current one from props
                console.log(
                    `[${formatJakartaTime()}] No active timeline found based on current date, using provided default`,
                );
            }
        }
    }, [currentTimeline, ticketCategories, categoryPrices]);

    // Process category prices based on active timeline and categories
    useEffect(() => {
        if (
            activeTimeline &&
            ticketCategories.length > 0 &&
            categoryPrices.length > 0
        ) {
            const formatJakartaTime = (date = new Date()) => {
                return new Date(date).toLocaleString('en-US', {
                    timeZone: 'Asia/Jakarta',
                    hour12: false,
                });
            };

            console.log(
                `[${formatJakartaTime()}] Updating price mappings for timeline: ${activeTimeline.name}`,
            );

            const priceMap: Record<string, number> = {};
            const colorMap: Record<string, string> = {};

            // Create a mapping of category IDs to category names and colors
            const categoryIdToNameMap: Record<string, string> = {};
            ticketCategories.forEach((category) => {
                categoryIdToNameMap[category.ticket_category_id] =
                    category.name;
                colorMap[category.name] = category.color;
            });

            // Find prices for the active timeline
            const currentTimelinePrices = categoryPrices.filter(
                (price) => price.timeline_id === activeTimeline.timeline_id,
            );

            // Map category IDs to their prices and then to category names
            currentTimelinePrices.forEach((price) => {
                const categoryName =
                    categoryIdToNameMap[price.ticket_category_id];
                if (categoryName) {
                    priceMap[categoryName] = price.price;
                }
            });

            // Update state with the new mappings
            setCategoryNameToPriceMap(priceMap);
            setCategoryColorMap(colorMap);

            console.log('Updated price map:', priceMap);
            console.log('Updated color map:', colorMap);
        }
    }, [activeTimeline, ticketCategories, categoryPrices]);

    const handleSave = (updatedSeats: UpdatedSeats[]) => {
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
                    showSuccess('Tickets updated successfully');
                    // Success already reflected in UI
                } else {
                    throw new Error(data.message || 'Unknown error occurred');
                }
            })
            .catch((err) => {
                // Log the error for debugging
                console.error('Error updating seats:', err);

                // Revert to original layout on error
                setCurrentLayout(layout);
                showError(
                    `Failed to update tickets. Please try again. ${err.message}`,
                );
            });
    };

    return (
        <>
            <Head title="Configure Event Seats" />
            <div className="py-12">
                <div className="mx-auto px-4">
                    <div className="overflow-hidden rounded-lg bg-white shadow-md">
                        <div className="p-6">
                            <div className="mb-6 flex flex-wrap items-center">
                                <div>
                                    <h2 className="text-2xl font-semibold text-gray-900">
                                        {event.name}
                                    </h2>
                                    <p className="text-gray-600">
                                        Venue: {venue.name} | Event ID:{' '}
                                        {event.event_id}
                                    </p>
                                </div>
                                <button
                                    className="ml-auto rounded-lg bg-blue-600 px-4 py-2 font-bold text-white transition hover:bg-blue-700"
                                    onClick={() => window.history.back()}
                                >
                                    Back to Dashboard
                                </button>
                            </div>

                            {currentTimeline ? (
                                <div className="mb-6 overflow-hidden rounded-xl border border-blue-100 bg-gradient-to-r from-blue-50 to-indigo-50 shadow-sm">
                                    <div className="border-b border-blue-100 bg-blue-100/30 p-4">
                                        <h3 className="flex items-center gap-2 font-semibold text-blue-800">
                                            <svg
                                                xmlns="http://www.w3.org/2000/svg"
                                                width="18"
                                                height="18"
                                                viewBox="0 0 24 24"
                                                fill="none"
                                                stroke="currentColor"
                                                strokeWidth="2"
                                                strokeLinecap="round"
                                                strokeLinejoin="round"
                                            >
                                                <rect
                                                    x="3"
                                                    y="4"
                                                    width="18"
                                                    height="18"
                                                    rx="2"
                                                    ry="2"
                                                ></rect>
                                                <line
                                                    x1="16"
                                                    y1="2"
                                                    x2="16"
                                                    y2="6"
                                                ></line>
                                                <line
                                                    x1="8"
                                                    y1="2"
                                                    x2="8"
                                                    y2="6"
                                                ></line>
                                                <line
                                                    x1="3"
                                                    y1="10"
                                                    x2="21"
                                                    y2="10"
                                                ></line>
                                            </svg>
                                            Current Timeline:{' '}
                                            {currentTimeline.name}
                                        </h3>
                                        <p className="mt-1 flex items-center gap-1 text-sm text-blue-600">
                                            <svg
                                                xmlns="http://www.w3.org/2000/svg"
                                                width="14"
                                                height="14"
                                                viewBox="0 0 24 24"
                                                fill="none"
                                                stroke="currentColor"
                                                strokeWidth="2"
                                                strokeLinecap="round"
                                                strokeLinejoin="round"
                                            >
                                                <circle
                                                    cx="12"
                                                    cy="12"
                                                    r="10"
                                                ></circle>
                                                <polyline points="12 6 12 12 16 14"></polyline>
                                            </svg>
                                            {new Date(
                                                currentTimeline.start_date,
                                            ).toLocaleDateString()}{' '}
                                            -{' '}
                                            {new Date(
                                                currentTimeline.end_date,
                                            ).toLocaleDateString()}
                                        </p>
                                    </div>
                                    <div className="p-4">
                                        <p className="flex items-center gap-2 text-sm text-blue-500">
                                            <svg
                                                xmlns="http://www.w3.org/2000/svg"
                                                width="14"
                                                height="14"
                                                viewBox="0 0 24 24"
                                                fill="none"
                                                stroke="currentColor"
                                                strokeWidth="2"
                                                strokeLinecap="round"
                                                strokeLinejoin="round"
                                            >
                                                <circle
                                                    cx="12"
                                                    cy="12"
                                                    r="10"
                                                ></circle>
                                                <line
                                                    x1="12"
                                                    y1="8"
                                                    x2="12"
                                                    y2="12"
                                                ></line>
                                                <line
                                                    x1="12"
                                                    y1="16"
                                                    x2="12.01"
                                                    y2="16"
                                                ></line>
                                            </svg>
                                            Prices are managed in the ticket
                                            category settings and are applied
                                            automatically.
                                        </p>
                                    </div>
                                </div>
                            ) : (
                                <div className="mb-6 rounded-lg border-l-4 border-yellow-400 bg-yellow-50 p-4">
                                    <h3 className="text-lg font-semibold text-yellow-900">
                                        No Active Timeline
                                    </h3>
                                    <p className="text-yellow-700">
                                        Please set up a timeline for this event
                                        to configure pricing.
                                    </p>
                                </div>
                            )}

                            {Object.keys(categoryNameToPriceMap).length > 0 && (
                                <div className="mb-6">
                                    <h3 className="mb-2 text-lg font-semibold text-gray-900">
                                        Current Ticket Prices
                                    </h3>
                                    <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 md:grid-cols-3">
                                        {Object.entries(
                                            categoryNameToPriceMap,
                                        ).map(([category, price]) => (
                                            <div
                                                key={category}
                                                className="rounded-lg border p-4 text-center shadow-sm"
                                                style={{
                                                    backgroundColor:
                                                        categoryColorMap[
                                                            category
                                                        ] + '33',
                                                    borderColor:
                                                        categoryColorMap[
                                                            category
                                                        ],
                                                }}
                                            >
                                                <p className="font-semibold">
                                                    {category}
                                                </p>
                                                <p className="text-xl font-bold text-gray-800">
                                                    Rp {price.toLocaleString()}
                                                </p>
                                            </div>
                                        ))}
                                    </div>
                                </div>
                            )}

                            <div className="rounded-lg border bg-white p-4 shadow-sm">
                                <h3 className="mb-4 text-lg font-semibold text-gray-900">
                                    Seat Map Editor
                                </h3>
                                <SeatMapEditor
                                    layout={currentLayout}
                                    onSave={handleSave}
                                    ticketTypes={ticketTypes}
                                    categoryColors={categoryColorMap}
                                    currentTimeline={activeTimeline}
                                    categoryPrices={categoryNameToPriceMap}
                                />
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <Toaster
                message={toasterState.message}
                type={toasterState.type}
                isVisible={toasterState.isVisible}
                onClose={hideToaster}
            />
        </>
    );
};

export default Edit;
