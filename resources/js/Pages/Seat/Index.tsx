import { Head } from '@inertiajs/react';
import React, { useState, useEffect } from 'react';
import SeatMapDisplay from './SeatMapDisplay';
import { Category, Layout, SeatItem } from './types';
import ProceedTransactionButton from './components/ProceedTransactionButton';

interface Props {
    layout: Layout;
}

const categoryPrice: { [key in Category]: number } = {
    diamond: 150000,
    gold: 100000,
    silver: 75000,
};

const tax = 1;

const formatRupiah = (value: number): string =>
    new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
    }).format(value);

const Index: React.FC<Props> = ({ layout }) => {
    const [selectedSeats, setSelectedSeats] = useState<SeatItem[]>([]);;

    const handleSeatClick = (seat: SeatItem) => {
        const exists = selectedSeats.find((s) => s.seat_id === seat.seat_id);
        if (exists) {
            setSelectedSeats(
                selectedSeats.filter((s) => s.seat_id !== seat.seat_id),
            );
        } else {
            if (selectedSeats.length < 5) {
                setSelectedSeats([...selectedSeats, seat]);
                console.log(exists, seat);
            }
        }
    };

    const subtotal = selectedSeats.reduce(
        (acc, seat) => acc + categoryPrice[seat.category],
        0,
    );
    const taxAmount = (subtotal * tax) / 100;
    const total = subtotal + taxAmount;

    const categoryLegends = [
        { label: 'Diamond', color: 'bg-cyan-400' },
        { label: 'Gold', color: 'bg-yellow-400' },
        { label: 'Silver', color: 'bg-gray-300' },
    ];

    const statusLegends = [
        { label: 'Booked', color: 'bg-red-500' },
        { label: 'In Transaction', color: 'bg-yellow-500' },
        { label: 'Not Available', color: 'bg-gray-400' },
    ];

    return (
        <>
            <Head title="Seat Map" />
            <div className="py-12">
                <div className="w-full px-4">
                    <div className="overflow-hidden bg-white p-6 shadow-xl sm:rounded-lg">
                        {/* Legend Section */}
                        <div className="mb-8">
                            <h3 className="mb-4 text-center text-2xl font-bold">
                                SeatMap
                            </h3>
                            <div className="grid grid-cols-1 gap-8 md:grid-cols-2">
                                {/* Category Legend */}
                                <div className="rounded-lg bg-gray-50 p-4 shadow">
                                    <h4 className="mb-2 text-center text-lg font-semibold">
                                        Category
                                    </h4>
                                    <div className="flex items-center justify-center space-x-6">
                                        {categoryLegends.map((legend, i) => (
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
                                {/* Status Legend */}
                                <div className="rounded-lg bg-gray-50 p-4 shadow">
                                    <h4 className="mb-2 text-center text-lg font-semibold">
                                        Status
                                    </h4>
                                    <div className="flex items-center justify-center space-x-6">
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
                            <div className="min-w-max">
                                <SeatMapDisplay
                                    config={layout}
                                    onSeatClick={handleSeatClick}
                                    selectedSeats={selectedSeats}
                                />
                            </div>
                        </div>

                        {/* Section Kursi yang Dipilih */}
                        <div className="mt-8 rounded border p-4">
                            <h3 className="mb-4 text-xl font-semibold">
                                Kursi yang Dipilih
                            </h3>
                            {selectedSeats.length === 0 ? (
                                <p>Tidak ada kursi yang dipilih.</p>
                            ) : (
                                <div className="space-y-4">
                                    {selectedSeats.map((seat) => (
                                        <div
                                            key={seat.seat_id}
                                            className="flex items-center justify-between"
                                        >
                                            <div>
                                                <p className="font-semibold">
                                                    Kategori: {seat.category}
                                                </p>
                                                <p className="text-sm">
                                                    Nomor Kursi: {seat.seat_id}
                                                </p>
                                            </div>
                                            <div>
                                                <p className="font-semibold">
                                                    Harga:{' '}
                                                    {formatRupiah(
                                                        categoryPrice[
                                                            seat.category
                                                        ],
                                                    )}
                                                </p>
                                            </div>
                                        </div>
                                    ))}
                                </div>
                            )}

                            {/* Subtotal, Tax, and Total */}
                            <div className="mt-6 space-y-2">
                                <div className="flex justify-between">
                                    <span className="font-medium">
                                        Subtotal:
                                    </span>
                                    <span>{formatRupiah(subtotal)}</span>
                                </div>
                                <div className="flex justify-between">
                                    <span className="font-medium">
                                        Tax ({tax}%):
                                    </span>
                                    <span>{formatRupiah(taxAmount)}</span>
                                </div>
                                <div className="flex justify-between text-lg font-semibold">
                                    <span>Total:</span>
                                    <span>{formatRupiah(total)}</span>
                                </div>
                            </div>

                            <ProceedTransactionButton selectedSeats={selectedSeats} />
                        </div>
                    </div>
                </div>
            </div>
        </>
    );
};

export default Index;
