import React, { useCallback, useEffect, useState } from 'react';
import { Layout, SeatItem } from './types';

interface Props {
    config: Layout;
    onSeatClick?: (seat: SeatItem) => void;
    selectedSeats?: SeatItem[];
    ticketTypeColors?: Record<string, string>;
}

const SeatMapDisplay: React.FC<Props> = ({
    config,
    onSeatClick,
    selectedSeats = [],
    ticketTypeColors = {},
}) => {
    const [rows, setRows] = useState(config.totalRows);
    const [columns, setColumns] = useState(config.totalColumns);

    // Function to find highest row from data
    const findHighestRow = useCallback(() => {
        let maxRowIndex = 0;
        config.items.forEach((item) => {
            if ('seat_id' in item) {
                const rowIndex =
                    typeof item.row === 'string'
                        ? item.row.charCodeAt(0) - 65
                        : item.row;
                maxRowIndex = Math.max(maxRowIndex, rowIndex);
            }
        });
        return maxRowIndex + 1; // Add 1 because index is 0-based
    }, [config.items]);

    // Function to find highest column from data
    const findHighestColumn = useCallback(() => {
        let maxColumn = 0;
        config.items.forEach((item) => {
            if ('seat_id' in item) {
                maxColumn = Math.max(maxColumn, item.column);
            }
        });
        return maxColumn;
    }, [config.items]);

    // Update grid dimensions based on actual data
    useEffect(() => {
        const maxRows = findHighestRow();
        const maxColumns = findHighestColumn();
        setRows(Math.max(maxRows, config.totalRows));
        setColumns(Math.max(maxColumns, config.totalColumns));
    }, [config, findHighestRow, findHighestColumn]);

    // Create the seat grid
    const grid = Array.from({ length: rows }, () => Array(columns).fill(null));

    // Fill grid with seat items
    config.items.forEach((item) => {
        if ('seat_id' in item) {
            const rowIndex =
                typeof item.row === 'string'
                    ? item.row.charCodeAt(0) - 65
                    : item.row;

            if (rowIndex >= 0 && rowIndex < rows) {
                const colIndex = (item.column as number) - 1;
                if (colIndex >= 0 && colIndex < columns) {
                    grid[rowIndex][colIndex] = item;
                }
            }
        }
    });

    // Function to determine seat color based on status and ticket type
    const getSeatColor = (seat: SeatItem): string => {
        // First check if the seat is selected
        const isSelected = selectedSeats.some(
            (s) => s.seat_id === seat.seat_id,
        );
        if (isSelected) {
            return 'bg-green-400'; // Selected seats are green
        }

        // Then check status
        if (seat.status !== 'available') {
            switch (seat.status) {
                case 'booked':
                    return 'bg-red-500';
                case 'in_transaction':
                    return 'bg-yellow-500';
                case 'reserved':
                    return 'bg-blue-300';
                default:
                    return 'bg-gray-300';
            }
        }

        // If available, use ticket type color
        const ticketType = seat.ticket_type || 'standard';
        return (
            ticketTypeColors[ticketType] || 'bg-white border-2 border-gray-300'
        );
    };

    // Function to determine if a seat is selectable
    const isSeatSelectable = (seat: SeatItem): boolean => {
        return seat.status === 'available';
    };

    // Render a single seat cell
    const renderCell = (seat: SeatItem | null, colIndex: number) => {
        if (!seat) {
            return <div key={colIndex} className="h-8 w-8" />;
        }

        const seatColor = getSeatColor(seat);
        const isSelectable = isSeatSelectable(seat);

        return (
            <div
                key={colIndex}
                onClick={() => isSelectable && onSeatClick && onSeatClick(seat)}
                className={`flex h-8 w-8 items-center justify-center rounded border ${seatColor} ${isSelectable ? 'cursor-pointer hover:opacity-80' : 'cursor-not-allowed opacity-75'} text-xs`}
                title={`Seat: ${seat.seat_number} | Type: ${seat.ticket_type || 'Standard'} | Price: ${seat.price} | Status: ${seat.status}`}
            >
                {seat.seat_number}
            </div>
        );
    };

    // Reverse grid to display rows from bottom to top
    const reversedGrid = [...grid].reverse();

    return (
        <div className="flex flex-col items-center">
            <div className="grid gap-1">
                {reversedGrid.map((row, reversedIndex) => (
                    <div
                        key={reversedIndex}
                        className="flex items-center gap-1"
                    >
                        <div className="flex gap-1">
                            {row.map((seat, colIndex) =>
                                renderCell(seat, colIndex),
                            )}
                        </div>
                    </div>
                ))}
            </div>

            {/* Stage */}
            <div className="mt-12 flex h-12 w-[50vw] items-center justify-center rounded border border-gray-200 bg-white">
                Stage
            </div>
        </div>
    );
};

export default SeatMapDisplay;
