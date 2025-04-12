import { SeatItem, SeatMapDisplayProps } from '@/types/seatmap';
import React, { useCallback, useEffect, useState } from 'react';

const SeatMapDisplay: React.FC<SeatMapDisplayProps> = ({
    config,
    onSeatClick,
    selectedSeats = [],
    ticketTypeColors = {},
    props,
    currentTimeline,
    eventStatus = 'active', // Nilai default
}) => {
    // Pindahkan deklarasi fungsi konversi ke awal komponen
    // agar dapat digunakan sebelum panggilan fungsi lain
    const getRowIndex = (label: string): number => {
        let result = 0;

        for (let i = 0; i < label.length; i++) {
            result = result * 26 + (label.charCodeAt(i) - 64);
        }

        return result - 1; // Konversi ke 0-based index
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

    const [rows, setRows] = useState(config.totalRows);
    const [columns, setColumns] = useState(config.totalColumns);

    // Function to find highest row from data
    const findHighestRow = useCallback(() => {
        let maxRowIndex = 0;
        config.items.forEach((item) => {
            if ('id' in item) {
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
        return maxRowIndex + 1; // Add 1 because index is 0-based
    }, [config.items]);

    // Function to find highest column from data
    const findHighestColumn = useCallback(() => {
        let maxColumn = 0;
        config.items.forEach((item) => {
            if ('id' in item) {
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
        if ('id' in item) {
            const rowIndex =
                typeof item.row === 'string' ? getRowIndex(item.row) : item.row;

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
        const isSelected = selectedSeats.some((s) => s.id === seat.id);
        if (isSelected) {
            return '#4CAF50'; // Green untuk selected seats
        }

        // Then check status
        if (seat.status !== 'available') {
            switch (seat.status) {
                case 'booked':
                    return 'rgba(244, 67, 54, 0.5)'; // Merah with 50% opacity
                case 'in_transaction':
                    return 'rgba(255, 152, 0, 0.5)'; // Oranye with 50% opacity
                case 'reserved':
                    return 'rgba(158, 158, 158, 0.5)'; // Abu-abu with 50% opacity
                default:
                    return 'rgba(224, 224, 224, 0.5)'; // Default color with 50% opacity
            }
        }

        // If available, use ticket type color from provided colors
        const ticketType = seat.ticket_type || 'Not Specified';

        // Gunakan warna dari ticket type jika tersedia
        // Ubah format jika perlu - jika ticketTypeColors sudah berisi nilai hex
        return ticketTypeColors[ticketType] || '#FFF';
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

        let borderColor;

        switch (seat.status) {
            case 'booked':
                borderColor = '#F44336'; // Merah
                break;
            case 'in_transaction':
                borderColor = '#FF9800'; // Oranye
                break;
            case 'reserved':
                borderColor = '#9E9E9E'; // Abu-abu
                break;
            default:
                borderColor = '#E0E0E0';
                break;
        }

        // Format the price as IDR
        const formattedPrice = new Intl.NumberFormat('id-ID', {
            style: 'currency',
            currency: 'IDR',
        }).format(seat.price as number);

        // Capitalize the first letter of ticket type and status
        const formattedTicketType = seat.ticket_type
            ? seat.ticket_type.charAt(0).toUpperCase() +
              seat.ticket_type.slice(1)
            : 'Unset';

        const formattedStatus =
            seat.status.charAt(0).toUpperCase() + seat.status.slice(1);

        // Use the formatted variables in the title
        const title = `Seat: ${seat.seat_number} | Type: ${formattedTicketType} | Price: ${formattedPrice} | Status: ${formattedStatus}`;

        return (
            <div
                key={colIndex}
                onClick={() => isSelectable && onSeatClick && onSeatClick(seat)}
                className={`flex h-8 w-8 items-center justify-center rounded border-2 ${isSelectable ? 'cursor-pointer hover:opacity-80' : 'cursor-not-allowed opacity-75'} text-xs`}
                style={{ backgroundColor: seatColor, borderColor }}
                title={title}
            >
                {seat.seat_number}
            </div>
        );
    };

    // Reverse grid to display rows from bottom to top
    const reversedGrid = [...grid].reverse();

    return (
        <div className="flex h-fit w-full flex-col items-center">
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
                className="mx-auto mt-12 flex h-12 w-full max-w-4xl items-center justify-center rounded"
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
