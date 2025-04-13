import Toaster from '@/Components/novatix/Toaster';
import useToaster from '@/hooks/useToaster';
import { EditorProps } from '@/types/editor';
import { Layout, SeatItem, Timeline, UpdatedSeats } from '@/types/seatmap';
import { Head } from '@inertiajs/react';
import React, { useEffect, useState } from 'react';
import SeatMapEditor from './SeatMapEditor';

const Edit: React.FC<EditorProps> = ({
    layout,
    event,
    ticketTypes,
    // categoryColors = {},
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
    // const getCategoryStyle = (category: string) => {
    //     return {
    //         backgroundColor: categoryColors[category]
    //             ? categoryColors[category] + '33'
    //             : undefined,
    //         borderColor: categoryColors[category],
    //     };
    // };

    // Process timeline data directly from props rather than from API
    useEffect(() => {
        // Get current date with Jakarta timezone
        // const formatJakartaTime = (date = new Date()) => {
        //     return new Date(date).toLocaleString('en-US', {
        //         timeZone: 'Asia/Jakarta',
        //         hour12: false,
        //     });
        // };

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
            // const timelineIds = Array.from(
            //     new Set(categoryPrices.map((price) => price.timeline_id)),
            // );

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
                // console.log(
                //     `[${formatJakartaTime()}] Active timeline: ${active.name} (${active.start_date} to ${active.end_date})`,
                // );
            } else {
                // If no active timeline found, keep using the current one from props
                // console.log(
                //     `[${formatJakartaTime()}] No active timeline found based on current date, using provided default`,
                // );
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
            // const formatJakartaTime = (date = new Date()) => {
            //     return new Date(date).toLocaleString('en-US', {
            //         timeZone: 'Asia/Jakarta',
            //         hour12: false,
            //     });
            // };

            // console.log(
            //     `[${formatJakartaTime()}] Updating price mappings for timeline: ${activeTimeline.name}`,
            // );

            const priceMap: Record<string, number> = {};
            const colorMap: Record<string, string> = {};

            // Create a mapping of category IDs to category names and colors
            const categoryIdToNameMap: Record<string, string> = {};
            ticketCategories.forEach((category) => {
                categoryIdToNameMap[category.id] = category.name;
                colorMap[category.name] = category.color;
            });

            // Find prices for the active timeline
            const currentTimelinePrices = categoryPrices.filter(
                (price) => price.timeline_id === activeTimeline.id,
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
        }
    }, [activeTimeline, ticketCategories, categoryPrices]);

    const handleSave = (updatedSeats: UpdatedSeats[]) => {
        // Optimistically update the UI immediately
        const updatedLayout = { ...currentLayout };
        updatedSeats.forEach((update) => {
            const seatToUpdate = updatedLayout.items.find(
                (item) =>
                    // item.type === 'seat' &&
                    (item as SeatItem).id === update.id,
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
            event_id: event.id,
            seats: updatedSeats,
        };

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
            <Head title="Event Editor - NovaTix EO" />
            <div className="flex h-screen overflow-hidden">
                {' '}
                {/* Changed to h-screen and removed padding/overflow */}
                <div className="w-full">
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
