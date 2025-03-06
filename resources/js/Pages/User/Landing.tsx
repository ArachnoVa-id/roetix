import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import ProceedTransactionButton from '@/Pages/Seat/components/ProceedTransactionButton';
import SeatMapDisplay from '@/Pages/Seat/SeatMapDisplay';
import { Layout, SeatItem } from '@/Pages/Seat/types';
import { Head } from '@inertiajs/react';
import { useState } from 'react';

interface Event {
    id: string;
    name: string;
    date: string;
    venue: {
        id: string;
        name: string;
    };
}

interface Props {
    client?: string;
    layout: Layout;
    event: Event;
    ticketTypes: string[];
}

const formatRupiah = (value: number): string =>
    new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
    }).format(value);

export default function Landing({
    client,
    layout,
    event,
    ticketTypes = ['standard', 'VIP', 'Regular'],
}: Props) {
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

    // Calculate subtotal, tax and total
    const subtotal = selectedSeats.reduce((acc, seat) => {
        // Explicit check and conversion with type assertions
        let price = 0;
        if (seat.price !== undefined && seat.price !== null) {
            price =
                typeof seat.price === 'string'
                    ? parseFloat(
                          (seat.price as string)
                              .replace(/[^\d.,-]/g, '')
                              .replace(',', '.'),
                      )
                    : Number(seat.price);
        }
        return acc + price;
    }, 0);
    const taxRate = 1; // 1% tax
    const taxAmount = (subtotal * taxRate) / 100;
    const total = subtotal + taxAmount;

    // Status legend
    const statusLegends = [
        { label: 'Available', color: 'bg-white border-2 border-gray-300' },
        { label: 'Booked', color: 'bg-red-500' },
        { label: 'In Transaction', color: 'bg-yellow-500' },
        { label: 'Not Available', color: 'bg-gray-400' },
    ];

    return (
        <AuthenticatedLayout>
            <Head title="Book Tickets" />
            <div className="py-8">
                <div className="mx-auto max-w-7xl sm:px-6 lg:px-8">
                    <div className="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                        <div className="p-6 text-gray-900">
                            {event ? (
                                <>
                                    <h1 className="text-2xl font-bold">
                                        {event.name}
                                    </h1>
                                    <p className="text-lg text-gray-600">
                                        Venue: {event.venue.name}
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

                        <div className="overflow-x-auto">
                            <div className="w-full">
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
                                                    {formatRupiah(seat.price)}
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
                                            Tax ({taxRate}%):
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
