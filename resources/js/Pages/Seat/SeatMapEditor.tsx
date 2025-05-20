import React, { useCallback, useEffect, useRef, useState } from 'react';

// Import shared components
import { SelectionMode } from '@/types/seatmap';
import { EditorLayout } from './shared/components/Layout';
import { CategorySelection } from './shared/components/seat/CategorySelection';
import { DragInstructions } from './shared/components/seat/DragInstructions';
import { Legend } from './shared/components/seat/Legend';
import { ModeSelection } from './shared/components/seat/ModeSelection';
import { SeatItem } from './shared/components/seat/SeatCell';
import { SeatConfigPanel } from './shared/components/seat/SeatConfigPanel';
import { SelectedSeatsInfo } from './shared/components/seat/SelectedSeatsInfo';
import { Stage } from './shared/components/Stage';

// Types
interface Layout {
    totalRows: number;
    totalColumns: number;
    items: SeatItem[];
}

interface SeatMapEditorProps {
    layout: Layout;
    onSave: (updatedSeats: Partial<SeatItem>[]) => void;
    ticketTypes: string[];
    categoryColors?: Record<string, string>;
    categoryPrices?: Record<string, number>;
}

const SeatMapEditor: React.FC<SeatMapEditorProps> = ({
    layout,
    onSave,
    ticketTypes,
    categoryColors = {},
    categoryPrices = {},
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
    // const [selectionBox, setSelectionBox] = useState<{
    //     left: number;
    //     top: number;
    //     width: number;
    //     height: number;
    // } | null>(null);

    const gridRef = useRef<HTMLDivElement>(null);
    const seatRefs = useRef<Map<string, HTMLDivElement>>(new Map());

    // For mobile dropdown state
    const [droppedDown, setDroppedDown] = useState<boolean>(false);
    const handleToggle = () => {
        setDroppedDown((prev) => !prev);
    };

    // Use provided category colors or defaults
    const getColorForCategory = (category: string): string => {
        // If categoryColors (from ticket categories) is available, use that
        if (categoryColors && categoryColors[category]) {
            // Use hex value directly from database
            return categoryColors[category];
        }

        // Default colors if not provided (use hex)
        const defaultColors: Record<string, string> = {
            unset: '#FFF',
        };

        return defaultColors[category] || '#E0E0E0'; // default gray
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
            if ('id' in item) {
                // Convert row label to number with correct algorithm
                let rowIndex = 0;
                if (typeof item.row === 'string') {
                    rowIndex = getRowIndex(item.row);
                } else {
                    rowIndex = item.row;
                }
                maxRowIndex = Math.max(maxRowIndex, rowIndex);
            }
        });
        return maxRowIndex + 1; // Add 1 because index is 0-based
    };

    const getRowIndex = (label: string): number => {
        let result = 0;

        for (let i = 0; i < label.length; i++) {
            result = result * 26 + (label.charCodeAt(i) - 64);
        }

        return result - 1; // Convert to 0-based index
    };

    const findHighestColumn = (): number => {
        let maxColumn = 0;
        layout.items.forEach((item) => {
            if ('id' in item) {
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
        if ('id' in item) {
            const rowIndex =
                typeof item.row === 'string'
                    ? getRowIndex(item.row) // Use getRowIndex function
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
                    return 'bg-red-400 text-white hover:bg-red-500';
                case 'in_transaction':
                    return 'bg-amber-400 text-gray-800 hover:bg-amber-500';
                case 'reserved':
                    return 'bg-gray-400 text-white hover:bg-gray-500';
                default:
                    return 'bg-white';
            }
        } else {
            // If available, show ticket type color
            const ticketType = seat.ticket_type || 'unset';
            baseColor = getColorForCategory(ticketType);
        }

        // Pentingnya ada di sini - jika baseColor adalah hex, gunakan sebagai style, jika tidak pakai className
        if (baseColor.startsWith('#')) {
            return ''; // Return empty string so we use style attribute instead
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
            // setSelectionBox(null);
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
    const handleGridMouseMove = (event: React.MouseEvent<HTMLDivElement>) => {
        if (!isDragging || selectionMode !== 'DRAG' || !gridRef.current) return;

        // const gridRect = gridRef.current.getBoundingClientRect();

        // Ensure we have both starting coordinates
        if (!dragStartCoords) return;

        // Calculate the selection box coordinates relative to the grid
        // const left = Math.min(dragStartCoords.x, event.clientX) - gridRect.left;
        // const top = Math.min(dragStartCoords.y, event.clientY) - gridRect.top;
        // const width = Math.abs(event.clientX - dragStartCoords.x);
        // const height = Math.abs(event.clientY - dragStartCoords.y);

        // setSelectionBox({ left, top, width, height });

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
            if (isSeatEditable(item as SeatItem)) {
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
                // setSelectionBox(null);
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
                    (item as SeatItem).ticket_type === category &&
                    isSeatEditable(item as SeatItem),
            )
            .map((item) => getSeatId(item as SeatItem));

        setSelectedSeats(new Set(seatsInCategory));
        setSelectedCategory(category);
    };

    const renderCell = (item: SeatItem | null, colIndex: number) => {
        if (item) {
            const seat = item;
            const isEditable = isSeatEditable(seat);
            const seatId = getSeatId(seat);
            const isSelected = selectedSeats.has(seatId);
            const seatColor = getSeatColor(seat);
            // Penting: pastikan style diterapkan hanya jika warna dimulai dengan '#'
            const seatStyle =
                seatColor.startsWith('#') || !seatColor
                    ? {
                          backgroundColor: getColorForCategory(
                              seat.ticket_type || 'unset',
                          ),
                      }
                    : {};

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
                    } text-xs font-medium ${seatColor}`}
                    style={seatStyle}
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
                    selectedSeats.has(getSeatId(item as SeatItem)) &&
                    isSeatEditable(item as SeatItem),
            )
            .map((item) => {
                return {
                    id: (item as SeatItem).id,
                    status: selectedStatus,
                    ticket_type: selectedTicketType,
                    price: currentPrice,
                };
            });

        if (updatedSeats.length > 0) {
            onSave(updatedSeats);
        }
    };

    const handleModeChange = (mode: string) => {
        setSelectionMode(mode as SelectionMode);
        setSelectedSeats(new Set());
        setSelectedCategory(null);
        setIsDragging(false);
        setDragStartSeat(null);
        setDragStartCoords(null);
        // setSelectionBox(null);
    };

    // Build sidebar content
    const sidebarContent = (
        <>
            <ModeSelection
                mode={selectionMode}
                onModeChange={handleModeChange}
                modes={['SINGLE', 'MULTIPLE', 'CATEGORY', 'DRAG']}
            />

            {selectionMode === 'CATEGORY' && (
                <CategorySelection
                    categories={ticketTypes}
                    selectedCategory={selectedCategory}
                    onSelectCategory={handleSelectCategory}
                    getCategoryColor={getColorForCategory}
                />
            )}

            {selectionMode === 'DRAG' && <DragInstructions />}

            <div ref={configSectionRef}>
                <SeatConfigPanel
                    selectedCount={selectedSeats.size}
                    selectedTicketType={selectedTicketType}
                    onTicketTypeChange={setSelectedTicketType}
                    selectedStatus={selectedStatus}
                    onStatusChange={setSelectedStatus}
                    currentPrice={currentPrice}
                    ticketTypes={ticketTypes}
                />
            </div>

            <Legend
                ticketTypes={ticketTypes}
                categoryPrices={categoryPrices}
                statusLegends={statusLegends}
                getColorForCategory={getColorForCategory}
            />

            <SelectedSeatsInfo
                count={selectedSeats.size}
                ticketType={selectedTicketType}
                status={selectedStatus}
                price={currentPrice}
            />
        </>
    );

    // Render grid - Modified to better match GridSeatEditor style
    const renderGrid = () => {
        return (
            <div className="flex h-full items-center justify-center">
                <div className="h-full w-full p-4">
                    <div className="relative h-full w-full rounded-3xl border-2 border-dashed border-gray-300 bg-white p-4">
                        <div
                            className="h-full overflow-auto"
                            ref={gridRef}
                            onMouseMove={handleGridMouseMove}
                            onMouseUp={handleMouseUp}
                            onMouseLeave={handleMouseUp}
                        >
                            <div className="min-w-fit p-4">
                                <div className="flex h-full items-center justify-center">
                                    <div className="grid grid-flow-row gap-1">
                                        {/* Visual selection box overlay */}
                                        {isDragging && (
                                            <div
                                                className="pointer-events-none absolute z-10 border-2 border-blue-500 bg-blue-100 bg-opacity-20"
                                                // style={{
                                                //     left:
                                                //         selectionBox.left +
                                                //         'px',
                                                //     top:
                                                //         selectionBox.top + 'px',
                                                //     width:
                                                //         selectionBox.width +
                                                //         'px',
                                                //     height:
                                                //         selectionBox.height +
                                                //         'px',
                                                // }}
                                            />
                                        )}

                                        {/* Grid rows */}
                                        {[...grid]
                                            .reverse()
                                            .map((row, reversedIndex) => (
                                                <div
                                                    key={reversedIndex}
                                                    className="flex gap-1"
                                                >
                                                    {row.map((item, colIndex) =>
                                                        renderCell(
                                                            item,
                                                            colIndex,
                                                        ),
                                                    )}
                                                </div>
                                            ))}
                                    </div>
                                </div>

                                {/* Stage */}
                                <Stage />
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        );
    };

    return (
        <EditorLayout
            sidebar={{
                icon: (
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
                ),
                title: 'Event Editor',
                content: sidebarContent,
                contentRef: sidebarContentRef,
                footer: (
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
                ),
            }}
            content={renderGrid()}
            droppedDown={droppedDown}
            handleToggle={handleToggle}
        />
    );
};

export default SeatMapEditor;
