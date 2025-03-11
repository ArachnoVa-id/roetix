import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import ProceedTransactionButton from '@/Pages/Seat/components/ProceedTransactionButton';
import SeatMapDisplay from '@/Pages/Seat/SeatMapDisplay';
import { Layout, SeatItem } from '@/Pages/Seat/types';
import { Head } from '@inertiajs/react';
import { useMemo, useState } from 'react';

interface Venue {
    venue_id: string;
    name: string;
}

interface Event {
    event_id: string;
    name: string;
    date: string;
    venue_id: string;
}

interface Props {
    client: string;
    layout: Layout;
    event: Event;
    venue: Venue;
    ticketTypes: string[];
    error?: string;
}

export default function Landing({
    client,
    layout,
    event,
    venue,
    ticketTypes = ['standard', 'VIP', 'Regular'],
    error,
}: Props) {
    // console.log('Landing component rendered with props:', {
    //     client,
    //     layout,
    //     event,
    //     venue,
    //     ticketTypes,
    //     error,
    // });

    const [selectedSeats, setSelectedSeats] = useState<SeatItem[]>([]);

    // Create a map of ticket types to colors for display
    const ticketTypeColors: Record<string, string> = {};
    ticketTypes.forEach((type, index) => {
        const colorClasses = [
            'bg-cyan-400', // For VIP or first type
            'bg-yellow-400', // For standard or second type
            'bg-green-400', // For third type
            'bg-purple-400', // For fourth type
            'bg-gray-300', // For any additional type
        ];

        ticketTypeColors[type] = colorClasses[index] || 'bg-gray-300';
    });

    const handleSeatClick = (seat: SeatItem) => {
        if (seat.status !== 'available') {
            return; // Only allow selecting available seats
        }

        const exists = selectedSeats.find((s) => s.seat_id === seat.seat_id);
        if (exists) {
            setSelectedSeats(
                selectedSeats.filter((s) => s.seat_id !== seat.seat_id),
            );
        } else {
            if (selectedSeats.length < 5) {
                setSelectedSeats([...selectedSeats, seat]);
            }
        }
    };

    // Fixed price conversion function that preserves decimal places correctly
    const getSafePrice = (price: string | number | undefined): number => {
        if (price === undefined || price === null) return 0;

        // For debugging
        // console.log('Original price value:', price, 'Type:', typeof price);

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

            // Log the cleaned string and the resulting number
            // console.log(
            //     'Cleaned string:',
            //     cleaned,
            //     'Parsed number:',
            //     numericPrice,
            // );

            return isNaN(numericPrice) ? 0 : numericPrice;
        }

        return 0;
    };

    // Format currency with proper Indonesian formatting without multiplying the value
    const formatRupiah = (value: number): string => {
        if (isNaN(value) || value === null || value === undefined) {
            // console.error('Attempting to format invalid value:', value);
            return 'Rp 0,00';
        }

        // Log the value we're about to format for debugging
        // console.log('Formatting value:', value);

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
        // Calculate subtotal with detailed logging
        const subtotal = selectedSeats.reduce((acc, seat) => {
            const seatPrice = getSafePrice(seat.price);
            // console.log(
            //     `Seat ${seat.seat_number} price: ${seat.price} → ${seatPrice}`,
            // );
            return acc + seatPrice;
        }, 0);

        // console.log('Calculated subtotal:', subtotal);

        // Calculate tax
        const taxRate = 1; // 1%
        const taxAmount = (subtotal * taxRate) / 100;

        // console.log('Calculated tax amount:', taxAmount);

        // Calculate total
        const total = subtotal + taxAmount;

        // console.log('Calculated total:', total);

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
            <AuthenticatedLayout client={client}>
                <Head title="Error" />
                <div className="py-8">
                    <div className="mx-auto max-w-7xl sm:px-6 lg:px-8">
                        <div className="overflow-hidden bg-white shadow-sm sm:rounded-lg">
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
        <AuthenticatedLayout client={client}>
            <Head title="Book Tickets" />
            <div className="py-8">
                <div className="mx-auto max-w-7xl sm:px-6 lg:px-8">
                    <div className="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                        <div className="p-6 text-gray-900">
                            {event && venue ? (
                                <>
                                    <h1 className="text-2xl font-bold">
                                        {event.name}
                                    </h1>
                                    <p className="text-lg text-gray-600">
                                        Venue: {venue.name}
                                    </p>
                                    <p className="text-gray-600">
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

            <div className="py-6">
                <div className="mx-auto w-full sm:px-6 lg:px-8">
                    <div className="overflow-hidden bg-white p-6 shadow-xl sm:rounded-lg">
                        {/* Legend Section */}
                        <div className="mb-8">
                            <h3 className="mb-4 text-center text-2xl font-bold">
                                Seat Map
                            </h3>
                            <div className="grid grid-cols-1 gap-8 md:grid-cols-2">
                                {/* Ticket Type Legend */}
                                <div className="rounded-lg bg-gray-50 p-4 shadow">
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
                                                    className={`h-8 w-8 ${ticketTypeColors[type]} rounded-full shadow-lg`}
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
                                <div className="rounded-lg bg-gray-50 p-4 shadow">
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

                        <div className="flex justify-center overflow-x-auto">
                            <div className="flex items-center justify-center">
                                <SeatMapDisplay
                                    config={layout}
                                    onSeatClick={handleSeatClick}
                                    selectedSeats={selectedSeats}
                                    ticketTypeColors={ticketTypeColors}
                                />
                            </div>
                        </div>

                        {/* Selected Seats Section */}
                        <div className="mt-8 rounded border p-4">
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
                                            className="flex items-center justify-between rounded-lg bg-gray-50 p-3"
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
                                <div className="mt-6 space-y-2 rounded-lg bg-gray-50 p-4">
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
                            {selectedSeats.length > 0 && (
                                <ProceedTransactionButton
                                    selectedSeats={selectedSeats}
                                />
                            )}
                        </div>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
