import React, { useState } from 'react';
import { Layout, LayoutItem, SeatItem } from './types';

export interface UpdatedSeats {
    seat_id: string;
    status: string;
    ticket_type: string;
    price: number;
}

interface Props {
    layout: Layout;
    onSave: (updatedSeats: UpdatedSeats[]) => void;
    ticketTypes: string[];
}

type SelectionMode = 'SINGLE' | 'MULTIPLE' | 'CATEGORY';

const categoryColors: Record<string, string> = {
    standard: 'bg-gray-300',
    VIP: 'bg-yellow-400',
};

const statusLegends = [
    { label: 'Available', color: 'bg-white border-2 border-gray-300' },
    { label: 'Booked', color: 'bg-red-500' },
    { label: 'In Transaction', color: 'bg-yellow-500' },
    { label: 'Reserved', color: 'bg-gray-400' },
];

const SeatMapEditor: React.FC<Props> = ({ layout, onSave, ticketTypes }) => {
    const [selectionMode, setSelectionMode] = useState<SelectionMode>('SINGLE');
    const [selectedSeats, setSelectedSeats] = useState<Set<string>>(new Set());
    const [selectedCategory, setSelectedCategory] = useState<string | null>(
        null,
    );
    const [selectedStatus, setSelectedStatus] = useState<string>('available');
    const [selectedTicketType, setSelectedTicketType] = useState<string>(
        ticketTypes[0] || 'standard',
    );
    const [ticketPrice, setTicketPrice] = useState<number>(0);

    // Find highest row and column from existing seats
    const findHighestRow = (): number => {
        let maxRowIndex = 0;
        layout.items.forEach((item) => {
            if ('seat_id' in item) {
                const rowIndex =
                    typeof item.row === 'string'
                        ? item.row.charCodeAt(0) - 65
                        : item.row;
                maxRowIndex = Math.max(maxRowIndex, rowIndex);
            }
        });
        return maxRowIndex + 1;
    };

    const findHighestColumn = (): number => {
        let maxColumn = 0;
        layout.items.forEach((item) => {
            if ('seat_id' in item) {
                maxColumn = Math.max(maxColumn, item.column);
            }
        });
        return maxColumn;
    };

    // Create grid with dimensions based on actual data
    const actualRows = Math.max(findHighestRow(), layout.totalRows);
    const actualColumns = Math.max(findHighestColumn(), layout.totalColumns);

    const grid = Array.from({ length: actualRows }, () =>
        Array(actualColumns).fill(null),
    );

    // Fill grid with seats
    layout.items.forEach((item) => {
        if ('seat_id' in item) {
            const rowIndex =
                typeof item.row === 'string'
                    ? item.row.charCodeAt(0) - 65
                    : item.row;

            if (rowIndex >= 0 && rowIndex < actualRows) {
                const colIndex = (item.column as number) - 1;
                if (colIndex >= 0 && colIndex < actualColumns) {
                    grid[rowIndex][colIndex] = item;
                }
            }
        }
    });

    // Function to check if seat can be edited
    const isSeatEditable = (seat: SeatItem): boolean => {
        return seat.status !== 'booked';
    };

    const getSeatColor = (seat: SeatItem): string => {
        // Check if selected
        const isSelected = selectedSeats.has(`${seat.row}${seat.column}`);
        let baseColor = '';

        // If seat is selected, prioritize selection color
        if (isSelected) {
            return 'bg-blue-200 ring-2 ring-blue-500';
        }

        if (seat.status !== 'available') {
            switch (seat.status) {
                case 'booked':
                    baseColor = 'bg-red-500';
                    break;
                case 'in_transaction':
                    baseColor = 'bg-yellow-500';
                    break;
                case 'reserved':
                    baseColor = 'bg-gray-400';
                    break;
            }
        } else {
            // If available, show ticket type color
            const ticketType = seat.ticket_type || 'standard';
            baseColor = categoryColors[ticketType] || 'bg-gray-200';
        }

        return baseColor;
    };

    const handleSeatClick = (seat: SeatItem) => {
        if (!isSeatEditable(seat)) return;

        const seatId = `${seat.row}${seat.column}`;

        setSelectedSeats((prev) => {
            const next = new Set(prev);

            switch (selectionMode) {
                case 'SINGLE':
                    next.clear();
                    next.add(seatId);
                    // Set current values from the seat for editing
                    setSelectedStatus(seat.status);
                    setSelectedTicketType(seat.ticket_type || 'standard');
                    setTicketPrice(seat.price || 0);
                    break;

                case 'MULTIPLE':
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
                            (item as SeatItem).ticket_type ===
                                seat.ticket_type &&
                            isSeatEditable(item as SeatItem)
                        ) {
                            const id = `${(item as SeatItem).row}${(item as SeatItem).column}`;
                            next.add(id);
                        }
                    });
                    setSelectedCategory(seat.ticket_type || 'standard');
                    break;
            }

            return next;
        });
    };

    const handleSelectCategory = (category: string) => {
        if (selectionMode !== 'CATEGORY') return;

        // Collect all editable seats with the selected ticket type
        const seatsInCategory = layout.items
            .filter(
                (item) =>
                    item.type === 'seat' &&
                    (item as SeatItem).ticket_type === category &&
                    isSeatEditable(item as SeatItem),
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
            const seatId = `${seat.row}${seat.column}`;
            const isSelected = selectedSeats.has(seatId);

            return (
                <div
                    key={colIndex}
                    onClick={() => isEditable && handleSeatClick(seat)}
                    className={`flex h-8 w-8 select-none items-center justify-center rounded border ${getSeatColor(seat)} ${isEditable ? 'cursor-pointer hover:opacity-80' : 'cursor-not-allowed'} ${seat.status === 'booked' ? 'opacity-75' : ''} ${isSelected ? 'ring-2 ring-blue-500' : ''} text-xs`}
                    title={
                        !isEditable
                            ? 'This seat is booked and cannot be edited'
                            : `${seat.seat_number} - ${seat.ticket_type || 'Standard'} - ${seat.status}`
                    }
                >
                    {seat.seat_number}
                </div>
            );
        }
        return <div key={colIndex} className="h-8 w-8"></div>;
    };

    const handleUpdateSelectedSeats = () => {
        const updatedSeats = layout.items
            .filter(
                (item) =>
                    item.type === 'seat' &&
                    selectedSeats.has(
                        `${(item as SeatItem).row}${(item as SeatItem).column}`,
                    ) &&
                    isSeatEditable(item as SeatItem),
            )
            .map((item) => ({
                seat_id: (item as SeatItem).seat_id,
                status: selectedStatus,
                ticket_type: selectedTicketType,
                price: ticketPrice,
            }));

        if (updatedSeats.length > 0) {
            onSave(updatedSeats);
        }
    };

    const handleModeChange = (mode: SelectionMode) => {
        setSelectionMode(mode);
        setSelectedSeats(new Set());
        setSelectedCategory(null);
    };

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

            {/* Category Selection for Category Mode */}
            {selectionMode === 'CATEGORY' && (
                <div className="flex gap-4 rounded-lg bg-gray-50 p-4">
                    {ticketTypes.map((category) => (
                        <button
                            key={category}
                            className={`rounded px-4 py-2 ${
                                selectedCategory === category
                                    ? 'ring-2 ring-blue-500'
                                    : ''
                            } ${categoryColors[category] || 'bg-gray-200'} text-black`}
                            onClick={() => handleSelectCategory(category)}
                        >
                            {category.charAt(0).toUpperCase() +
                                category.slice(1)}
                        </button>
                    ))}
                </div>
            )}

            {/* Ticket Type and Status Configuration */}
            <div className="mt-4 grid grid-cols-2 gap-4 rounded-lg bg-gray-50 p-4">
                <div>
                    <label
                        htmlFor="ticketType"
                        className="block text-sm font-medium text-gray-700"
                    >
                        Ticket Type
                    </label>
                    <select
                        id="ticketType"
                        name="ticketType"
                        className="mt-1 block w-full rounded-md border-gray-300 py-2 pl-3 pr-10 text-base focus:border-indigo-500 focus:outline-none focus:ring-indigo-500 sm:text-sm"
                        value={selectedTicketType}
                        onChange={(e) => setSelectedTicketType(e.target.value)}
                        disabled={selectedSeats.size === 0}
                        aria-label="Select ticket type"
                    >
                        {ticketTypes.map((type) => (
                            <option key={type} value={type}>
                                {type.charAt(0).toUpperCase() + type.slice(1)}
                            </option>
                        ))}
                    </select>
                </div>

                <div>
                    <label
                        htmlFor="seatStatus"
                        className="block text-sm font-medium text-gray-700"
                    >
                        Status
                    </label>
                    <select
                        id="seatStatus"
                        name="seatStatus"
                        className="mt-1 block w-full rounded-md border-gray-300 py-2 pl-3 pr-10 text-base focus:border-indigo-500 focus:outline-none focus:ring-indigo-500 sm:text-sm"
                        value={selectedStatus}
                        onChange={(e) => setSelectedStatus(e.target.value)}
                        disabled={selectedSeats.size === 0}
                        aria-label="Select seat status"
                    >
                        <option value="available">Available</option>
                        <option value="in_transaction">In Transaction</option>
                        <option value="reserved">Reserved</option>
                    </select>
                </div>

                <div>
                    <label
                        htmlFor="ticketPrice"
                        className="block text-sm font-medium text-gray-700"
                    >
                        Price
                    </label>
                    <input
                        id="ticketPrice"
                        name="ticketPrice"
                        type="number"
                        min="0"
                        step="1000"
                        className="mt-1 block w-full rounded-md border-gray-300 py-2 pl-3 pr-10 text-base focus:border-indigo-500 focus:outline-none focus:ring-indigo-500 sm:text-sm"
                        value={ticketPrice}
                        onChange={(e) => setTicketPrice(Number(e.target.value))}
                        disabled={selectedSeats.size === 0}
                        aria-label="Ticket price"
                        placeholder="Enter ticket price"
                    />
                </div>

                <div className="flex items-end">
                    <button
                        className="w-full rounded bg-blue-500 px-4 py-2 text-white hover:bg-blue-600 disabled:opacity-50"
                        onClick={handleUpdateSelectedSeats}
                        disabled={selectedSeats.size === 0}
                    >
                        Apply to Selected ({selectedSeats.size})
                    </button>
                </div>
            </div>

            {/* Legends Section */}
            <div className="mb-8 mt-6">
                <div className="grid grid-cols-2 gap-8">
                    <div className="flex flex-col items-center">
                        <h4 className="mb-2 text-lg font-semibold">
                            Ticket Types
                        </h4>
                        <div className="flex flex-wrap gap-4">
                            {ticketTypes.map((type) => (
                                <div
                                    key={type}
                                    className="flex flex-col items-center"
                                >
                                    <div
                                        className={`h-8 w-8 ${categoryColors[type] || 'bg-gray-200'} rounded-full shadow-md`}
                                    ></div>
                                    <span className="mt-1 text-sm">
                                        {type.charAt(0).toUpperCase() +
                                            type.slice(1)}
                                    </span>
                                </div>
                            ))}
                        </div>
                    </div>
                    <div className="flex flex-col items-center">
                        <h4 className="mb-2 text-lg font-semibold">Status</h4>
                        <div className="flex flex-wrap gap-4">
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

            {/* Selected Seats Summary */}
            {selectedSeats.size > 0 && (
                <div className="mb-4 rounded-lg bg-blue-50 p-4">
                    <h4 className="text-md font-semibold text-blue-800">
                        Selected Seats: {selectedSeats.size}
                    </h4>
                    <p className="text-sm text-blue-600">
                        Will be configured as {selectedTicketType} tickets with{' '}
                        {selectedStatus} status at price {ticketPrice}
                    </p>
                </div>
            )}

            {/* Grid display */}
            <div className="flex w-full flex-col items-center">
                <div className="grid gap-1">
                    {[...grid].reverse().map((row, reversedIndex) => {
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
                    Stage
                </div>
            </div>
        </div>
    );
};

export default SeatMapEditor;
