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
    const determineActiveTimeline = (timelines: Timeline[] = []): Timeline | undefined => {
        // Get current date in Jakarta time
        const jakartaTime = new Date().toLocaleString('en-US', { timeZone: 'Asia/Jakarta' });
        const now = new Date(jakartaTime);
        
        console.log('Current Jakarta time:', jakartaTime);
        console.log('Timelines to check:', timelines.map(t => ({
            name: t.name,
            start: t.start_date,
            end: t.end_date
        })));
        
        // Find the timeline that includes current Jakarta time
        const active = timelines.find((timeline) => {
            const startDate = new Date(timeline.start_date);
            const endDate = new Date(timeline.end_date);
            return now >= startDate && now <= endDate;
        });
    
        // If no timeline is active, find the next upcoming one
        if (!active && timelines.length > 0) {
            const upcoming = timelines
                .filter(t => new Date(t.start_date) > now)
                .sort((a, b) => new Date(a.start_date).getTime() - new Date(b.start_date).getTime())[0];
                
            if (upcoming) {
                return upcoming;
            }
        }
    
        return active;
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
                <div className="w-full px-4">
                    <div className="overflow-hidden bg-white shadow-xl sm:rounded-lg">
                        <div className="p-6">
                            <div className="flex w-full flex-wrap">
                                <div className="flex flex-col">
                                    <h2 className="mb-4 text-2xl font-bold">
                                        Configure Seats for "{event.name}"
                                    </h2>
                                    <p className="mb-4 text-gray-600">
                                        Venue: {venue.name} | Event ID:{' '}
                                        {event.event_id}
                                    </p>
                                </div>
                                <button
                                    className="ml-auto h-fit w-fit rounded bg-blue-500 px-4 py-2 font-bold text-white hover:bg-blue-700"
                                    onClick={() => window.history.back()}
                                >
                                    Back to Dashboard
                                </button>
                            </div>

                            {activeTimeline ? (
                                <div className="mb-4">
                                    <h3 className="text-lg font-semibold">
                                        Current Timeline Period
                                    </h3>
                                    <div className="mt-2 rounded-lg bg-blue-50 p-3">
                                        <div className="font-medium">
                                            {activeTimeline.name}
                                        </div>
                                        <div className="text-sm text-gray-600">
                                            {new Date(
                                                activeTimeline.start_date,
                                            ).toLocaleDateString()}{' '}
                                            -{' '}
                                            {new Date(
                                                activeTimeline.end_date,
                                            ).toLocaleDateString()}
                                        </div>
                                        <div className="mt-2 text-xs text-blue-500">
                                            Prices are managed in the ticket
                                            category settings for this timeline.
                                        </div>
                                    </div>
                                </div>
                            ) : (
                                <div className="mb-4 rounded-lg bg-yellow-50 p-3">
                                    <div className="font-medium text-yellow-700">
                                        No active timeline found
                                    </div>
                                    <div className="text-sm text-yellow-600">
                                        Please set up a timeline for this event
                                        to configure pricing.
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
                                                style={getCategoryStyle(
                                                    category,
                                                )}
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

                            <div className="overflow-x-auto">
                                <div className="min-w-max">
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
            </div>

            {/* Render the Toaster component */}
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
