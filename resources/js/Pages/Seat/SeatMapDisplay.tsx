import { EventProps } from '@/types/front-end';
import React, { useCallback, useEffect, useState } from 'react';
import { Layout, SeatItem } from './types';

interface Timeline {
    timeline_id: string;
    name: string;
    start_date: string;
    end_date: string;
}

interface Props {
    config: Layout;
    onSeatClick?: (seat: SeatItem) => void;
    selectedSeats?: SeatItem[];
    ticketTypeColors?: Record<string, string>;
    props: EventProps;
    currentTimeline?: Timeline;
    eventStatus?: string; // Tambahkan ini
}

const SeatMapDisplay: React.FC<Props> = ({
    config,
    onSeatClick,
    selectedSeats = [],
    ticketTypeColors = {},
    props,
    currentTimeline,
    eventStatus = 'active', // Nilai default
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
            return '#4CAF50'; // Green untuk selected seats
        }

        // Then check status
        if (seat.status !== 'available') {
            switch (seat.status) {
                case 'booked':
                    return '#F44336'; // Merah
                case 'in_transaction':
                    return '#FF9800'; // Oranye
                case 'reserved':
                    return '#9E9E9E'; // Abu-abu
                default:
                    return '#E0E0E0';
            }
        }

        // If available, use ticket type color from provided colors
        const ticketType = seat.ticket_type || 'standard';

        // Gunakan warna dari ticket type jika tersedia
        // Ubah format jika perlu - jika ticketTypeColors sudah berisi nilai hex
        return ticketTypeColors[ticketType] || '#FFFFFF';
    };

    // Function to determine if a seat is selectable
    const isSeatSelectable = (seat: SeatItem): boolean => {
        return seat.status === 'available' && eventStatus === 'active';
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
                className={`flex h-8 w-8 items-center justify-center rounded border ${isSelectable ? 'cursor-pointer hover:opacity-80' : 'cursor-not-allowed opacity-75'} text-xs`}
                style={{ backgroundColor: seatColor }}
                title={`Seat: ${seat.seat_number} | Type: ${seat.ticket_type || 'Standard'} | Price: ${seat.price} | Status: ${seat.status}`}
            >
                {seat.seat_number}
            </div>
        );
    };

    // Reverse grid to display rows from bottom to top
    const reversedGrid = [...grid].reverse();

    return (
        <div className="flex h-fit w-full flex-col items-center">
            {/* Tampilkan pesan status event jika tidak active */}
            {/* {eventStatus !== 'active' && (
                <div className="mb-4 w-full rounded-lg bg-yellow-50 p-3 text-center">
                    <p className="text-yellow-800">
                        {eventStatus === 'planned' &&
                            'This event is not yet ready for booking'}
                        {eventStatus === 'completed' &&
                            'This event does not accept booking anymore'}
                        {eventStatus === 'cancelled' &&
                            'This event has been cancelled'}
                    </p>
                </div>
            )} */}
            {/* Timeline Information */}
            {currentTimeline && (
                <div className="mb-4 w-full rounded-lg bg-blue-50 p-3 text-center">
                    <h3 className="font-medium text-blue-800">
                        {currentTimeline.name}
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
                </div>
            )}

            <div className="mx-auto grid gap-1">
                {reversedGrid.map((row, reversedIndex) => (
                    <div
                        key={reversedIndex}
                        className="flex items-center justify-center gap-1"
                    >
                        <div className="flex select-none gap-1">
                            {row.map((seat, colIndex) =>
                                renderCell(seat, colIndex),
                            )}
                        </div>
                    </div>
                ))}
            </div>

            {/* Stage */}
            <div
                className="mt-12 flex h-12 w-full max-w-4xl items-center justify-center rounded"
                style={{
                    backgroundColor: props.primary_color,
                }}
            >
                Stage
            </div>
        </div>
    );
};

export default SeatMapDisplay;
