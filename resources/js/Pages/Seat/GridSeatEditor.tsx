import {
    AlertDialog,
    AlertDialogAction,
    AlertDialogCancel,
    AlertDialogContent,
    AlertDialogDescription,
    AlertDialogFooter,
    AlertDialogHeader,
    AlertDialogTitle,
} from '@/Components/ui/alert-dialog';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import React, { useCallback, useEffect, useState } from 'react';
import { Category, Layout, LayoutItem, SeatItem, SeatStatus } from './types';

interface Props {
    initialLayout?: Layout;
    onSave?: (layout: Layout) => void;
    venueId: string;
}

interface GridCell {
    type: 'empty' | 'seat' | 'label';
    item?: SeatItem;
}

interface GridDimensions {
    top: number;
    bottom: number;
    left: number;
    right: number;
}

// Helper function to convert number to Excel-style column label
// const getRowLabel = (num: number): string => {
//     let dividend = num;
//     let columnName = '';
//     let modulo;

//     while (dividend > 0) {
//         modulo = (dividend - 1) % 26;
//         columnName = String.fromCharCode(65 + modulo) + columnName;
//         dividend = Math.floor((dividend - 1) / 26);
//     }

//     return columnName;
// };

// Helper function to convert Excel-style column label to number
const getRowNumber = (label: string): number => {
    let result = 0;
    for (let i = 0; i < label.length; i++) {
        result *= 26;
        result += label.charCodeAt(i) - 64; // 'A' is 65 in ASCII
    }
    return result;
};

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
    const [selectedCell, setSelectedCell] = useState<{
        row: number;
        col: number;
    } | null>(null);
    const [showSeatDialog, setShowSeatDialog] = useState(false);
    const [showDeleteDialog, setShowDeleteDialog] = useState(false);
    const [editMode, setEditMode] = useState<'add' | 'edit' | null>(null);
    const [selectedCategory, setSelectedCategory] =
        useState<Category>('silver');
    const [selectedStatus, setSelectedStatus] =
        useState<SeatStatus>('available');

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

    //
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
        setSelectedCell({ row: rowIndex, col: colIndex });
        const cell = grid[rowIndex][colIndex];

        if (cell.type === 'empty') {
            setEditMode('add');
            setShowSeatDialog(true);
            setSelectedStatus('available');
            setSelectedCategory('silver');
        } else if (cell.type === 'seat' && cell.item) {
            setEditMode('edit');
            setShowSeatDialog(true);
            setSelectedCategory(cell.item.category);
            setSelectedStatus(cell.item.status);
        }
    };

    // Fungsi untuk mendapatkan row label yang benar berdasarkan posisi dari bawah
    const getAdjustedRowLabel = (index: number, totalRows: number): string => {
        // index adalah posisi dari atas, kita perlu mengonversinya ke posisi dari bawah
        const rowFromBottom = index + 1; // Mulai dari 1 untuk baris paling bawah

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

    const handleAddSeat = () => {
        if (!selectedCell) return;

        const newGrid = [...grid];
        const rowLabel = getAdjustedRowLabel(selectedCell.row, totalRows);

        // Perbaikan perhitungan kolom
        const adjustedColumn = selectedCell.col + 1; // Tambah 1 karena indeks dimulai dari 0

        const newSeat: SeatItem = {
            type: 'seat',
            seat_id: `${rowLabel}${adjustedColumn}`,
            seat_number: `${rowLabel}${adjustedColumn}`,
            row: rowLabel,
            column: adjustedColumn,
            status: selectedStatus,
            category: selectedCategory,
            price: 0,
            seat_type: 'regular',
        };

        newGrid[selectedCell.row][selectedCell.col] = {
            type: 'seat',
            item: newSeat,
        };

        setGrid(newGrid);
        setShowSeatDialog(false);
        reorderSeatNumbers();
    };

    const handleDeleteSeat = () => {
        if (!selectedCell) return;

        const newGrid = [...grid];
        newGrid[selectedCell.row][selectedCell.col] = { type: 'empty' };

        setGrid(newGrid);
        setShowDeleteDialog(false);
        reorderSeatNumbers();
    };

    const reorderSeatNumbers = () => {
        const newGrid = [...grid];
        const seatCounters: { [key: string]: number } = {};

        // Proses dari bawah ke atas
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
                    const adjustedColumn = colIndex + 1; // Perbaikan perhitungan kolom
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

        const seat = cell.item;
        if (!seat) return 'bg-gray-100';

        if (seat.status !== 'available') {
            switch (seat.status) {
                case 'booked':
                    return 'bg-red-500';
                case 'in_transaction':
                    return 'bg-yellow-500';
                case 'not_available':
                    return 'bg-gray-400';
            }
        }

        switch (seat.category) {
            case 'diamond':
                return 'bg-cyan-400';
            case 'gold':
                return 'bg-yellow-400';
            case 'silver':
                return 'bg-gray-300';
            default:
                return 'bg-gray-200';
        }
    };

    const DimensionControl = () => (
        <div className="mb-6 space-y-4">
            <div className="flex items-center gap-4">
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
                        Bottom Rows
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

            <div className="mb-6 flex flex-col items-center">
                <div className="grid w-full gap-1">
                    {[...grid].reverse().map((row, reversedIndex) => {
                        // const originalIndex = grid.length - 1 - reversedIndex;
                        // const adjustedRowIndex = originalIndex - dimensions.top;
                        // const rowLabel = getAdjustedRowLabel(
                        //     reversedIndex,
                        //     totalRows,
                        // );
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
                                        className={`flex h-8 w-8 items-center justify-center rounded border ${getCellColor(cell)} cursor-pointer text-xs hover:opacity-80`}
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

            {/* Add/Edit Seat Dialog */}
            <Dialog open={showSeatDialog} onOpenChange={setShowSeatDialog}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>
                            {editMode === 'add' ? 'Add New Seat' : 'Edit Seat'}
                        </DialogTitle>
                    </DialogHeader>
                    <div className="space-y-4 py-4">
                        <div>
                            <label className="mb-2 block text-sm font-medium">
                                Category
                            </label>
                            <Select
                                value={selectedCategory}
                                onValueChange={(value: Category) =>
                                    setSelectedCategory(value)
                                }
                            >
                                <SelectTrigger>
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="diamond">
                                        Diamond
                                    </SelectItem>
                                    <SelectItem value="gold">Gold</SelectItem>
                                    <SelectItem value="silver">
                                        Silver
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                        </div>

                        <div>
                            <label className="mb-2 block text-sm font-medium">
                                Status
                            </label>
                            <Select
                                value={selectedStatus}
                                onValueChange={(value: SeatStatus) =>
                                    setSelectedStatus(value)
                                }
                            >
                                <SelectTrigger>
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="available">
                                        Available
                                    </SelectItem>
                                    <SelectItem value="booked">
                                        Booked
                                    </SelectItem>
                                    <SelectItem value="in_transaction">
                                        In Transaction
                                    </SelectItem>
                                    <SelectItem value="not_available">
                                        Not Available
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                        </div>
                    </div>
                    <DialogFooter>
                        <Button
                            variant="outline"
                            onClick={() => setShowSeatDialog(false)}
                        >
                            Cancel
                        </Button>
                        <Button onClick={handleAddSeat}>
                            {editMode === 'add' ? 'Add Seat' : 'Update Seat'}
                        </Button>
                        {editMode === 'edit' && (
                            <Button
                                variant="destructive"
                                onClick={() => {
                                    setShowSeatDialog(false);
                                    setShowDeleteDialog(true);
                                }}
                            >
                                Delete Seat
                            </Button>
                        )}
                    </DialogFooter>
                </DialogContent>
            </Dialog>

            {/* Delete Confirmation Dialog */}
            <AlertDialog
                open={showDeleteDialog}
                onOpenChange={setShowDeleteDialog}
            >
                <AlertDialogContent>
                    <AlertDialogHeader>
                        <AlertDialogTitle>Delete Seat</AlertDialogTitle>
                        <AlertDialogDescription>
                            Are you sure you want to delete this seat? This
                            action cannot be undone.
                        </AlertDialogDescription>
                    </AlertDialogHeader>
                    <AlertDialogFooter>
                        <AlertDialogCancel>Cancel</AlertDialogCancel>
                        <AlertDialogAction onClick={handleDeleteSeat}>
                            Delete
                        </AlertDialogAction>
                    </AlertDialogFooter>
                </AlertDialogContent>
            </AlertDialog>
        </div>
    );
};

export default GridSeatEditor;
