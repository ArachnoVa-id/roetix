import { Button } from '@/Components/ui/button';
import { GridCell, GridDimensions, GridSeatEditorProps } from '@/types/editor';
import { Layout, LayoutItem, SeatItem, SeatStatus } from '@/types/seatmap';
import { MousePointer, Plus, Square, Trash2 } from 'lucide-react';
import React, { useCallback, useEffect, useRef, useState } from 'react';

// Helper function to convert Excel-style column label to number
const getRowNumber = (label: string): number => {
    let result = 0;
    for (let i = 0; i < label.length; i++) {
        result *= 26;
        result += label.charCodeAt(i) - 64; // 'A' is 65 in ASCII
    }
    return result;
};

type EditorMode = 'add' | 'delete' | 'block';

const GridSeatEditor: React.FC<GridSeatEditorProps> = ({
    initialLayout,
    onSave,
    isDisabled,
    // venueId,
}) => {
    const [dimensions, setDimensions] = useState<GridDimensions>({
        top: 0,
        bottom: 10,
        left: 0,
        right: 15,
    });

    const [grid, setGrid] = useState<GridCell[][]>([]);
    const [mode, setMode] = useState<EditorMode>('add');
    const [isMouseDown, setIsMouseDown] = useState(false);
    const [startCell, setStartCell] = useState<{
        row: number;
        col: number;
    } | null>(null);
    const [endCell, setEndCell] = useState<{ row: number; col: number } | null>(
        null,
    );

    // State for improved block mode
    const [isDragging, setIsDragging] = useState<boolean>(false);
    const [blockedArea, setBlockedArea] = useState<{
        minRow: number;
        maxRow: number;
        minCol: number;
        maxCol: number;
    } | null>(null);

    // Default values for new seats
    const defaultCategory = 'unset';
    const defaultStatus: SeatStatus = 'available';

    const totalRows = dimensions.top + dimensions.bottom;
    const totalColumns = dimensions.left + dimensions.right;
    const blockActionsRef = useRef<HTMLDivElement>(null);
    const sidebarContentRef = useRef<HTMLDivElement>(null);

    // Function to check if a cell is in the most recently blocked/unblocked area
    const isInBlockedArea = (rowIndex: number, colIndex: number): boolean => {
        if (!blockedArea) return false;

        return (
            rowIndex >= blockedArea.minRow &&
            rowIndex <= blockedArea.maxRow &&
            colIndex >= blockedArea.minCol &&
            colIndex <= blockedArea.maxCol
        );
    };

    // Handle mode change with cleanup
    const handleModeChange = (newMode: EditorMode) => {
        // Clear blocked area highlight when switching out of block mode
        if (mode === 'block') {
            setBlockedArea(null);
        }
        setMode(newMode);
    };

    // Function to find highest row and adjust dimensions
    const findHighestRow = (items: LayoutItem[]): number => {
        let maxRow = 0;
        items.forEach((item) => {
            // if (item.type === 'seat') {
            const rowNum =
                typeof item.row === 'string'
                    ? getRowNumber(item.row) - 1
                    : item.row;
            maxRow = Math.max(maxRow, rowNum);
            // }
        });
        return maxRow;
    };

    // Function to find highest column and adjust dimensions
    const findHighestColumn = (items: LayoutItem[]): number => {
        let maxCol = 0;
        items.forEach((item) => {
            // if (item.type === 'seat') {
            maxCol = Math.max(maxCol, item.column);
            // }
        });
        return maxCol;
    };

    const initializeGrid = useCallback(() => {
        const newGrid: GridCell[][] = Array(totalRows)
            .fill(null)
            .map(() =>
                Array(totalColumns)
                    .fill(null)
                    .map(() => ({ type: 'empty' })),
            );

        if (initialLayout) {
            initialLayout.items.forEach((item: SeatItem) => {
                // if (item.type === 'seat') {
                const seatItem = item;
                const rowIndex =
                    typeof seatItem.row === 'string'
                        ? getRowNumber(seatItem.row) - 1 + dimensions.top
                        : seatItem.row + dimensions.top;
                const colIndex = seatItem.column - 1 + dimensions.left;

                if (
                    rowIndex >= 0 &&
                    rowIndex < totalRows &&
                    colIndex >= 0 &&
                    colIndex < totalColumns
                ) {
                    newGrid[rowIndex][colIndex] = {
                        type: 'seat',
                        item: seatItem,
                    };
                }
                // }
            });
        }

        setGrid(newGrid);
    }, [
        dimensions.top,
        dimensions.left,
        initialLayout,
        totalRows,
        totalColumns,
    ]);

    // Initialize grid when dimensions change
    useEffect(() => {
        initializeGrid();
    }, [
        dimensions.top,
        dimensions.bottom,
        dimensions.left,
        dimensions.right,
        initializeGrid,
    ]);

    // Initialize dimensions based on initialLayout
    useEffect(() => {
        if (initialLayout?.items?.length) {
            const maxRow = findHighestRow(initialLayout.items);
            const maxCol = findHighestColumn(initialLayout.items);
            setDimensions((prev) => ({
                ...prev,
                bottom: Math.max(maxRow + 1, prev.bottom),
                right: Math.max(maxCol, prev.right),
            }));
        }
    }, [initialLayout]);

    // Reset state when mode changes
    useEffect(() => {
        setIsDragging(false);
        setIsMouseDown(false);
        setStartCell(null);
        setEndCell(null);
        setBlockedArea(null);
    }, [mode]);

    const handleCellClick = (rowIndex: number, colIndex: number) => {
        const cell = grid[rowIndex][colIndex];

        if (mode === 'add') {
            if (cell.type === 'empty' && !cell.isBlocked) {
                // Immediately add a seat with default values
                addSeatAtPosition(rowIndex, colIndex);
            }
        } else if (mode === 'delete') {
            if (cell.type === 'seat') {
                // Delete seat directly without confirmation
                deleteSeat(rowIndex, colIndex);
            }
        } else if (mode === 'block') {
            // Toggle blocked status
            toggleBlockedCell(rowIndex, colIndex);
        }
    };

    const handleMouseDown = (rowIndex: number, colIndex: number) => {
        if (mode !== 'block') return;

        setIsMouseDown(true);
        setIsDragging(true);
        setStartCell({ row: rowIndex, col: colIndex });
        setEndCell({ row: rowIndex, col: colIndex });
    };

    const handleMouseOver = (rowIndex: number, colIndex: number) => {
        if (!isMouseDown || mode !== 'block') return;

        setEndCell({ row: rowIndex, col: colIndex });
    };

    const handleMouseUp = () => {
        if (!isMouseDown || mode !== 'block' || !startCell || !endCell) return;

        // Process blocked area
        const minRow = Math.min(startCell.row, endCell.row);
        const maxRow = Math.max(startCell.row, endCell.row);
        const minCol = Math.min(startCell.col, endCell.col);
        const maxCol = Math.max(startCell.col, endCell.col);

        const newGrid = [...grid];

        // Determine if we're blocking or unblocking based on the first cell
        const firstCell = newGrid[startCell.row][startCell.col];
        const isBlocking = !firstCell.isBlocked;

        for (let i = minRow; i <= maxRow; i++) {
            for (let j = minCol; j <= maxCol; j++) {
                // Toggle isBlocked flag instead of changing the cell type
                newGrid[i][j] = {
                    ...newGrid[i][j],
                    isBlocked: isBlocking,
                };
            }
        }

        // Save the blocked area so we can highlight it
        setBlockedArea({ minRow, maxRow, minCol, maxCol });

        setGrid(newGrid);
        setIsMouseDown(false);
        setIsDragging(false);
        setStartCell(null);
        setEndCell(null);

        // Scroll sidebar to block actions after a short delay
        setTimeout(() => {
            if (blockActionsRef.current && sidebarContentRef.current) {
                blockActionsRef.current.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start',
                });
            }
        }, 100);
    };

    // Function to toggle a single cell's blocked status
    const toggleBlockedCell = (rowIndex: number, colIndex: number) => {
        const newGrid = [...grid];
        const currentCell = newGrid[rowIndex][colIndex];

        // Toggle the isBlocked flag
        newGrid[rowIndex][colIndex] = {
            ...currentCell,
            isBlocked: !currentCell.isBlocked,
        };

        setGrid(newGrid);
    };

    // Function to get row label from bottom-up position
    const getAdjustedRowLabel = (index: number, totalRows: number): string => {
        const rowFromBottom = index + 1;

        if (rowFromBottom <= 0 || rowFromBottom > totalRows) return '';

        let label = '';
        let n = rowFromBottom;

        while (n > 0) {
            n--;
            label = String.fromCharCode(65 + (n % 26)) + label;
            n = Math.floor(n / 26);
        }

        return label;
    };

    // Function to add a seat at the specified position
    const addSeatAtPosition = (rowIndex: number, colIndex: number) => {
        const newGrid = [...grid];
        const rowLabel = getAdjustedRowLabel(rowIndex, totalRows);
        const adjustedColumn = colIndex + 1;

        const newSeat: SeatItem = {
            // type: 'seat',
            seat_id: '', // Kosongkan seat_id, akan dibuat di backend
            seat_number: `${rowLabel}${adjustedColumn}`,
            row: rowLabel,
            column: adjustedColumn,
            status: defaultStatus,
            category: defaultCategory,
            price: 0,
        };

        // Keep the isBlocked flag if it exists
        const isBlocked = newGrid[rowIndex][colIndex].isBlocked || false;

        newGrid[rowIndex][colIndex] = {
            type: 'seat',
            item: newSeat,
            isBlocked,
        };

        setGrid(newGrid);
        reorderSeatNumbers();
    };

    const deleteSeat = (rowIndex: number, colIndex: number) => {
        const newGrid = [...grid];
        // Preserve the isBlocked flag when deleting a seat
        const isBlocked = newGrid[rowIndex][colIndex].isBlocked || false;

        newGrid[rowIndex][colIndex] = {
            type: 'empty',
            isBlocked,
        };

        setGrid(newGrid);
        reorderSeatNumbers();
    };

    // Function to add seats to all empty cells in the blocked area
    const addSeatsToBlockedArea = () => {
        if (!blockedArea) return;

        const newGrid = [...grid];

        for (let i = blockedArea.minRow; i <= blockedArea.maxRow; i++) {
            for (let j = blockedArea.minCol; j <= blockedArea.maxCol; j++) {
                const cell = newGrid[i][j];
                if (cell.type === 'empty' && cell.isBlocked) {
                    const rowLabel = getAdjustedRowLabel(i, totalRows);
                    const adjustedColumn = j + 1;

                    const newSeat: SeatItem = {
                        // type: 'seat',
                        seat_id: '', // Kosongkan seat_id, akan dibuat di backend
                        seat_number: `${rowLabel}${adjustedColumn}`,
                        row: rowLabel,
                        column: adjustedColumn,
                        status: defaultStatus,
                        category: defaultCategory,
                        price: 0,
                    };

                    newGrid[i][j] = {
                        type: 'seat',
                        item: newSeat,
                        isBlocked: true,
                    };
                }
            }
        }

        setGrid(newGrid);
        reorderSeatNumbers();
    };

    // Function to delete all seats in the blocked area
    const deleteSeatsFromBlockedArea = () => {
        if (!blockedArea) return;

        const newGrid = [...grid];

        for (let i = blockedArea.minRow; i <= blockedArea.maxRow; i++) {
            for (let j = blockedArea.minCol; j <= blockedArea.maxCol; j++) {
                const cell = newGrid[i][j];
                if (cell.type === 'seat' && cell.isBlocked) {
                    newGrid[i][j] = {
                        type: 'empty',
                        isBlocked: true,
                    };
                }
            }
        }

        setGrid(newGrid);
        reorderSeatNumbers();
    };

    const reorderSeatNumbers = () => {
        const newGrid = [...grid];
        const seatCounters: { [key: string]: number } = {};

        // Process from bottom to top
        for (let i = totalRows - 1; i >= 0; i--) {
            const rowLabel = getAdjustedRowLabel(i, totalRows);
            seatCounters[rowLabel] = 1;

            for (let j = 0; j < totalColumns; j++) {
                const cell = newGrid[i][j];
                if (cell.type === 'seat' && cell.item) {
                    cell.item.row = rowLabel;
                    cell.item.seat_number = `${rowLabel}${seatCounters[rowLabel]}`;
                    // Hapus bagian ini, biarkan seat_id diatur oleh backend
                    // cell.item.seat_id = `${rowLabel}${seatCounters[rowLabel]}`;
                    seatCounters[rowLabel]++;
                }
            }
        }

        setGrid(newGrid);
    };

    // Modified handleSave function to add seats to blocked empty spaces
    // Modified handleSave function to respect deleted seats
    const handleSave = () => {
        // Create a copy of the grid to work with
        const newGrid = [...grid];

        // Reorder seat numbers to ensure consistency
        const tempGrid = [...newGrid];
        const seatCounters: { [key: string]: number } = {};

        for (let i = totalRows - 1; i >= 0; i--) {
            const rowLabel = getAdjustedRowLabel(i, totalRows);
            seatCounters[rowLabel] = 1;

            for (let j = 0; j < totalColumns; j++) {
                const cell = tempGrid[i][j];
                if (cell.type === 'seat' && cell.item) {
                    cell.item.row = rowLabel;
                    // Only generate seat_number, seat_id will be generated on server
                    cell.item.seat_number = `${rowLabel}${seatCounters[rowLabel]}`;

                    // We're not setting seat_id here as it will be generated on the server
                    // based on the venue_id and seat_number

                    seatCounters[rowLabel]++;
                }
            }
        }

        // Now collect all seats for the final layout
        const items: SeatItem[] = [];

        for (let i = 0; i < totalRows; i++) {
            const rowLabel = getAdjustedRowLabel(i, totalRows);

            for (let j = 0; j < totalColumns; j++) {
                const cell = tempGrid[i][j];
                // We only include actual seats, not empty blocked spaces
                if (cell.type === 'seat' && cell.item) {
                    const adjustedColumn = j + 1;
                    items.push({
                        ...cell.item,
                        row: rowLabel,
                        column: adjustedColumn,
                    });
                }
            }
        }

        const layout: Layout = {
            totalRows,
            totalColumns,
            items: items.filter((item) => item.row),
        };

        onSave?.(layout);
    };

    const getCellColor = (cell: GridCell): string => {
        if (cell.type === 'empty') return 'bg-gray-100 hover:bg-gray-200';
        if (cell.type === 'label') return 'bg-gray-200';

        const seat = cell.item;
        if (!seat) return 'bg-gray-100';

        if (seat.status !== 'available') {
            switch (seat.status) {
                case 'booked':
                    return 'bg-red-400 text-white hover:bg-red-500';
                case 'in_transaction':
                    return 'bg-amber-400 text-gray-800 hover:bg-amber-500';
                case 'reserved':
                    return 'bg-gray-400 text-white hover:bg-gray-500';
            }
        }

        switch (seat.category) {
            case 'standard':
                return 'bg-blue-400 text-white hover:bg-blue-500';
            case 'VIP':
                return 'bg-amber-400 text-gray-900 hover:bg-amber-500';
            default:
                return 'bg-gray-300 hover:bg-gray-400';
        }
    };

    const [droppedDown, setDroppedDown] = useState(false);
    const handleToggle = () => {
        setDroppedDown((prev) => !prev);
    };

    return (
        <div className="flex h-screen max-md:flex-col">
            {/* Panel Kontrol - Posisi absolut dengan lebar tetap di atas */}
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
                            {/* back icon */}
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
                            >
                                <path d="M3 9h18v10a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V9Z"></path>
                                <path d="M3 9V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2v4"></path>
                                <path d="M12 12v5"></path>
                            </svg>
                            Grid Seat Editor
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

                {/* Content Scrollable */}
                <div
                    className={`flex-l overflow-y-auto duration-500 md:h-full md:p-5 ${droppedDown ? 'max-md:h-0' : 'h-[35vh] p-5'}`}
                    ref={sidebarContentRef}
                >
                    {/* Dimensi Layout Card */}
                    <div className="mb-6 rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                        <h3 className="mb-4 flex items-center gap-2 font-medium text-gray-700">
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
                                    width="18"
                                    height="18"
                                    x="3"
                                    y="3"
                                    rx="2"
                                    ry="2"
                                ></rect>
                                <line x1="3" x2="21" y1="15" y2="15"></line>
                                <line x1="3" x2="21" y1="9" y2="9"></line>
                                <line x1="9" x2="9" y1="21" y2="3"></line>
                                <line x1="15" x2="15" y1="21" y2="3"></line>
                            </svg>
                            Dimensi Layout
                        </h3>

                        <div className="max-md:grid max-md:grid-cols-2 max-md:gap-2 md:space-y-5">
                            <div className="rounded-lg bg-blue-50 p-3">
                                <label className="mb-2 block text-sm font-medium text-gray-700">
                                    Bottom Rows
                                </label>
                                <div className="flex items-center gap-2">
                                    <Button
                                        variant="outline"
                                        onClick={() =>
                                            setDimensions((d) => ({
                                                ...d,
                                                top: Math.max(0, d.top - 1),
                                            }))
                                        }
                                        className="h-8 w-8 rounded-md border-gray-300 bg-white p-0 shadow-sm transition-colors hover:bg-gray-50"
                                    >
                                        -
                                    </Button>
                                    <span className="flex w-12 items-center justify-center rounded-md bg-white py-1 text-center font-medium shadow-sm">
                                        {dimensions.top}
                                    </span>
                                    <Button
                                        variant="outline"
                                        onClick={() =>
                                            setDimensions((d) => ({
                                                ...d,
                                                top: d.top + 1,
                                            }))
                                        }
                                        className="h-8 w-8 rounded-md border-gray-300 bg-white p-0 shadow-sm transition-colors hover:bg-gray-50"
                                    >
                                        +
                                    </Button>
                                </div>
                            </div>

                            <div className="rounded-lg bg-purple-50 p-3">
                                <label className="mb-2 block text-sm font-medium text-gray-700">
                                    Top Rows
                                </label>
                                <div className="flex items-center gap-2">
                                    <Button
                                        variant="outline"
                                        onClick={() =>
                                            setDimensions((d) => ({
                                                ...d,
                                                bottom: Math.max(
                                                    1,
                                                    d.bottom - 1,
                                                ),
                                            }))
                                        }
                                        className="h-8 w-8 rounded-md border-gray-300 bg-white p-0 shadow-sm transition-colors hover:bg-gray-50"
                                    >
                                        -
                                    </Button>
                                    <span className="flex w-12 items-center justify-center rounded-md bg-white py-1 text-center font-medium shadow-sm">
                                        {dimensions.bottom}
                                    </span>
                                    <Button
                                        variant="outline"
                                        onClick={() =>
                                            setDimensions((d) => ({
                                                ...d,
                                                bottom: d.bottom + 1,
                                            }))
                                        }
                                        className="h-8 w-8 rounded-md border-gray-300 bg-white p-0 shadow-sm transition-colors hover:bg-gray-50"
                                    >
                                        +
                                    </Button>
                                </div>
                            </div>

                            <div className="rounded-lg bg-green-50 p-3">
                                <label className="mb-2 block text-sm font-medium text-gray-700">
                                    Left Columns
                                </label>
                                <div className="flex items-center gap-2">
                                    <Button
                                        variant="outline"
                                        onClick={() =>
                                            setDimensions((d) => ({
                                                ...d,
                                                left: Math.max(0, d.left - 1),
                                            }))
                                        }
                                        className="h-8 w-8 rounded-md border-gray-300 bg-white p-0 shadow-sm transition-colors hover:bg-gray-50"
                                    >
                                        -
                                    </Button>
                                    <span className="flex w-12 items-center justify-center rounded-md bg-white py-1 text-center font-medium shadow-sm">
                                        {dimensions.left}
                                    </span>
                                    <Button
                                        variant="outline"
                                        onClick={() =>
                                            setDimensions((d) => ({
                                                ...d,
                                                left: d.left + 1,
                                            }))
                                        }
                                        className="h-8 w-8 rounded-md border-gray-300 bg-white p-0 shadow-sm transition-colors hover:bg-gray-50"
                                    >
                                        +
                                    </Button>
                                </div>
                            </div>

                            <div className="rounded-lg bg-amber-50 p-3">
                                <label className="mb-2 block text-sm font-medium text-gray-700">
                                    Right Columns
                                </label>
                                <div className="flex items-center gap-2">
                                    <Button
                                        variant="outline"
                                        onClick={() =>
                                            setDimensions((d) => ({
                                                ...d,
                                                right: Math.max(1, d.right - 1),
                                            }))
                                        }
                                        className="h-8 w-8 rounded-md border-gray-300 bg-white p-0 shadow-sm transition-colors hover:bg-gray-50"
                                    >
                                        -
                                    </Button>
                                    <span className="flex w-12 items-center justify-center rounded-md bg-white py-1 text-center font-medium shadow-sm">
                                        {dimensions.right}
                                    </span>
                                    <Button
                                        variant="outline"
                                        onClick={() =>
                                            setDimensions((d) => ({
                                                ...d,
                                                right: d.right + 1,
                                            }))
                                        }
                                        className="h-8 w-8 rounded-md border-gray-300 bg-white p-0 shadow-sm transition-colors hover:bg-gray-50"
                                    >
                                        +
                                    </Button>
                                </div>
                            </div>
                        </div>
                    </div>

                    {/* Card Mode Editor */}
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
                                Mode Editor
                            </h3>
                        </div>

                        <div className="p-3">
                            <div className="mb-3 grid grid-cols-1 gap-2">
                                <Button
                                    variant="outline"
                                    onClick={() => handleModeChange('add')}
                                    className={`flex items-center justify-start gap-2 py-2 pl-3 pr-2 text-left transition-all ${
                                        mode === 'add'
                                            ? 'border-blue-500 bg-blue-50 text-blue-700 hover:bg-blue-100'
                                            : 'border-gray-200 bg-white hover:bg-gray-50'
                                    }`}
                                >
                                    <div
                                        className={`flex h-7 w-7 items-center justify-center rounded-full ${mode === 'add' ? 'bg-blue-600' : 'bg-gray-100'}`}
                                    >
                                        <MousePointer
                                            size={14}
                                            className={
                                                mode === 'add'
                                                    ? 'text-white'
                                                    : 'text-gray-500'
                                            }
                                        />
                                    </div>
                                    <span>Add Seats</span>
                                </Button>

                                <Button
                                    variant="outline"
                                    onClick={() => handleModeChange('delete')}
                                    className={`flex items-center justify-start gap-2 py-2 pl-3 pr-2 text-left transition-all ${
                                        mode === 'delete'
                                            ? 'border-red-500 bg-red-50 text-red-700 hover:bg-red-100'
                                            : 'border-gray-200 bg-white hover:bg-gray-50'
                                    }`}
                                >
                                    <div
                                        className={`flex h-7 w-7 items-center justify-center rounded-full ${mode === 'delete' ? 'bg-red-600' : 'bg-gray-100'}`}
                                    >
                                        <Trash2
                                            size={14}
                                            className={
                                                mode === 'delete'
                                                    ? 'text-white'
                                                    : 'text-gray-500'
                                            }
                                        />
                                    </div>
                                    <span>Delete Seats</span>
                                </Button>

                                <Button
                                    variant="outline"
                                    onClick={() => handleModeChange('block')}
                                    className={`flex items-center justify-start gap-2 py-2 pl-3 pr-2 text-left transition-all ${
                                        mode === 'block'
                                            ? 'border-purple-500 bg-purple-50 text-purple-700 hover:bg-purple-100'
                                            : 'border-gray-200 bg-white hover:bg-gray-50'
                                    }`}
                                >
                                    <div
                                        className={`flex h-7 w-7 items-center justify-center rounded-full ${mode === 'block' ? 'bg-purple-600' : 'bg-gray-100'}`}
                                    >
                                        <Square
                                            size={14}
                                            className={
                                                mode === 'block'
                                                    ? 'text-white'
                                                    : 'text-gray-500'
                                            }
                                        />
                                    </div>
                                    <span>Block Area</span>
                                </Button>
                            </div>

                            <div className="rounded-lg border border-gray-200 bg-gray-50 p-3 text-sm text-gray-700">
                                {mode === 'add' && (
                                    <div className="flex items-start gap-2">
                                        <MousePointer
                                            size={14}
                                            className="mt-0.5 shrink-0 text-blue-500"
                                        />
                                        <div>
                                            <span className="font-medium">
                                                Click on empty cells
                                            </span>{' '}
                                            to add new seats
                                        </div>
                                    </div>
                                )}
                                {mode === 'delete' && (
                                    <div className="flex items-start gap-2">
                                        <Trash2
                                            size={14}
                                            className="mt-0.5 shrink-0 text-red-500"
                                        />
                                        <div>
                                            <span className="font-medium">
                                                Click on seats
                                            </span>{' '}
                                            to remove them from the layout
                                        </div>
                                    </div>
                                )}
                                {mode === 'block' && (
                                    <div className="flex items-start gap-2">
                                        <Square
                                            size={14}
                                            className="mt-0.5 shrink-0 text-purple-500"
                                        />
                                        <div>
                                            <span className="font-medium">
                                                Click and drag
                                            </span>{' '}
                                            to select and block/unblock multiple
                                            cells at once
                                        </div>
                                    </div>
                                )}
                            </div>
                        </div>
                    </div>

                    {/* Block Area Actions - Conditional */}
                    {mode === 'block' && blockedArea && (
                        <div
                            className="mb-6 overflow-hidden rounded-xl border border-blue-300 bg-gradient-to-r from-blue-50 to-indigo-50 shadow-sm"
                            ref={blockActionsRef}
                        >
                            <div className="border-b border-blue-200 bg-blue-100/50 p-3">
                                <div className="flex items-center gap-2 font-medium text-blue-800">
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
                                    Area Selected
                                </div>
                            </div>
                            <div className="p-3">
                                <div className="grid grid-cols-1 gap-2">
                                    <Button
                                        variant="outline"
                                        onClick={addSeatsToBlockedArea}
                                        className="flex items-center justify-center gap-2 border-green-500 bg-white py-2 text-green-600 shadow-sm transition-colors hover:bg-green-50"
                                    >
                                        <Plus size={16} />
                                        <span>Add Seats</span>
                                    </Button>
                                    <Button
                                        variant="outline"
                                        onClick={deleteSeatsFromBlockedArea}
                                        className="flex items-center justify-center gap-2 border-red-500 bg-white py-2 text-red-600 shadow-sm transition-colors hover:bg-red-50"
                                    >
                                        <Trash2 size={16} />
                                        <span>Delete Seats</span>
                                    </Button>
                                    <Button
                                        variant="outline"
                                        onClick={() => setBlockedArea(null)}
                                        className="border-gray-300 bg-white py-2 shadow-sm transition-colors hover:bg-gray-50"
                                    >
                                        Cancel
                                    </Button>
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
                        onClick={handleSave}
                        className="flex w-full items-center justify-center gap-2 rounded-lg bg-gradient-to-r from-blue-600 to-blue-700 px-4 py-3 font-medium text-white shadow-sm transition-all hover:from-blue-700 hover:to-blue-800 hover:shadow-md disabled:cursor-not-allowed disabled:opacity-50"
                        disabled={isDisabled}
                    >
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
                            <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path>
                            <polyline points="17 21 17 13 7 13 7 21"></polyline>
                            <polyline points="7 3 7 8 15 8"></polyline>
                        </svg>
                        {isDisabled ? 'Saving...' : 'Save Layout'}
                    </button>
                </div>
            </div>

            {/* Main content area */}
            <div className="h-screen flex-1 overflow-auto bg-gray-50 max-md:order-1">
                {/* Use a flex container to properly center and expand the content */}
                <div className="flex h-full items-center justify-center">
                    <div className="h-full w-full p-4">
                        {/* The key container with dotted border that should expand */}
                        <div
                            className="relative h-full w-full rounded-3xl border-2 border-dashed border-gray-300 bg-white p-4"
                            style={{ minHeight: '80vh' }}
                            onMouseUp={handleMouseUp}
                            onMouseLeave={() => {
                                if (isMouseDown) {
                                    handleMouseUp();
                                }
                            }}
                        >
                            {/* Remove the overflow-auto from this container and put it on an inner element */}
                            <div className="h-full w-full overflow-auto rounded-lg">
                                <div className="min-w-max p-2">
                                    <div className="grid grid-flow-row gap-1">
                                        {[...grid]
                                            .reverse()
                                            .map((row, reversedIndex) => (
                                                <div
                                                    key={reversedIndex}
                                                    className="flex gap-1"
                                                >
                                                    {row.map(
                                                        (cell, colIndex) => {
                                                            const actualRowIndex =
                                                                grid.length -
                                                                1 -
                                                                reversedIndex;

                                                            return (
                                                                <div
                                                                    key={
                                                                        colIndex
                                                                    }
                                                                    onClick={() =>
                                                                        handleCellClick(
                                                                            actualRowIndex,
                                                                            colIndex,
                                                                        )
                                                                    }
                                                                    onMouseDown={() =>
                                                                        handleMouseDown(
                                                                            actualRowIndex,
                                                                            colIndex,
                                                                        )
                                                                    }
                                                                    onMouseOver={() =>
                                                                        handleMouseOver(
                                                                            actualRowIndex,
                                                                            colIndex,
                                                                        )
                                                                    }
                                                                    className={`flex h-8 w-8 cursor-pointer select-none items-center justify-center rounded text-xs font-medium ${getCellColor(cell)} ${
                                                                        cell.isBlocked
                                                                            ? 'border-2 border-gray-400'
                                                                            : 'border border-gray-200'
                                                                    } ${
                                                                        (isDragging &&
                                                                            mode ===
                                                                                'block' &&
                                                                            startCell &&
                                                                            endCell &&
                                                                            actualRowIndex >=
                                                                                Math.min(
                                                                                    startCell.row,
                                                                                    endCell.row,
                                                                                ) &&
                                                                            actualRowIndex <=
                                                                                Math.max(
                                                                                    startCell.row,
                                                                                    endCell.row,
                                                                                ) &&
                                                                            colIndex >=
                                                                                Math.min(
                                                                                    startCell.col,
                                                                                    endCell.col,
                                                                                ) &&
                                                                            colIndex <=
                                                                                Math.max(
                                                                                    startCell.col,
                                                                                    endCell.col,
                                                                                )) ||
                                                                        (mode ===
                                                                            'block' &&
                                                                            isInBlockedArea(
                                                                                actualRowIndex,
                                                                                colIndex,
                                                                            ))
                                                                            ? 'ring-2 ring-blue-500'
                                                                            : ''
                                                                    }`}
                                                                    draggable={
                                                                        false
                                                                    }
                                                                >
                                                                    {cell.type ===
                                                                        'seat' &&
                                                                        cell
                                                                            .item
                                                                            ?.seat_number}
                                                                </div>
                                                            );
                                                        },
                                                    )}
                                                </div>
                                            ))}
                                    </div>

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
                                                />
                                            </svg>
                                            Stage
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    );
    {
        /* Panel Footer dengan tombol Save - Posisi absolut/fixed di bawah
            <div
                className="z-20 w-full border-t border-gray-200 bg-white"
                style={{ position: 'fixed', bottom: 0, left: 0, right: 0 }}
            >
                <div className="mx-auto max-w-7xl px-6 py-3">
                    <button
                        onClick={handleSave}
                        className="flex w-full items-center justify-center gap-2 rounded-md bg-blue-600 px-4 py-2 font-medium text-white transition-colors hover:bg-blue-700 disabled:cursor-not-allowed disabled:opacity-50"
                        disabled={isDisabled}
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
                            <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path>
                            <polyline points="17 21 17 13 7 13 7 21"></polyline>
                            <polyline points="7 3 7 8 15 8"></polyline>
                        </svg>
                        {isDisabled ? 'Saving...' : 'Save Layout'}
                    </button>
                </div>
            </div> */
    }
    //{' '}
};

export default GridSeatEditor;
