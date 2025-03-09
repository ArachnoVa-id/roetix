import { Button } from '@/components/ui/button';
import { MousePointer, Square, Trash2 } from 'lucide-react';
import React, { useCallback, useEffect, useState } from 'react';
import { Category, Layout, LayoutItem, SeatItem, SeatStatus } from './types';

interface Props {
    initialLayout?: Layout;
    onSave?: (layout: Layout) => void;
    venueId: string;
}

interface GridCell {
    type: 'empty' | 'seat' | 'label' | 'blocked';
    item?: SeatItem;
}

interface GridDimensions {
    top: number;
    bottom: number;
    left: number;
    right: number;
}

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

const GridSeatEditor: React.FC<Props> = ({
    initialLayout,
    onSave,
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

    // Default values for new seats
    const defaultCategory: Category = 'standard';
    const defaultStatus: SeatStatus = 'available';

    const totalRows = dimensions.top + dimensions.bottom;
    const totalColumns = dimensions.left + dimensions.right;

    // Function to find highest row and adjust dimensions
    const findHighestRow = (items: LayoutItem[]): number => {
        let maxRow = 0;
        items.forEach((item) => {
            if (item.type === 'seat') {
                const rowNum =
                    typeof item.row === 'string'
                        ? getRowNumber(item.row) - 1
                        : item.row;
                maxRow = Math.max(maxRow, rowNum);
            }
        });
        return maxRow;
    };

    // Function to find highest column and adjust dimensions
    const findHighestColumn = (items: LayoutItem[]): number => {
        let maxCol = 0;
        items.forEach((item) => {
            if (item.type === 'seat') {
                maxCol = Math.max(maxCol, item.column);
            }
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
            initialLayout.items.forEach((item) => {
                if (item.type === 'seat') {
                    const seatItem = item as SeatItem;
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
                }
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

    const handleCellClick = (rowIndex: number, colIndex: number) => {
        const cell = grid[rowIndex][colIndex];

        if (mode === 'add') {
            if (cell.type === 'empty') {
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

        for (let i = minRow; i <= maxRow; i++) {
            for (let j = minCol; j <= maxCol; j++) {
                // Toggle between blocked and the current type
                if (newGrid[i][j].type !== 'blocked') {
                    newGrid[i][j] = { type: 'blocked' };
                } else if (newGrid[i][j].type === 'blocked') {
                    newGrid[i][j] = { type: 'empty' };
                }
            }
        }

        setGrid(newGrid);
        setIsMouseDown(false);
        setStartCell(null);
        setEndCell(null);
    };

    // Function to toggle a single cell's blocked status
    const toggleBlockedCell = (rowIndex: number, colIndex: number) => {
        const newGrid = [...grid];

        if (newGrid[rowIndex][colIndex].type !== 'blocked') {
            // Save current state (regardless if it's empty or seat)
            newGrid[rowIndex][colIndex] = { type: 'blocked' };
        } else if (newGrid[rowIndex][colIndex].type === 'blocked') {
            newGrid[rowIndex][colIndex] = { type: 'empty' };
        }

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
            type: 'seat',
            seat_id: `${rowLabel}${adjustedColumn}`,
            seat_number: `${rowLabel}${adjustedColumn}`,
            row: rowLabel,
            column: adjustedColumn,
            status: defaultStatus,
            category: defaultCategory,
            price: 0,
        };

        newGrid[rowIndex][colIndex] = {
            type: 'seat',
            item: newSeat,
        };

        setGrid(newGrid);
        reorderSeatNumbers();
    };

    const deleteSeat = (rowIndex: number, colIndex: number) => {
        const newGrid = [...grid];
        newGrid[rowIndex][colIndex] = { type: 'empty' };

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
                    cell.item.seat_id = `${rowLabel}${seatCounters[rowLabel]}`;
                    seatCounters[rowLabel]++;
                }
            }
        }

        setGrid(newGrid);
    };

    const handleSave = () => {
        const items: SeatItem[] = [];

        for (let i = totalRows - 1; i >= 0; i--) {
            const rowLabel = getAdjustedRowLabel(i, totalRows);

            grid[i].forEach((cell, colIndex) => {
                if (cell.type === 'seat' && cell.item) {
                    const adjustedColumn = colIndex + 1;
                    items.push({
                        ...cell.item,
                        row: rowLabel,
                        column: adjustedColumn,
                    });
                }
            });
        }

        const layout: Layout = {
            totalRows,
            totalColumns,
            items: items.filter((item) => item.row),
        };

        onSave?.(layout);
    };

    const getCellColor = (cell: GridCell): string => {
        if (cell.type === 'empty') return 'bg-gray-100';
        if (cell.type === 'label') return 'bg-gray-200';
        if (cell.type === 'blocked') return 'bg-gray-600';

        const seat = cell.item;
        if (!seat) return 'bg-gray-100';

        if (seat.status !== 'available') {
            switch (seat.status) {
                case 'booked':
                    return 'bg-red-500';
                case 'in_transaction':
                    return 'bg-yellow-500';
                case 'reserved':
                    return 'bg-gray-400';
            }
        }

        switch (seat.category) {
            case 'standard':
                return 'bg-cyan-400';
            case 'VIP':
                return 'bg-yellow-400';
            default:
                return 'bg-gray-200';
        }
    };

    const getModeButtonVariant = (buttonMode: EditorMode) => {
        return mode === buttonMode ? 'default' : 'outline';
    };

    const DimensionControl = () => (
        <div className="mb-6 space-y-4">
            <div className="flex items-center gap-4">
                <div>
                    <label className="block text-sm font-medium text-gray-700">
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
                        >
                            -
                        </Button>
                        <span className="w-8 text-center">
                            {dimensions.top}
                        </span>
                        <Button
                            variant="outline"
                            onClick={() =>
                                setDimensions((d) => ({ ...d, top: d.top + 1 }))
                            }
                        >
                            +
                        </Button>
                    </div>
                </div>

                <div>
                    <label className="block text-sm font-medium text-gray-700">
                        Top Rows
                    </label>
                    <div className="flex items-center gap-2">
                        <Button
                            variant="outline"
                            onClick={() =>
                                setDimensions((d) => ({
                                    ...d,
                                    bottom: Math.max(1, d.bottom - 1),
                                }))
                            }
                        >
                            -
                        </Button>
                        <span className="w-8 text-center">
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
                        >
                            +
                        </Button>
                    </div>
                </div>
            </div>

            <div className="flex items-center gap-4">
                <div>
                    <label className="block text-sm font-medium text-gray-700">
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
                        >
                            -
                        </Button>
                        <span className="w-8 text-center">
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
                        >
                            +
                        </Button>
                    </div>
                </div>

                <div>
                    <label className="block text-sm font-medium text-gray-700">
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
                        >
                            -
                        </Button>
                        <span className="w-8 text-center">
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
                        >
                            +
                        </Button>
                    </div>
                </div>
            </div>
        </div>
    );

    return (
        <div className="p-6">
            <DimensionControl />

            <div className="mb-4 flex gap-2">
                <Button
                    variant={getModeButtonVariant('add')}
                    onClick={() => setMode('add')}
                    className="flex items-center gap-2"
                >
                    <MousePointer size={16} />
                    Add Seats
                </Button>
                <Button
                    variant={getModeButtonVariant('delete')}
                    onClick={() => setMode('delete')}
                    className="flex items-center gap-2"
                >
                    <Trash2 size={16} />
                    Delete Seats
                </Button>
                <Button
                    variant={getModeButtonVariant('block')}
                    onClick={() => setMode('block')}
                    className="flex items-center gap-2"
                >
                    <Square size={16} />
                    Block Area
                </Button>
            </div>

            <div className="mb-2 text-sm">
                {mode === 'add' && <p>Click on empty cells to add seats</p>}
                {mode === 'delete' && <p>Click on seats to delete them</p>}
                {mode === 'block' && (
                    <p>
                        Click and drag to block/unblock multiple cells (works on
                        seats and empty areas)
                    </p>
                )}
            </div>

            <div className="mb-6 flex flex-col items-center">
                <div className="grid w-full gap-1">
                    {[...grid].reverse().map((row, reversedIndex) => {
                        return (
                            <div key={reversedIndex} className="flex gap-1">
                                {row.map((cell, colIndex) => (
                                    <div
                                        key={colIndex}
                                        onClick={() =>
                                            handleCellClick(
                                                grid.length - 1 - reversedIndex,
                                                colIndex,
                                            )
                                        }
                                        onMouseDown={() =>
                                            handleMouseDown(
                                                grid.length - 1 - reversedIndex,
                                                colIndex,
                                            )
                                        }
                                        onMouseOver={() =>
                                            handleMouseOver(
                                                grid.length - 1 - reversedIndex,
                                                colIndex,
                                            )
                                        }
                                        onMouseUp={handleMouseUp}
                                        className={`flex h-8 w-8 select-none items-center justify-center rounded border ${getCellColor(cell)} cursor-pointer text-xs hover:opacity-80`}
                                    >
                                        {cell.type === 'seat' &&
                                            cell.item?.seat_number}
                                    </div>
                                ))}
                            </div>
                        );
                    })}
                </div>

                <div className="mt-10 flex h-10 w-[50VW] items-center justify-center self-center rounded border border-gray-200 bg-white text-sm">
                    Panggung
                </div>
            </div>

            <Button onClick={handleSave} className="mt-4">
                Save Layout
            </Button>
        </div>
    );
};

export default GridSeatEditor;
