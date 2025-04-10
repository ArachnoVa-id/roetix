import {
    LayoutItem,
    SeatItem,
    SeatMapEditorProps,
    SelectionMode,
} from '@/types/seatmap';
import React, { useCallback, useEffect, useRef, useState } from 'react';

const SeatMapEditor: React.FC<SeatMapEditorProps> = ({
    layout,
    onSave,
    ticketTypes,
    categoryColors = {},
    categoryPrices = {}, // Default to empty object
}) => {
    const [selectionMode, setSelectionMode] = useState<SelectionMode>('SINGLE');
    const [selectedSeats, setSelectedSeats] = useState<Set<string>>(new Set());
    const [selectedCategory, setSelectedCategory] = useState<string | null>(
        null,
    );
    const [selectedStatus, setSelectedStatus] = useState<string>('available');
    const [selectedTicketType, setSelectedTicketType] = useState<string>(
        ticketTypes[0] || 'unset',
    );
    const sidebarContentRef = useRef<HTMLDivElement>(null);

    // Calculate current price based on selected ticket type
    const [currentPrice, setCurrentPrice] = useState<number>(
        categoryPrices[ticketTypes[0]] || 0,
    );

    const configSectionRef = useRef<HTMLDivElement>(null);

    // Update the price when ticket type changes
    useEffect(() => {
        if (selectedTicketType && categoryPrices) {
            setCurrentPrice(categoryPrices[selectedTicketType] || 0);
        }
    }, [selectedTicketType, categoryPrices]);

    useEffect(() => {
        if (selectedSeats.size > 0 && configSectionRef.current) {
            configSectionRef.current.scrollIntoView({
                behavior: 'smooth',
                block: 'start',
            });
        }
    }, [selectedSeats.size, selectedTicketType, selectedStatus]);

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
            unset: '#FFF',
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
                // Konversi label baris ke angka dengan algoritma yang benar
                let rowIndex = 0;
                if (typeof item.row === 'string') {
                    rowIndex = getRowIndex(item.row);
                } else {
                    rowIndex = item.row;
                }
                maxRowIndex = Math.max(maxRowIndex, rowIndex);
            }
        });
        return maxRowIndex + 1; // Add 1 karena index 0-based
    };

    // const getRowLabel = (index: number): string => {
    //     let label = '';
    //     let n = index + 1; // Konversi ke 1-based

    //     while (n > 0) {
    //         let remainder = n % 26;

    //         if (remainder === 0) {
    //             remainder = 26;
    //             n -= 1;
    //         }

    //         label = String.fromCharCode(64 + remainder) + label;
    //         n = Math.floor(n / 26);
    //     }

    //     return label;
    // };

    const getRowIndex = (label: string): number => {
        let result = 0;

        for (let i = 0; i < label.length; i++) {
            result = result * 26 + (label.charCodeAt(i) - 64);
        }

        return result - 1; // Konversi ke 0-based index
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
                    ? getRowIndex(item.row) // Gunakan fungsi getRowIndex
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
                default:
                    baseColor = '#FFF'; // Putih
            }
        } else {
            // Jika available, tampilkan warna tipe tiket
            const ticketType = seat.ticket_type || 'unset';
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
            if (
                // item.type === 'seat' &&
                isSeatEditable(item as SeatItem)
            ) {
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
                    setSelectedTicketType(seat.ticket_type || 'unset');
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
                            // item.type === 'seat' &&
                            (item as SeatItem).ticket_type ===
                                seat.ticket_type &&
                            isSeatEditable(item as SeatItem)
                        ) {
                            const id = getSeatId(item as SeatItem);
                            next.add(id);
                        }
                    });
                    setSelectedCategory(seat.ticket_type || 'unset');
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
                    // item.type === 'seat' &&
                    (item as SeatItem).ticket_type === category &&
                    isSeatEditable(item as SeatItem),
            )
            .map((item) => getSeatId(item as SeatItem));

        setSelectedSeats(new Set(seatsInCategory));
        setSelectedCategory(category);
    };

    const renderCell = (item: LayoutItem | null, colIndex: number) => {
        if (
            item
            // && item.type === 'seat'
        ) {
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
                    className={`flex h-8 w-8 cursor-pointer select-none items-center justify-center rounded border ${
                        isEditable ? 'hover:opacity-80' : 'cursor-not-allowed'
                    } ${seat.status === 'booked' ? 'opacity-75' : ''} ${
                        isSelected ? 'ring-2 ring-blue-500' : 'border-gray-200'
                    } text-xs font-medium`}
                    style={{ backgroundColor: seatColor }}
                    title={
                        !isEditable
                            ? 'This seat is booked and cannot be edited'
                            : `${seat.seat_number} - ${seat.ticket_type || 'Unset'} - ${seat.status} - ${seat.price || 0}`
                    }
                    draggable={false}
                >
                    {seat.seat_number}
                </div>
            );
        }
        return (
            <div
                key={colIndex}
                className="flex h-8 w-8 items-center justify-center rounded border border-gray-200 bg-gray-100 hover:bg-gray-200"
            ></div>
        );
    };

    const handleUpdateSelectedSeats = () => {
        if (selectedSeats.size === 0) return;

        // Find all the selected seats in the layout
        const updatedSeats = layout.items
            .filter(
                (item) =>
                    // item.type === 'seat' &&
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
            // console.log('Sending updated seats:', updatedSeats);
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

    const [droppedDown, setDroppedDown] = useState(false);
    const handleToggle = () => {
        setDroppedDown((prev) => !prev);
    };

    return (
        <div className="flex h-screen max-md:flex-col">
            {/* Left Panel - Fixed position with constant width */}
            <div
                className={`flex h-fit w-72 flex-col border-r border-gray-200 bg-white shadow-lg max-md:order-2 max-md:w-full md:h-full`}
            >
                {/* Header */}
                <div className="flex w-full justify-between border-b border-gray-200 bg-blue-600 p-4 text-white">
                    <div className="flex w-fit gap-2">
                        <button
                            className="h-full w-fit rounded bg-blue-500 px-1 font-bold text-white hover:bg-blue-700"
                            onClick={() => window.history.back()}
                        >
                            <svg
                                xmlns="http://www.w3.org/2000/svg"
                                width="20"
                                height="20"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                strokeWidth="2"
                                strokeLinecap="round"
                                strokeLinejoin="round"
                            >
                                <polyline points="15 18 9 12 15 6"></polyline>
                            </svg>
                        </button>
                        <h2 className="flex items-center gap-2 text-xl font-bold">
                            <svg
                                xmlns="http://www.w3.org/2000/svg"
                                width="20"
                                height="20"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                strokeWidth="2"
                                strokeLinecap="round"
                                strokeLinejoin="round"
                                className="rotate-90 transform"
                            >
                                <path d="M3 9h18v10a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V9Z"></path>
                                <path d="M3 9V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2v4"></path>
                                <path d="M12 12v5"></path>
                            </svg>
                            Seat Map Editor
                        </h2>
                    </div>
                    <button
                        className={`h-full w-fit rotate-90 rounded-full bg-blue-500 px-1 font-bold text-white duration-500 hover:bg-blue-700 md:hidden ${droppedDown ? 'rotate-90' : '-rotate-90'}`}
                        onClick={handleToggle}
                    >
                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            width="20"
                            height="20"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            strokeWidth="2"
                            strokeLinecap="round"
                            strokeLinejoin="round"
                        >
                            <polyline points="15 18 9 12 15 6"></polyline>
                        </svg>
                    </button>
                </div>

                {/* Scrollable Content */}
                <div
                    className={`flex-l overflow-y-auto duration-500 md:h-full md:p-5 ${droppedDown ? 'max-md:h-0' : 'h-[35vh] p-5'}`}
                    ref={sidebarContentRef}
                >
                    {/* Mode Selection */}
                    <div className="mb-6 overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
                        <div className="border-b border-gray-200 bg-gray-50 p-3">
                            <h3 className="flex items-center gap-2 font-medium text-gray-700">
                                <svg
                                    xmlns="http://www.w3.org/2000/svg"
                                    width="16"
                                    height="16"
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    stroke="currentColor"
                                    strokeWidth="2"
                                    strokeLinecap="round"
                                    strokeLinejoin="round"
                                >
                                    <path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"></path>
                                </svg>
                                Selection Mode
                            </h3>
                        </div>
                        <div className="grid grid-cols-2 gap-4 p-4">
                            <button
                                className={`flex items-center justify-center gap-2 rounded-md border px-3 py-2 text-sm transition-all ${
                                    selectionMode === 'SINGLE'
                                        ? 'border-blue-500 bg-blue-50 text-blue-700'
                                        : 'border-gray-200 bg-white text-gray-700 hover:bg-gray-50'
                                }`}
                                onClick={() => handleModeChange('SINGLE')}
                            >
                                <svg
                                    xmlns="http://www.w3.org/2000/svg"
                                    width="16"
                                    height="16"
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    stroke="currentColor"
                                    strokeWidth="2"
                                    strokeLinecap="round"
                                    strokeLinejoin="round"
                                >
                                    <path d="M12 20h9"></path>
                                    <path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"></path>
                                </svg>
                                Single
                            </button>
                            <button
                                className={`flex items-center justify-center gap-2 rounded-md border px-3 py-2 text-sm transition-all ${
                                    selectionMode === 'MULTIPLE'
                                        ? 'border-blue-500 bg-blue-50 text-blue-700'
                                        : 'border-gray-200 bg-white text-gray-700 hover:bg-gray-50'
                                }`}
                                onClick={() => handleModeChange('MULTIPLE')}
                            >
                                <svg
                                    xmlns="http://www.w3.org/2000/svg"
                                    width="16"
                                    height="16"
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    stroke="currentColor"
                                    strokeWidth="2"
                                    strokeLinecap="round"
                                    strokeLinejoin="round"
                                >
                                    <rect
                                        x="3"
                                        y="3"
                                        width="7"
                                        height="7"
                                    ></rect>
                                    <rect
                                        x="14"
                                        y="3"
                                        width="7"
                                        height="7"
                                    ></rect>
                                    <rect
                                        x="14"
                                        y="14"
                                        width="7"
                                        height="7"
                                    ></rect>
                                    <rect
                                        x="3"
                                        y="14"
                                        width="7"
                                        height="7"
                                    ></rect>
                                </svg>
                                Multiple
                            </button>
                            <button
                                className={`flex items-center justify-center gap-2 rounded-md border px-3 py-2 text-sm transition-all ${
                                    selectionMode === 'CATEGORY'
                                        ? 'border-blue-500 bg-blue-50 text-blue-700'
                                        : 'border-gray-200 bg-white text-gray-700 hover:bg-gray-50'
                                }`}
                                onClick={() => handleModeChange('CATEGORY')}
                            >
                                <svg
                                    xmlns="http://www.w3.org/2000/svg"
                                    width="16"
                                    height="16"
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    stroke="currentColor"
                                    strokeWidth="2"
                                    strokeLinecap="round"
                                    strokeLinejoin="round"
                                >
                                    <path d="M4 20h16a2 2 0 0 0 2-2V8a2 2 0 0 0-2-2h-7.93a2 2 0 0 1-1.66-.9l-.82-1.2A2 2 0 0 0 7.93 3H4a2 2 0 0 0-2 2v13c0 1.1.9 2 2 2Z"></path>
                                </svg>
                                Category
                            </button>
                            <button
                                className={`flex items-center justify-center gap-2 rounded-md border px-3 py-2 text-sm transition-all ${
                                    selectionMode === 'DRAG'
                                        ? 'border-blue-500 bg-blue-50 text-blue-700'
                                        : 'border-gray-200 bg-white text-gray-700 hover:bg-gray-50'
                                }`}
                                onClick={() => handleModeChange('DRAG')}
                            >
                                <svg
                                    xmlns="http://www.w3.org/2000/svg"
                                    width="16"
                                    height="16"
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    stroke="currentColor"
                                    strokeWidth="2"
                                    strokeLinecap="round"
                                    strokeLinejoin="round"
                                >
                                    <path d="M3 3h18v18H3z"></path>
                                </svg>
                                Drag
                            </button>
                        </div>
                    </div>

                    {/* Category Selection for Category Mode */}
                    {selectionMode === 'CATEGORY' && (
                        <div className="mb-6 overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
                            <div className="border-b border-gray-200 bg-gray-50 p-3">
                                <h3 className="flex items-center gap-2 font-medium text-gray-700">
                                    <svg
                                        xmlns="http://www.w3.org/2000/svg"
                                        width="16"
                                        height="16"
                                        viewBox="0 0 24 24"
                                        fill="none"
                                        stroke="currentColor"
                                        strokeWidth="2"
                                        strokeLinecap="round"
                                        strokeLinejoin="round"
                                    >
                                        <path d="M4 20h16a2 2 0 0 0 2-2V8a2 2 0 0 0-2-2h-7.93a2 2 0 0 1-1.66-.9l-.82-1.2A2 2 0 0 0 7.93 3H4a2 2 0 0 0-2 2v13c0 1.1.9 2 2 2Z"></path>
                                    </svg>
                                    Select Ticket Category
                                </h3>
                            </div>
                            <div className="flex flex-wrap gap-3 p-4">
                                {ticketTypes.map((category) => (
                                    <button
                                        key={category}
                                        className={`flex h-14 w-24 flex-col items-center justify-center rounded-lg border-2 p-1 transition-all hover:bg-gray-50 ${
                                            selectedCategory === category
                                                ? 'border-blue-500 ring-2 ring-blue-200'
                                                : 'border-gray-200'
                                        }`}
                                        onClick={() =>
                                            handleSelectCategory(category)
                                        }
                                        style={{
                                            borderColor:
                                                selectedCategory === category
                                                    ? 'rgb(59, 130, 246)'
                                                    : '#e5e7eb',
                                        }}
                                    >
                                        <div
                                            className="h-6 w-6 rounded-full"
                                            style={{
                                                backgroundColor:
                                                    getColorForCategory(
                                                        category,
                                                    ),
                                            }}
                                        ></div>
                                        <span className="mt-1 text-xs font-medium">
                                            {category.charAt(0).toUpperCase() +
                                                category.slice(1)}
                                        </span>
                                    </button>
                                ))}
                            </div>
                        </div>
                    )}

                    {/* Drag Selection Instructions */}
                    {selectionMode === 'DRAG' && (
                        <div className="mb-6 overflow-hidden rounded-xl border border-amber-100 bg-amber-50 shadow-sm">
                            <div className="flex items-start gap-3 p-4">
                                <div className="rounded-full bg-amber-100 p-2 text-amber-600">
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
                                        <circle cx="12" cy="12" r="10"></circle>
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
                                </div>
                                <div>
                                    <p className="font-medium text-amber-800">
                                        Drag Selection Mode
                                    </p>
                                    <p className="mt-1 text-sm text-amber-700">
                                        Click and drag to select multiple seats
                                        at once. Hold{' '}
                                        <kbd className="mx-1 rounded bg-amber-100 px-1.5 py-0.5 text-xs font-semibold">
                                            Shift
                                        </kbd>{' '}
                                        to add to existing selection. Press{' '}
                                        <kbd className="mx-1 rounded bg-amber-100 px-1.5 py-0.5 text-xs font-semibold">
                                            Esc
                                        </kbd>{' '}
                                        to cancel.
                                    </p>
                                </div>
                            </div>
                        </div>
                    )}

                    <div
                        className="mb-6 overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm"
                        ref={configSectionRef}
                    >
                        <div className="border-b border-gray-200 bg-gray-50 p-3">
                            <h3 className="flex items-center gap-2 font-medium text-gray-700">
                                <svg
                                    xmlns="http://www.w3.org/2000/svg"
                                    width="16"
                                    height="16"
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    stroke="currentColor"
                                    strokeWidth="2"
                                    strokeLinecap="round"
                                    strokeLinejoin="round"
                                >
                                    <path d="M12.22 2h-.44a2 2 0 0 0-2 2v.18a2 2 0 0 1-1 1.73l-.43.25a2 2 0 0 1-2 0l-.15-.08a2 2 0 0 0-2.73.73l-.22.38a2 2 0 0 0 .73 2.73l.15.1a2 2 0 0 1 1 1.72v.51a2 2 0 0 1-1 1.74l-.15.09a2 2 0 0 0-.73 2.73l.22.38a2 2 0 0 0 2.73.73l.15-.08a2 2 0 0 1 2 0l.43.25a2 2 0 0 1 1 1.73V20a2 2 0 0 0 2 2h.44a2 2 0 0 0 2-2v-.18a2 2 0 0 1 1-1.73l.43-.25a2 2 0 0 1 2 0l.15.08a2 2 0 0 0 2.73-.73l.22-.39a2 2 0 0 0-.73-2.73l-.15-.08a2 2 0 0 1-1-1.74v-.5a2 2 0 0 1 1-1.74l.15-.09a2 2 0 0 0 .73-2.73l-.22-.38a2 2 0 0 0-2.73-.73l-.15.08a2 2 0 0 1-2 0l-.43-.25a2 2 0 0 1-1-1.73V4a2 2 0 0 0-2-2z"></path>
                                    <circle cx="12" cy="12" r="3"></circle>
                                </svg>
                                Configure Selected Seats
                            </h3>
                        </div>
                        <div className="p-4">
                            <div className="grid grid-cols-1 gap-6">
                                <div>
                                    <label
                                        htmlFor="ticketType"
                                        className="mb-1 block text-sm font-medium text-gray-700"
                                    >
                                        Category
                                    </label>
                                    <div className="relative">
                                        <select
                                            id="ticketType"
                                            name="ticketType"
                                            className={`mt-1 block w-full rounded-md border-gray-300 py-2 pl-3 pr-10 text-base focus:border-indigo-500 focus:outline-none focus:ring-indigo-500 sm:text-sm ${selectedSeats.size === 0 ? 'cursor-not-allowed bg-gray-100' : ''}`}
                                            value={selectedTicketType}
                                            onChange={(e) =>
                                                setSelectedTicketType(
                                                    e.target.value,
                                                )
                                            }
                                            disabled={selectedSeats.size === 0}
                                            aria-label="Select ticket type"
                                        >
                                            {ticketTypes.map((type) => (
                                                <option key={type} value={type}>
                                                    {type
                                                        .charAt(0)
                                                        .toUpperCase() +
                                                        type.slice(1)}
                                                </option>
                                            ))}
                                        </select>
                                        <div className="pointer-events-none absolute inset-y-0 right-0 flex items-center px-2 text-gray-700">
                                            <svg
                                                className="h-4 w-4 fill-current"
                                                xmlns="http://www.w3.org/2000/svg"
                                                viewBox="0 0 20 20"
                                            ></svg>
                                        </div>
                                    </div>
                                </div>

                                <div>
                                    <label
                                        htmlFor="seatStatus"
                                        className="mb-1 block text-sm font-medium text-gray-700"
                                    >
                                        Status
                                    </label>
                                    <div className="relative">
                                        <select
                                            id="seatStatus"
                                            name="seatStatus"
                                            className={`mt-1 block w-full rounded-md border-gray-300 py-2 pl-3 pr-10 text-base focus:border-indigo-500 focus:outline-none focus:ring-indigo-500 sm:text-sm ${selectedSeats.size === 0 ? 'cursor-not-allowed bg-gray-100' : ''}`}
                                            value={selectedStatus}
                                            onChange={(e) =>
                                                setSelectedStatus(
                                                    e.target.value,
                                                )
                                            }
                                            disabled={selectedSeats.size === 0}
                                            aria-label="Select seat status"
                                        >
                                            <option value="available">
                                                Available
                                            </option>
                                            <option value="in_transaction">
                                                In Transaction
                                            </option>
                                            <option value="reserved">
                                                Reserved
                                            </option>
                                        </select>
                                        <div className="pointer-events-none absolute inset-y-0 right-0 flex items-center px-2 text-gray-700">
                                            <svg
                                                className="h-4 w-4 fill-current"
                                                xmlns="http://www.w3.org/2000/svg"
                                                viewBox="0 0 20 20"
                                            ></svg>
                                        </div>
                                    </div>
                                </div>

                                <div>
                                    <label
                                        htmlFor="ticketPrice"
                                        className="mb-1 block text-sm font-medium text-gray-700"
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
                                        Prices are automatically set based on
                                        ticket category
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    {/* Legends Section */}
                    <div className="mb-8 overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
                        <div className="border-b border-gray-200 bg-gray-50 p-3">
                            <h3 className="flex items-center gap-2 font-medium text-gray-700">
                                <svg
                                    xmlns="http://www.w3.org/2000/svg"
                                    width="16"
                                    height="16"
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    stroke="currentColor"
                                    strokeWidth="2"
                                    strokeLinecap="round"
                                    strokeLinejoin="round"
                                >
                                    <circle cx="12" cy="12" r="10"></circle>
                                    <line x1="12" y1="8" x2="12" y2="12"></line>
                                    <line
                                        x1="12"
                                        y1="16"
                                        x2="12.01"
                                        y2="16"
                                    ></line>
                                </svg>
                                Legend
                            </h3>
                        </div>
                        <div className="grid grid-cols-1 gap-2 p-4">
                            <div className="overflow-hidden rounded-lg border border-gray-100 bg-gray-50">
                                <div className="border-b border-gray-100 px-3 py-2 text-center text-sm font-medium text-gray-600">
                                    Category
                                </div>
                                <div className="flex flex-wrap items-center justify-center gap-6 p-4">
                                    {ticketTypes.map((type) => (
                                        <div
                                            key={type}
                                            className="flex flex-col items-center"
                                        >
                                            <div
                                                className="h-8 w-8 rounded-full shadow-sm"
                                                style={{
                                                    backgroundColor:
                                                        getColorForCategory(
                                                            type,
                                                        ),
                                                }}
                                            ></div>
                                            <span className="mt-1 text-sm font-medium">
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
                            <div className="overflow-hidden rounded-lg border border-gray-100 bg-gray-50">
                                <div className="border-b border-gray-100 px-3 py-2 text-center text-sm font-medium text-gray-600">
                                    Seat Status
                                </div>
                                <div className="flex flex-wrap items-center justify-center gap-6 p-4">
                                    <div className="flex flex-col items-center">
                                        <div className="h-8 w-8 rounded-full bg-green-500 shadow-sm"></div>
                                        <span className="mt-1 text-sm font-medium">
                                            Available
                                        </span>
                                    </div>
                                    {statusLegends.map((legend, i) => (
                                        <div
                                            key={i}
                                            className="flex flex-col items-center"
                                        >
                                            <div
                                                className={`h-8 w-8 ${legend.color} rounded-full shadow-sm`}
                                            ></div>
                                            <span className="mt-1 text-sm font-medium">
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
                        <div className="mb-6 overflow-hidden rounded-xl border border-green-100 bg-green-50 shadow-sm">
                            <div className="flex items-start gap-3 p-4">
                                <div className="rounded-full bg-green-100 p-2 text-green-600">
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
                                        <path d="M3 7V5a2 2 0 0 1 2-2h2"></path>
                                        <path d="M17 3h2a2 2 0 0 1 2 2v2"></path>
                                        <path d="M21 17v2a2 2 0 0 1-2 2h-2"></path>
                                        <path d="M7 21H5a2 2 0 0 1-2-2v-2"></path>
                                        <path d="M8 7v10"></path>
                                        <path d="M16 7v10"></path>
                                        <path d="M7 12h10"></path>
                                    </svg>
                                </div>
                                <div>
                                    <p className="font-medium text-green-800">
                                        Selected: {selectedSeats.size} seats
                                    </p>
                                    <p className="mt-1 text-sm text-green-700">
                                        Will be configured as{' '}
                                        <span className="font-semibold">
                                            {selectedTicketType}
                                        </span>{' '}
                                        tickets with{' '}
                                        <span className="font-semibold">
                                            {selectedStatus}
                                        </span>{' '}
                                        status at{' '}
                                        <span className="font-semibold">
                                            Rp {currentPrice.toLocaleString()}
                                        </span>{' '}
                                        each
                                    </p>
                                </div>
                            </div>
                        </div>
                    )}
                </div>

                {/* Save Button */}
                <div
                    className={`overflow-hidden border-gray-200 bg-gray-50 duration-500 md:border-t md:p-4 ${droppedDown ? 'max-md:h-0' : 'border-t p-4'}`}
                >
                    <button
                        onClick={handleUpdateSelectedSeats}
                        className={`flex w-full items-center justify-center gap-2 rounded-md bg-blue-600 px-4 py-2 font-medium text-white shadow-sm transition-all hover:bg-blue-700 ${selectedSeats.size === 0 ? 'cursor-not-allowed opacity-50' : 'hover:shadow'}`}
                        disabled={selectedSeats.size === 0}
                    >
                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            width="16"
                            height="16"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            strokeWidth="2"
                            strokeLinecap="round"
                            strokeLinejoin="round"
                        >
                            <path d="M20 6 9 17l-5-5" />
                        </svg>
                        Apply to Selected ({selectedSeats.size} seats)
                    </button>
                </div>
            </div>

            {/* Main Content - Seat Map */}
            <div className="flex-1 overflow-auto bg-gray-50 max-md:order-1">
                <div className="flex h-full w-full items-start justify-center p-4">
                    {/* Scrollable Seat Map Container - Just one scrollable area */}
                    <div className="relative flex w-full flex-col items-center overflow-auto rounded-lg border border-gray-300 bg-gray-100 p-2">
                        {/* Grid container */}
                        <div
                            className="relative m-auto p-4"
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
                                {[...grid]
                                    .reverse()
                                    .map((row, reversedIndex) => (
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
                                    ))}
                            </div>

                            {/* Stage - Fixed at the bottom of the grid */}
                            <div className="mx-auto mt-8 flex h-12 w-64 items-center justify-center rounded-lg border border-gray-400 bg-gray-200 font-medium text-gray-700">
                                <span className="flex items-center justify-center gap-2">
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
                                            x="4"
                                            y="5"
                                            width="16"
                                            height="14"
                                            rx="2"
                                        ></rect>
                                    </svg>
                                    Stage
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    );
};

export default SeatMapEditor;
