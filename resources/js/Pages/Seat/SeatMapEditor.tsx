import React, { useState } from 'react';
import { Category, Layout, LayoutItem, SeatItem } from './types';

interface Props {
    layout: Layout;
    onSave: (updatedSeats: any) => void;
}

type SelectionMode = 'SINGLE' | 'MULTIPLE' | 'CATEGORY';

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

const SeatMapEditor: React.FC<Props> = ({ layout, onSave }) => {
    const [selectionMode, setSelectionMode] = useState<SelectionMode>('SINGLE');
    const [selectedSeats, setSelectedSeats] = useState<Set<string>>(new Set());
    const [selectedCategory, setSelectedCategory] = useState<Category | null>(
        null,
    );

    // Map untuk menyimpan nomor terakhir untuk setiap baris
    const lastNumberByRow = new Map<string, number>();

    // Fungsi untuk mendapatkan nomor kursi berikutnya untuk suatu baris
    const getNextNumber = (row: string): number => {
        const lastNum = lastNumberByRow.get(row) || 0;
        const nextNum = lastNum + 1;
        lastNumberByRow.set(row, nextNum);
        return nextNum;
    };

    const grid = Array.from({ length: layout.totalRows }, () =>
        Array(layout.totalColumns).fill(null),
    );

    // Isi grid dengan kursi
    layout.items.forEach((item) => {
        if ('seat_id' in item) {
            const rowIndex =
                typeof item.row === 'string'
                    ? item.row.charCodeAt(0) - 65
                    : item.row;

            if (rowIndex >= 0 && rowIndex < layout.totalRows) {
                const rowLetter = String.fromCharCode(65 + rowIndex);
                // Gunakan column dari item untuk penempatan
                const colIndex = (item.column as number) - 1;
                // Gunakan column sebagai nomor kursi
                const updatedItem = {
                    ...item,
                    seat_id: `${rowLetter}${item.column}`,
                };

                grid[rowIndex][colIndex] = updatedItem;
            }
        }
    });

    // Fungsi untuk mengecek apakah kursi dapat diedit
    const isSeatEditable = (seat: SeatItem): boolean => {
        return seat.status !== 'booked';
    };

    const getSeatColor = (seat: SeatItem): string => {
        const isSelected = selectedSeats.has(seat.seat_id);
        let baseColor = '';

        if (seat.status !== 'available') {
            switch (seat.status) {
                case 'booked':
                    baseColor = 'bg-red-500';
                    break;
                case 'in_transaction':
                    baseColor = 'bg-yellow-500';
                    break;
                case 'not_available':
                    baseColor = 'bg-gray-400';
                    break;
            }
        } else {
            switch (seat.category) {
                case 'diamond':
                    baseColor = 'bg-cyan-400';
                    break;
                case 'gold':
                    baseColor = 'bg-yellow-400';
                    break;
                case 'silver':
                    baseColor = 'bg-gray-300';
                    break;
                default:
                    baseColor = 'bg-gray-200';
            }
        }

        return `${baseColor} ${isSelected ? 'ring-2 ring-blue-500' : ''}`;
    };

    const handleSeatClick = (seat: SeatItem) => {
        // Jika kursi booked, tidak lakukan apa-apa
        if (!isSeatEditable(seat)) return;

        setSelectedSeats((prev) => {
            const next = new Set(prev);

            switch (selectionMode) {
                case 'SINGLE':
                    next.clear();
                    next.add(`${seat.row}${seat.column}`);
                    break;

                case 'MULTIPLE':
                    const seatId = `${seat.row}${seat.column}`;
                    if (next.has(seatId)) {
                        next.delete(seatId);
                    } else {
                        next.add(seatId);
                    }
                    break;

                case 'CATEGORY':
                    next.clear();
                    layout.items.forEach((item) => {
                        if (
                            item.type === 'seat' &&
                            item.category === seat.category &&
                            isSeatEditable(item as SeatItem)
                        ) {
                            // Gunakan isSeatEditable
                            const rowLetter = (item as SeatItem).row;
                            const column = (item as SeatItem).column;
                            next.add(`${rowLetter}${column}`);
                        }
                    });
                    setSelectedCategory(seat.category);
                    break;
            }

            return next;
        });
    };

    const handleSelectCategory = (category: Category) => {
        if (selectionMode !== 'CATEGORY') return;

        // Kumpulkan semua kursi yang dapat diedit dengan kategori yang dipilih
        const seatsInCategory = layout.items
            .filter(
                (item) =>
                    item.type === 'seat' &&
                    item.category === category &&
                    isSeatEditable(item as SeatItem), // Gunakan isSeatEditable
            )
            .map((item) => {
                const rowLetter = (item as SeatItem).row;
                const column = (item as SeatItem).column;
                return `${rowLetter}${column}`;
            });

        setSelectedSeats(new Set(seatsInCategory));
        setSelectedCategory(category);
    };

    const renderCell = (item: LayoutItem | null, colIndex: number) => {
        if (item && item.type === 'seat') {
            const seat = item as SeatItem;
            const isEditable = isSeatEditable(seat);

            return (
                <div
                    key={colIndex}
                    onClick={() => isEditable && handleSeatClick(seat)}
                    className={`flex h-8 w-8 items-center justify-center rounded border ${getSeatColor(seat)} ${isEditable ? 'cursor-pointer hover:opacity-80' : 'cursor-not-allowed'} ${seat.status === 'booked' ? 'opacity-75' : ''} text-xs`}
                    title={
                        !isEditable
                            ? 'Kursi telah dibooking dan tidak dapat diedit'
                            : ''
                    }
                >
                    {seat.seat_id}
                </div>
            );
        }
        return <div key={colIndex} className="h-8 w-8"></div>;
    };

    const handleStatusUpdate = (status: string) => {
        const updatedSeats = layout.items
            .filter(
                (item) =>
                    item.type === 'seat' &&
                    selectedSeats.has(
                        `${(item as SeatItem).row}${(item as SeatItem).column}`,
                    ) &&
                    isSeatEditable(item as SeatItem), // Gunakan isSeatEditable untuk konsistensi
            )
            .map((item) => ({
                seat_id: (item as SeatItem).seat_id,
                status: status,
            }));

        if (updatedSeats.length > 0) {
            onSave(updatedSeats);
            setSelectedSeats(new Set());
        }
    };

    const handleModeChange = (mode: SelectionMode) => {
        setSelectionMode(mode);
        setSelectedSeats(new Set());
        setSelectedCategory(null);
    };

    // Rest of the component remains the same...
    return (
        <div className="p-6">
            {/* Mode Selection */}
            <div className="flex gap-4 rounded-lg bg-gray-100 p-4">
                <button
                    className={`rounded px-4 py-2 ${selectionMode === 'SINGLE' ? 'bg-blue-500 text-white' : 'bg-white'}`}
                    onClick={() => handleModeChange('SINGLE')}
                >
                    Single Edit
                </button>
                <button
                    className={`rounded px-4 py-2 ${selectionMode === 'MULTIPLE' ? 'bg-blue-500 text-white' : 'bg-white'}`}
                    onClick={() => handleModeChange('MULTIPLE')}
                >
                    Multiple Edit
                </button>
                <button
                    className={`rounded px-4 py-2 ${selectionMode === 'CATEGORY' ? 'bg-blue-500 text-white' : 'bg-white'}`}
                    onClick={() => handleModeChange('CATEGORY')}
                >
                    Category Edit
                </button>
            </div>

            {/* Category Selection */}
            {selectionMode === 'CATEGORY' && (
                <div className="flex gap-4 rounded-lg bg-gray-50 p-4">
                    {['diamond', 'gold', 'silver'].map((category) => (
                        <button
                            key={category}
                            className={`rounded px-4 py-2 ${
                                selectedCategory === category
                                    ? 'ring-2 ring-blue-500'
                                    : ''
                            } ${
                                category === 'diamond'
                                    ? 'bg-cyan-400'
                                    : category === 'gold'
                                      ? 'bg-yellow-400'
                                      : 'bg-gray-300'
                            } text-white`}
                            onClick={() =>
                                handleSelectCategory(category as Category)
                            }
                        >
                            {category.charAt(0).toUpperCase() +
                                category.slice(1)}
                        </button>
                    ))}
                </div>
            )}

            {/* Status Buttons */}
            <div className="flex gap-4 rounded-lg bg-gray-50 p-4">
                <button
                    className="rounded bg-green-400 px-4 py-2 text-white hover:bg-green-500 disabled:opacity-50"
                    onClick={() => handleStatusUpdate('available')}
                    disabled={selectedSeats.size === 0}
                >
                    Set Available
                </button>
                <button
                    className="rounded bg-yellow-400 px-4 py-2 text-white hover:bg-yellow-500 disabled:opacity-50"
                    onClick={() => handleStatusUpdate('in_transaction')}
                    disabled={selectedSeats.size === 0}
                >
                    Set In Transaction
                </button>
                <button
                    className="rounded bg-gray-400 px-4 py-2 text-white hover:bg-gray-500 disabled:opacity-50"
                    onClick={() => handleStatusUpdate('not_available')}
                    disabled={selectedSeats.size === 0}
                >
                    Set Not Available
                </button>
            </div>

            {/* Legends Section */}
            <div className="mb-8">
                <div className="grid grid-cols-2 gap-8">
                    <div className="flex flex-col items-center">
                        <h4 className="mb-2 text-lg font-semibold">Category</h4>
                        <div className="flex space-x-4">
                            {categoryLegends.map((legend, i) => (
                                <div
                                    key={i}
                                    className="flex flex-col items-center"
                                >
                                    <div
                                        className={`h-8 w-8 ${legend.color} rounded-full shadow-md`}
                                    ></div>
                                    <span className="mt-1 text-sm">
                                        {legend.label}
                                    </span>
                                </div>
                            ))}
                        </div>
                    </div>
                    <div className="flex flex-col items-center">
                        <h4 className="mb-2 text-lg font-semibold">Status</h4>
                        <div className="flex space-x-4">
                            {statusLegends.map((legend, i) => (
                                <div
                                    key={i}
                                    className="flex flex-col items-center"
                                >
                                    <div
                                        className={`h-8 w-8 ${legend.color} rounded-full shadow-md`}
                                    ></div>
                                    <span className="mt-1 text-sm">
                                        {legend.label}
                                    </span>
                                </div>
                            ))}
                        </div>
                    </div>
                </div>
            </div>

            {/* Grid */}
            <div className="flex w-full flex-col items-center">
                <div className="grid gap-1">
                    {[...grid].reverse().map((row, reversedIndex) => {
                        // Hitung kembali indeks asli untuk label baris
                        const originalIndex = grid.length - 1 - reversedIndex;
                        return (
                            <div
                                key={reversedIndex}
                                className="flex items-center gap-1"
                            >
                                <div className="flex gap-1">
                                    {row.map((item, colIndex) =>
                                        renderCell(item, colIndex),
                                    )}
                                </div>
                            </div>
                        );
                    })}
                </div>

                {/* Stage */}
                <div className="mt-4 flex h-8 w-60 items-center justify-center rounded border border-gray-200 bg-white text-sm">
                    Panggung
                </div>
            </div>
        </div>
    );
};

export default SeatMapEditor;
