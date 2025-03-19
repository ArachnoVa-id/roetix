import React, { useCallback, useEffect, useRef, useState } from 'react';
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
    categoryColors?: Record<string, string>;
    currentTimeline?: {
        timeline_id: string;
        name: string;
        start_date: string;
        end_date: string;
    };
    // Add categoryPrices prop
    categoryPrices?: Record<string, number>;
}

type SelectionMode = 'SINGLE' | 'MULTIPLE' | 'CATEGORY' | 'DRAG';

const SeatMapEditor: React.FC<Props> = ({
    layout,
    onSave,
    ticketTypes,
    categoryColors = {},
    currentTimeline,
    categoryPrices = {}, // Default to empty object
}) => {
    const [selectionMode, setSelectionMode] = useState<SelectionMode>('SINGLE');
    const [selectedSeats, setSelectedSeats] = useState<Set<string>>(new Set());
    const [selectedCategory, setSelectedCategory] = useState<string | null>(
        null,
    );
    const [selectedStatus, setSelectedStatus] = useState<string>('available');
    const [selectedTicketType, setSelectedTicketType] = useState<string>(
        ticketTypes[0] || 'standard',
    );

    // Calculate current price based on selected ticket type
    const [currentPrice, setCurrentPrice] = useState<number>(
        categoryPrices[ticketTypes[0]] || 0,
    );

    // Update the price when ticket type changes
    useEffect(() => {
        if (selectedTicketType && categoryPrices) {
            setCurrentPrice(categoryPrices[selectedTicketType] || 0);
        }
    }, [selectedTicketType, categoryPrices]);

    // Enhanced drag selection state
    const [isDragging, setIsDragging] = useState<boolean>(false);
    const [dragStartSeat, setDragStartSeat] = useState<string | null>(null);
    const [dragStartCoords, setDragStartCoords] = useState<{
        x: number;
        y: number;
    } | null>(null);
    const [selectionBox, setSelectionBox] = useState<{
        left: number;
        top: number;
        width: number;
        height: number;
    } | null>(null);

    const gridRef = useRef<HTMLDivElement>(null);
    const seatRefs = useRef<Map<string, HTMLDivElement>>(new Map());

    // Use provided category colors or defaults
    const getColorForCategory = (category: string): string => {
        // Jika categoryColors (dari ticket categories) tersedia, gunakan itu
        if (categoryColors && categoryColors[category]) {
            // Gunakan nilai hex langsung dari database
            return categoryColors[category];
        }

        // Default colors jika tidak disediakan (gunakan hex)
        const defaultColors: Record<string, string> = {
            standard: '#90CAF9', // biru
            VIP: '#FFD54F', // kuning
        };

        return defaultColors[category] || '#E0E0E0'; // default abu-abu
    };

    // Status color definitions
    const statusLegends = [
        { label: 'Booked', color: 'bg-red-500' },
        { label: 'In Transaction', color: 'bg-yellow-500' },
        { label: 'Reserved', color: 'bg-gray-400' },
    ];

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
        let baseColor = '';

        if (seat.status !== 'available') {
            switch (seat.status) {
                case 'booked':
                    baseColor = '#F44336'; // Merah
                    break;
                case 'in_transaction':
                    baseColor = '#FF9800'; // Oranye
                    break;
                case 'reserved':
                    baseColor = '#9E9E9E'; // Abu-abu
                    break;
            }
        } else {
            // Jika available, tampilkan warna tipe tiket
            const ticketType = seat.ticket_type || 'standard';
            baseColor = getColorForCategory(ticketType);
        }

        return baseColor;
    };

    // Convert row and column to a unique ID
    const getSeatId = (seat: SeatItem): string => `${seat.row}${seat.column}`;

    // Mouse Up handler to end dragging - defined with useCallback to use in dependencies
    const handleMouseUp = useCallback(() => {
        if (isDragging) {
            setIsDragging(false);
            setDragStartSeat(null);
            setDragStartCoords(null);
            setSelectionBox(null);
        }
    }, [isDragging]);

    // Mouse Down handler for drag selection
    const handleMouseDown = (event: React.MouseEvent, seat: SeatItem) => {
        if (!isSeatEditable(seat) || selectionMode !== 'DRAG') return;

        const seatId = getSeatId(seat);

        // Set starting position for the drag
        setDragStartSeat(seatId);
        setDragStartCoords({ x: event.clientX, y: event.clientY });
        setIsDragging(true);

        // Clear previous selection or keep it based on modifier key
        if (!event.shiftKey) {
            setSelectedSeats(new Set([seatId]));
        } else {
            setSelectedSeats((prev) => {
                const next = new Set(prev);
                next.add(seatId);
                return next;
            });
        }

        // Prevent default browser behavior
        event.preventDefault();
    };

    // Mouse Move handler for entire grid area during drag
    const handleGridMouseMove = (event: React.MouseEvent) => {
        if (!isDragging || selectionMode !== 'DRAG' || !gridRef.current) return;

        const gridRect = gridRef.current.getBoundingClientRect();

        // Ensure we have both starting coordinates
        if (!dragStartCoords) return;

        // Calculate the selection box coordinates relative to the grid
        const left = Math.min(dragStartCoords.x, event.clientX) - gridRect.left;
        const top = Math.min(dragStartCoords.y, event.clientY) - gridRect.top;
        const width = Math.abs(event.clientX - dragStartCoords.x);
        const height = Math.abs(event.clientY - dragStartCoords.y);

        setSelectionBox({ left, top, width, height });

        // Find all seats within the selection rectangle
        const newSelectedSeats = new Set<string>();

        // Add seats from previous selection if shift key was held
        if (event.shiftKey && dragStartSeat) {
            selectedSeats.forEach((id) => newSelectedSeats.add(id));
        } else if (dragStartSeat) {
            newSelectedSeats.add(dragStartSeat);
        }

        // Check each seat to see if it's in the selection box
        layout.items.forEach((item) => {
            if (item.type === 'seat' && isSeatEditable(item as SeatItem)) {
                const seatId = getSeatId(item as SeatItem);
                const seatRef = seatRefs.current.get(seatId);

                if (seatRef) {
                    const seatRect = seatRef.getBoundingClientRect();

                    // Check if seat overlaps with selection box
                    const isInSelection =
                        Math.min(dragStartCoords.x, event.clientX) <=
                            seatRect.right &&
                        Math.max(dragStartCoords.x, event.clientX) >=
                            seatRect.left &&
                        Math.min(dragStartCoords.y, event.clientY) <=
                            seatRect.bottom &&
                        Math.max(dragStartCoords.y, event.clientY) >=
                            seatRect.top;

                    if (isInSelection) {
                        newSelectedSeats.add(seatId);
                    }
                }
            }
        });

        setSelectedSeats(newSelectedSeats);
    };

    // Add a window mouse up event listener to handle cases when mouse up occurs outside of grid
    useEffect(() => {
        const handleWindowMouseUp = () => {
            handleMouseUp();
        };

        window.addEventListener('mouseup', handleWindowMouseUp);

        return () => {
            window.removeEventListener('mouseup', handleWindowMouseUp);
        };
    }, [handleMouseUp]);

    // Key press handler for keyboard shortcuts
    useEffect(() => {
        const handleKeyDown = (e: KeyboardEvent) => {
            // Cancel selection on Escape
            if (e.key === 'Escape' && isDragging) {
                setIsDragging(false);
                setDragStartSeat(null);
                setDragStartCoords(null);
                setSelectionBox(null);
                setSelectedSeats(new Set());
            }
        };

        window.addEventListener('keydown', handleKeyDown);

        return () => {
            window.removeEventListener('keydown', handleKeyDown);
        };
    }, [isDragging]);

    // Seat click handler (for single, multiple, and category selection modes)
    const handleSeatClick = (seat: SeatItem) => {
        if (!isSeatEditable(seat) || selectionMode === 'DRAG') return;

        const seatId = getSeatId(seat);

        setSelectedSeats((prev) => {
            const next = new Set(prev);

            switch (selectionMode) {
                case 'SINGLE':
                    next.clear();
                    next.add(seatId);
                    // Set current values from the seat for editing
                    setSelectedStatus(seat.status);
                    setSelectedTicketType(seat.ticket_type || 'standard');
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
                            const id = getSeatId(item as SeatItem);
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
            .map((item) => getSeatId(item as SeatItem));

        setSelectedSeats(new Set(seatsInCategory));
        setSelectedCategory(category);
    };

    const renderCell = (item: LayoutItem | null, colIndex: number) => {
        if (item && item.type === 'seat') {
            const seat = item as SeatItem;
            const isEditable = isSeatEditable(seat);
            const seatId = getSeatId(seat);
            const isSelected = selectedSeats.has(seatId);
            const seatColor = getSeatColor(seat);

            return (
                <div
                    key={colIndex}
                    ref={(el) => {
                        if (el) seatRefs.current.set(seatId, el);
                    }}
                    onClick={() =>
                        isEditable &&
                        selectionMode !== 'DRAG' &&
                        handleSeatClick(seat)
                    }
                    onMouseDown={(e) =>
                        isEditable &&
                        selectionMode === 'DRAG' &&
                        handleMouseDown(e, seat)
                    }
                    className={`flex h-8 w-8 select-none items-center justify-center rounded border ${isEditable ? 'cursor-pointer hover:opacity-80' : 'cursor-not-allowed'} ${seat.status === 'booked' ? 'opacity-75' : ''} ${isSelected ? 'ring-2 ring-blue-500' : ''} text-xs`}
                    style={{ backgroundColor: seatColor }}
                    title={
                        !isEditable
                            ? 'This seat is booked and cannot be edited'
                            : `${seat.seat_number} - ${seat.ticket_type || 'Standard'} - ${seat.status} - ${seat.price || 0}`
                    }
                    draggable={false}
                >
                    {seat.seat_number}
                </div>
            );
        }
        return <div key={colIndex} className="h-8 w-8"></div>;
    };

    const handleUpdateSelectedSeats = () => {
        if (selectedSeats.size === 0) return;

        // Find all the selected seats in the layout
        const updatedSeats = layout.items
            .filter(
                (item) =>
                    item.type === 'seat' &&
                    selectedSeats.has(getSeatId(item as SeatItem)) &&
                    isSeatEditable(item as SeatItem),
            )
            .map((item) => {
                return {
                    seat_id: (item as SeatItem).seat_id,
                    status: selectedStatus,
                    ticket_type: selectedTicketType,
                    // Use the calculated price from the selected ticket type
                    price: currentPrice,
                };
            });

        if (updatedSeats.length > 0) {
            console.log('Sending updated seats:', updatedSeats);
            onSave(updatedSeats);
        }
    };

    const handleModeChange = (mode: SelectionMode) => {
        setSelectionMode(mode);
        setSelectedSeats(new Set());
        setSelectedCategory(null);
        setIsDragging(false);
        setDragStartSeat(null);
        setDragStartCoords(null);
        setSelectionBox(null);
    };

    return (
        <div className="p-6">
            {/* Current Timeline Information */}
            {currentTimeline && (
                <div className="mb-4 rounded-lg bg-blue-50 p-4">
                    <h3 className="font-semibold text-blue-800">
                        Current Timeline: {currentTimeline.name}
                    </h3>
                    <p className="text-sm text-blue-600">
                        {new Date(
                            currentTimeline.start_date,
                        ).toLocaleDateString()}{' '}
                        -{' '}
                        {new Date(
                            currentTimeline.end_date,
                        ).toLocaleDateString()}
                    </p>
                    <p className="mt-2 text-xs text-blue-500">
                        Prices are managed in the ticket category settings.
                    </p>
                </div>
            )}

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
                <button
                    className={`rounded px-4 py-2 ${selectionMode === 'DRAG' ? 'bg-blue-500 text-white' : 'bg-white'}`}
                    onClick={() => handleModeChange('DRAG')}
                >
                    Drag Select
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
                            } ${getColorForCategory(category)} text-black`}
                            onClick={() => handleSelectCategory(category)}
                        >
                            {category.charAt(0).toUpperCase() +
                                category.slice(1)}
                        </button>
                    ))}
                </div>
            )}

            {/* Drag Selection Instructions */}
            {selectionMode === 'DRAG' && (
                <div className="mb-4 rounded-lg bg-blue-50 p-4">
                    <p className="text-sm text-blue-700">
                        Click and drag to select multiple seats at once. Hold
                        Shift to add to existing selection. Press Escape to
                        cancel the current selection.
                    </p>
                </div>
            )}

            <div className="mt-4 grid grid-cols-3 gap-4 rounded-lg bg-gray-50 p-4">
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

                {/* Price Display Field (Read-only) */}
                <div>
                    <label
                        htmlFor="ticketPrice"
                        className="block text-sm font-medium text-gray-700"
                    >
                        Price
                    </label>
                    <div className="mt-1 flex rounded-md shadow-sm">
                        <span className="inline-flex items-center rounded-l-md border border-r-0 border-gray-300 bg-gray-50 px-3 text-gray-500 sm:text-sm">
                            Rp
                        </span>
                        <input
                            type="text"
                            name="ticketPrice"
                            id="ticketPrice"
                            className="block w-full min-w-0 flex-1 rounded-none rounded-r-md border-gray-300 bg-gray-100 py-2 focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                            value={currentPrice.toLocaleString()}
                            disabled
                            readOnly
                            aria-label="Ticket price"
                        />
                    </div>
                    <p className="mt-1 text-xs text-gray-500">
                        Price is determined by ticket category and cannot be
                        edited directly
                    </p>
                </div>

                <div className="col-span-3 flex items-end">
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
                                        className="h-8 w-8 rounded-full shadow-md"
                                        style={{
                                            backgroundColor:
                                                getColorForCategory(type),
                                        }}
                                    ></div>
                                    <span className="mt-1 text-sm">
                                        {type.charAt(0).toUpperCase() +
                                            type.slice(1)}
                                    </span>
                                    <span className="text-xs text-gray-500">
                                        Rp{' '}
                                        {(
                                            categoryPrices[type] || 0
                                        ).toLocaleString()}
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
                        {selectedStatus} status at Rp{' '}
                        {currentPrice.toLocaleString()} each
                    </p>
                </div>
            )}

            {/* Grid display */}
            <div
                className="relative flex w-full flex-col items-center"
                ref={gridRef}
                onMouseMove={handleGridMouseMove}
                onMouseUp={handleMouseUp}
                onMouseLeave={handleMouseUp}
            >
                {/* Visual selection box overlay */}
                {isDragging && selectionBox && (
                    <div
                        className="pointer-events-none absolute z-10 border-2 border-blue-500 bg-blue-100 bg-opacity-20"
                        style={{
                            left: selectionBox.left + 'px',
                            top: selectionBox.top + 'px',
                            width: selectionBox.width + 'px',
                            height: selectionBox.height + 'px',
                        }}
                    />
                )}

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
