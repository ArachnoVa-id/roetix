import Toaster from '@/Components/novatix/Toaster'; // Import the Toaster component
import useToaster from '@/hooks/useToaster';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import ProceedTransactionButton from '@/Pages/Seat/components/ProceedTransactionButton';
import SeatMapDisplay from '@/Pages/Seat/SeatMapDisplay';
import { Layout, SeatItem } from '@/Pages/Seat/types';
import { EventProps } from '@/types/front-end';
import { Head } from '@inertiajs/react';
import { useEffect, useMemo, useState } from 'react';
interface Venue {
    venue_id: string;
    name: string;
}

interface Event {
    event_id: string;
    name: string;
    date: string;
    venue_id: string;
    status: string; // Tambahkan ini
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
    timebound_price_id: string;
    ticket_category_id: string;
    timeline_id: string;
    price: number;
}

interface Props {
    client: string;
    layout: Layout;
    event: Event;
    venue: Venue;
    ticketCategories: TicketCategory[];
    currentTimeline?: Timeline;
    categoryPrices?: CategoryPrice[];
    error?: string;
    props: EventProps;
}

export default function Landing({
    client,
    layout,
    event,
    venue,
    ticketCategories = [],
    currentTimeline,
    categoryPrices = [],
    error,
    props,
}: Props) {
    const [selectedSeats, setSelectedSeats] = useState<SeatItem[]>([]);
    const { toasterState, showSuccess, showError, hideToaster } = useToaster();

    // Show error if it exists when component mounts
    useEffect(() => {
        if (error) {
            showError(error);
        }
    }, [error, showError]);

    // Tentukan apakah booking diperbolehkan berdasarkan status event
    const isBookingAllowed = useMemo(() => {
        return event && event.status === 'active';
    }, [event]);

    // Tambahkan pesan status yang akan ditampilkan jika booking tidak diizinkan
    const eventStatusMessage = useMemo(() => {
        if (!event) return '';

        switch (event.status) {
            case 'planned':
                return 'This event is not yet ready for booking';
            case 'completed':
                return 'This event does not accept booking anymore';
            case 'cancelled':
                return 'This event has been cancelled';
            default:
                return '';
        }
    }, [event]);

    // Extract ticket types from categories
    const ticketTypes = useMemo(
        () =>
            ticketCategories.length > 0
                ? ticketCategories.map((cat) => cat.name)
                : ['standard', 'VIP', 'Regular'],
        [ticketCategories],
    );

    // Create a map of ticket types to colors for display
    const ticketTypeColors: Record<string, string> = useMemo(() => {
        const colors: Record<string, string> = {};

        // Jika ada kategori tiket dengan warna, gunakan itu
        if (ticketCategories.length > 0) {
            ticketCategories.forEach((category) => {
                // Gunakan nilai hex langsung dari database
                colors[category.name] = category.color;
            });
        } else {
            // Fallback colors dalam hex
            colors['VIP'] = '#FFD54F'; // Kuning
            colors['standard'] = '#90CAF9'; // Biru
            colors['Regular'] = '#A5D6A7'; // Hijau
        }

        return colors;
    }, [ticketCategories]);

    // Function to get price for a seat based on its category and current timeline
    const getSeatPrice = (seat: SeatItem): number => {
        if (!seat.ticket_type || !currentTimeline) {
            return typeof seat.price === 'number'
                ? seat.price
                : parseFloat(seat.price as string) || 0;
        }

        // Find the ticket category ID for this seat's ticket type
        const category = ticketCategories.find(
            (cat) => cat.name === seat.ticket_type,
        );
        if (!category) {
            return typeof seat.price === 'number'
                ? seat.price
                : parseFloat(seat.price as string) || 0;
        }

        // Find the price for this category in the current timeline
        const priceEntry = categoryPrices.find(
            (p) =>
                p.ticket_category_id === category.ticket_category_id &&
                p.timeline_id === currentTimeline.timeline_id,
        );

        if (priceEntry) {
            return priceEntry.price;
        }

        return typeof seat.price === 'number'
            ? seat.price
            : parseFloat(seat.price as string) || 0;
    };

    const handleSeatClick = (seat: SeatItem) => {
        if (!isBookingAllowed) {
            showError('Booking is not allowed at this time');
            return;
        }

        if (seat.status !== 'available') {
            showError('This seat is not available');
            return;
        }

        const exists = selectedSeats.find((s) => s.seat_id === seat.seat_id);
        if (exists) {
            setSelectedSeats(
                selectedSeats.filter((s) => s.seat_id !== seat.seat_id),
            );
            showSuccess(`Seat ${seat.seat_number} removed from selection`);
        } else {
            if (selectedSeats.length < 5) {
                // Calculate correct price based on category and timeline
                const updatedSeat = {
                    ...seat,
                    price: getSeatPrice(seat),
                };
                setSelectedSeats([...selectedSeats, updatedSeat]);
                showSuccess(`Seat ${seat.seat_number} added to selection`);
            } else {
                showError('You can only select up to 5 seats');
            }
        }
    };

    // Fixed price conversion function that preserves decimal places correctly
    const getSafePrice = (price: string | number | undefined): number => {
        if (price === undefined || price === null) return 0;

        // If it's already a number, return it directly
        if (typeof price === 'number') {
            return price;
        }

        if (typeof price === 'string') {
            // Remove currency symbol, spaces, and non-numeric characters except decimals and commas
            let cleaned = price.replace(/[^0-9,.]/g, '');

            // Handle Indonesian number format: convert "200.000,00" to "200000.00"
            if (cleaned.includes(',') && cleaned.includes('.')) {
                // This is likely Indonesian format with period as thousand separator
                // First, remove all periods (thousand separators)
                cleaned = cleaned.replace(/\./g, '');
                // Then replace comma with period for decimal
                cleaned = cleaned.replace(',', '.');
            } else if (cleaned.includes(',')) {
                // Just has a comma - replace with period for standard JS parsing
                cleaned = cleaned.replace(',', '.');
            }

            const numericPrice = parseFloat(cleaned);
            return isNaN(numericPrice) ? 0 : numericPrice;
        }

        return 0;
    };

    // Format currency with proper Indonesian formatting without multiplying the value
    const formatRupiah = (value: number): string => {
        if (isNaN(value) || value === null || value === undefined) {
            return 'Rp 0,00';
        }

        // Format with Indonesian locale (uses period for thousands, comma for decimal)
        return new Intl.NumberFormat('id-ID', {
            style: 'currency',
            currency: 'IDR',
            minimumFractionDigits: 2,
            maximumFractionDigits: 2,
        }).format(value);
    };

    // Calculate prices using useMemo to avoid recalculating on every render
    const { subtotal, taxAmount, total } = useMemo(() => {
        // Calculate subtotal
        const subtotal = selectedSeats.reduce((acc, seat) => {
            const seatPrice = getSafePrice(seat.price);
            return acc + seatPrice;
        }, 0);

        // Calculate tax
        const taxRate = 1; // 1%
        const taxAmount = (subtotal * taxRate) / 100;

        // Calculate total
        const total = subtotal + taxAmount;

        return { subtotal, taxAmount, total };
    }, [selectedSeats]);

    // Status legend
    const statusLegends = [
        { label: 'Booked', color: 'bg-red-500' },
        { label: 'In Transaction', color: 'bg-yellow-500' },
        { label: 'Reserved', color: 'bg-gray-400' },
    ];

    // If we have an error, show it
    if (error) {
        return (
            <AuthenticatedLayout client={client} props={props}>
                <Head title="Error" />
                <div className="py-8">
                    <div className="mx-auto max-w-7xl sm:px-6 lg:px-8">
                        <div
                            className="overflow-hidden shadow-sm sm:rounded-lg"
                            style={{
                                backgroundColor: props.primary_color,
                            }}
                        >
                            <div className="p-6 text-gray-900">
                                <h1 className="text-2xl font-bold text-red-600">
                                    Error
                                </h1>
                                <p className="mt-4">{error}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </AuthenticatedLayout>
        );
    }

    return (
        <AuthenticatedLayout client={client} props={props}>
            <Head title="Book Tickets" />
            {/* Tampilkan pesan status event jika tidak active */}
            {!isBookingAllowed && event && (
                <div className="py-2">
                    <div className="mx-auto w-full sm:px-6 lg:px-8">
                        <div className="overflow-hidden bg-yellow-100 p-4 shadow-md sm:rounded-lg">
                            <p className="text-center font-medium text-yellow-800">
                                {eventStatusMessage}
                            </p>
                        </div>
                    </div>
                </div>
            )}
            <div className="py-8">
                <div className="mx-auto max-w-7xl sm:px-6 lg:px-8">
                    <div
                        className="overflow-hidden shadow-sm sm:rounded-lg"
                        style={{
                            backgroundColor: props.primary_color,
                        }}
                    >
                        <div
                            className="p-6"
                            style={{
                                color: props.text_primary_color,
                            }}
                        >
                            {event && venue ? (
                                <>
                                    <h1 className="text-2xl font-bold">
                                        {event.name}
                                    </h1>
                                    <p
                                        className="text-lg"
                                        style={{
                                            color: props.text_secondary_color,
                                        }}
                                    >
                                        Venue: {venue.name}
                                    </p>
                                    <p
                                        className=""
                                        style={{
                                            color: props.text_secondary_color,
                                        }}
                                    >
                                        Date:{' '}
                                        {new Date(
                                            event.date,
                                        ).toLocaleDateString()}
                                    </p>
                                </>
                            ) : (
                                <h1 className="text-2xl font-bold">
                                    {(client ? client + ' : ' : '') +
                                        'Buy Tickets Here'}
                                </h1>
                            )}
                        </div>
                    </div>
                </div>
            </div>

            {/* Timeline Information */}
            {currentTimeline && (
                <div className="py-2">
                    <div className="mx-auto w-full sm:px-6 lg:px-8">
                        <div
                            className="overflow-hidden p-4 shadow-md sm:rounded-lg"
                            style={{
                                backgroundColor: props.primary_color,
                                color: props.text_primary_color,
                            }}
                        >
                            <h3 className="mb-3 text-xl font-semibold">
                                Current Ticket Period
                            </h3>
                            <div className="rounded-lg bg-blue-50 p-4 text-blue-800">
                                <div className="text-lg font-medium">
                                    {currentTimeline.name}
                                </div>
                                <div className="text-blue-600">
                                    {new Date(
                                        currentTimeline.start_date,
                                    ).toLocaleDateString()}{' '}
                                    -{' '}
                                    {new Date(
                                        currentTimeline.end_date,
                                    ).toLocaleDateString()}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            )}

            <div className="py-6">
                <div className="mx-auto w-full sm:px-6 lg:px-8">
                    <div
                        className="overflow-hidden p-6 shadow-xl sm:rounded-lg"
                        style={{
                            backgroundColor: props.primary_color,
                            color: props.text_primary_color,
                        }}
                    >
                        {/* Legend Section */}
                        <div className="mb-8">
                            <h3 className="mb-4 text-center text-2xl font-bold">
                                Seat Map
                            </h3>
                            <div className="grid grid-cols-1 gap-8 md:grid-cols-2">
                                {/* Ticket Type Legend */}
                                <div
                                    className="rounded-lg p-4 shadow"
                                    style={{
                                        backgroundColor: props.secondary_color,
                                        color: props.text_secondary_color,
                                    }}
                                >
                                    <h4 className="mb-2 text-center text-lg font-semibold">
                                        Ticket Types
                                    </h4>
                                    <div className="flex flex-wrap items-center justify-center gap-4">
                                        {ticketTypes.map((type) => (
                                            <div
                                                key={type}
                                                className="flex flex-col items-center"
                                            >
                                                <div
                                                    className="h-8 w-8 rounded-full shadow-lg"
                                                    style={{
                                                        backgroundColor:
                                                            ticketTypeColors[
                                                                type
                                                            ],
                                                    }}
                                                ></div>
                                                <span className="mt-2 text-sm font-medium">
                                                    {type
                                                        .charAt(0)
                                                        .toUpperCase() +
                                                        type.slice(1)}
                                                </span>
                                            </div>
                                        ))}
                                    </div>
                                </div>

                                {/* Status Legend */}
                                <div
                                    className="rounded-lg p-4 shadow"
                                    style={{
                                        backgroundColor: props.secondary_color,
                                        color: props.text_secondary_color,
                                    }}
                                >
                                    <h4 className="mb-2 text-center text-lg font-semibold">
                                        Status
                                    </h4>
                                    <div className="flex flex-wrap items-center justify-center gap-4">
                                        {statusLegends.map((legend, i) => (
                                            <div
                                                key={i}
                                                className="flex flex-col items-center"
                                            >
                                                <div
                                                    className={`h-8 w-8 ${legend.color} rounded-full shadow-lg`}
                                                ></div>
                                                <span className="mt-2 text-sm font-medium">
                                                    {legend.label}
                                                </span>
                                            </div>
                                        ))}
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div className="flex justify-center">
                            <div
                                className="overflow-x-auto"
                                style={{ maxWidth: '100%' }}
                            >
                                <div className="flex min-w-max justify-center">
                                    <SeatMapDisplay
                                        config={layout}
                                        props={props}
                                        onSeatClick={handleSeatClick}
                                        selectedSeats={selectedSeats}
                                        ticketTypeColors={ticketTypeColors}
                                        currentTimeline={currentTimeline}
                                        eventStatus={event?.status}
                                    />
                                </div>
                            </div>
                        </div>

                        {/* Selected Seats Section */}
                        <div
                            className="mt-8 rounded border p-4"
                            style={{
                                backgroundColor: props.secondary_color,
                                color: props.text_secondary_color,
                            }}
                        >
                            <h3 className="mb-4 text-xl font-semibold">
                                Selected Seats
                            </h3>
                            {selectedSeats.length === 0 ? (
                                <p>No seats selected.</p>
                            ) : (
                                <div className="space-y-4">
                                    {selectedSeats.map((seat) => (
                                        <div
                                            key={seat.seat_id}
                                            className="flex items-center justify-between rounded-lg p-3"
                                            style={{
                                                backgroundColor:
                                                    props.primary_color,
                                                color: props.text_primary_color,
                                            }}
                                        >
                                            <div>
                                                <p className="font-semibold">
                                                    Ticket Type:{' '}
                                                    {seat.ticket_type ||
                                                        seat.category ||
                                                        'Standard'}
                                                </p>
                                                <p className="text-sm">
                                                    Seat: {seat.seat_number}
                                                </p>
                                            </div>
                                            <div>
                                                <p className="font-semibold">
                                                    Price:{' '}
                                                    {formatRupiah(
                                                        getSafePrice(
                                                            seat.price,
                                                        ),
                                                    )}
                                                </p>
                                            </div>
                                        </div>
                                    ))}
                                </div>
                            )}

                            {/* Subtotal, Tax, and Total */}
                            {selectedSeats.length > 0 && (
                                <div
                                    className="mt-6 space-y-2 rounded-lg p-4"
                                    style={{
                                        backgroundColor: props.primary_color,
                                        color: props.text_primary_color,
                                    }}
                                >
                                    <div className="flex justify-between">
                                        <span className="font-medium">
                                            Subtotal:
                                        </span>
                                        <span>{formatRupiah(subtotal)}</span>
                                    </div>
                                    <div className="flex justify-between">
                                        <span className="font-medium">
                                            Tax (1%):
                                        </span>
                                        <span>{formatRupiah(taxAmount)}</span>
                                    </div>
                                    <div className="flex justify-between text-lg font-semibold">
                                        <span>Total:</span>
                                        <span>{formatRupiah(total)}</span>
                                    </div>
                                </div>
                            )}

                            {/* Proceed Button */}
                            {selectedSeats.length > 0 && isBookingAllowed && (
                                <ProceedTransactionButton
                                    selectedSeats={selectedSeats}
                                    taxAmount={taxAmount}
                                    subtotal={subtotal}
                                    total={total}
                                />
                            )}
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
        </AuthenticatedLayout>
    );
}
