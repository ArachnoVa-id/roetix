import React, { useCallback, useEffect, useRef, useState } from 'react';

// Import shared components and utils
import { EditorLayout } from './shared/components/Layout';
// import { NotificationToast } from './shared/components/NotificationToast';
import { SaveButton } from './shared/components/SaveButton';
import { BlockActions } from './shared/components/seat/BlockActions';
import { DimensionsPanel } from './shared/components/seat/DimensionPanel';
import { Grid } from './shared/components/seat/Grid';
import {
    ModeSelection,
    ModeType,
} from './shared/components/seat/ModeSelection';
import { SeatCell, SeatItem } from './shared/components/seat/SeatCell';
import {
    findHighestColumn,
    findHighestRow,
    getAdjustedRowLabel,
    getRowNumber,
    GridDimensions,
} from './shared/utils/gridHelpers';

// Types
interface GridCell {
    type: 'empty' | 'seat' | 'label';
    item?: SeatItem;
    isBlocked?: boolean;
}

interface BlockedArea {
    minRow: number;
    maxRow: number;
    minCol: number;
    maxCol: number;
}

interface StartEndCell {
    row: number;
    col: number;
}

interface AutoScrollDirection {
    horizontal: number;
    vertical: number;
}

export interface Layout {
    totalRows: number;
    totalColumns: number;
    items: SeatItem[];
}

interface GridSeatEditorProps {
    initialLayout: Layout;
    onSave: (layout: Layout) => Promise<void>;
    isDisabled?: boolean;
}

const GridSeatEditor: React.FC<GridSeatEditorProps> = ({
    initialLayout,
    onSave,
    isDisabled = false,
}) => {
    const [dimensions, setDimensions] = useState<GridDimensions>({
        top: 0,
        bottom: 10,
        left: 0,
        right: 15,
    });

    // const [showSaveSuccess, setShowSaveSuccess] = useState<boolean>(false);
    const [hasChanges, setHasChanges] = useState<boolean>(false);
    const [grid, setGrid] = useState<GridCell[][]>([]);
    const [mode, setMode] = useState<ModeType>('add');
    const [isMouseDown, setIsMouseDown] = useState<boolean>(false);
    const [startCell, setStartCell] = useState<StartEndCell | null>(null);
    const [endCell, setEndCell] = useState<StartEndCell | null>(null);
    const [blockedAreas, setBlockedAreas] = useState<BlockedArea[]>([]);
    const gridContainerRef = useRef<HTMLDivElement>(null);

    // State for improved block mode
    const [isDragging, setIsDragging] = useState<boolean>(false);
    const [, setBlockedArea] = useState<BlockedArea | null>(null);
    const [autoScrollDirection, setAutoScrollDirection] =
        useState<AutoScrollDirection>({
            horizontal: 0, // -1: left, 0: none, 1: right
            vertical: 0, // -1: up, 0: none, 1: down
        });

    // Default values for new seats
    const defaultCategory = 'unset';
    const defaultStatus = 'available';

    const totalRows = dimensions.top + dimensions.bottom;
    const totalColumns = dimensions.left + dimensions.right;
    // const blockActionsRef = useRef<HTMLDivElement>(null);
    const sidebarContentRef = useRef<HTMLDivElement>(null);

    // For mobile dropdown state
    const [droppedDown, setDroppedDown] = useState<boolean>(false);
    const handleToggle = () => {
        setDroppedDown((prev) => !prev);
    };

    // Function to check if a cell is in the most recently blocked/unblocked area
    // const isInBlockedArea = (rowIndex: number, colIndex: number): boolean => {
    //     if (blockedAreas.length === 0) return false;

    //     // Check if the cell is in any of the blocked areas
    //     return blockedAreas.some(
    //         (area) =>
    //             rowIndex >= area.minRow &&
    //             rowIndex <= area.maxRow &&
    //             colIndex >= area.minCol &&
    //             colIndex <= area.maxCol,
    //     );
    // };

    // Handle mode change with cleanup
    const handleModeChange = (mode: ModeType) => {
        // Clear blocked areas when switching out of block mode
        if (mode === 'block') {
            setBlockedAreas([]);
        }
        setMode(mode);
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
                const seatItem = item;
                // Convert row label to index properly
                const rowIndex =
                    typeof seatItem.row === 'string'
                        ? getRowNumber(seatItem.row)
                        : seatItem.row;

                // Adjust for dimensions offset
                const adjustedRowIndex = rowIndex + dimensions.top;
                const colIndex = seatItem.column - 1 + dimensions.left;

                if (
                    adjustedRowIndex >= 0 &&
                    adjustedRowIndex < totalRows &&
                    colIndex >= 0 &&
                    colIndex < totalColumns
                ) {
                    newGrid[adjustedRowIndex][colIndex] = {
                        type: 'seat',
                        item: seatItem,
                    };
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

    useEffect(() => {
        if (
            autoScrollDirection.horizontal === 0 &&
            autoScrollDirection.vertical === 0
        ) {
            return;
        }

        // Create interval for continuous scrolling
        const scrollInterval = setInterval(() => {
            if (!gridContainerRef.current) return;

            const container = gridContainerRef.current;
            const scrollAmount = 15;

            // Apply horizontal scrolling
            if (autoScrollDirection.horizontal !== 0) {
                container.scrollLeft +=
                    autoScrollDirection.horizontal * scrollAmount;
            }

            // Apply vertical scrolling
            if (autoScrollDirection.vertical !== 0) {
                container.scrollTop +=
                    autoScrollDirection.vertical * scrollAmount;
            }
        }, 50); // Adjust timing as needed for smoothness

        return () => clearInterval(scrollInterval);
    }, [autoScrollDirection]);

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

    const handleCellClick = (rowIndex: number, colIndex: number): void => {
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

    const handleMouseDown = (rowIndex: number, colIndex: number): void => {
        if (mode !== 'block') return;

        // Clear previous blocked areas when starting a new selection
        setBlockedAreas([]);

        // Clear the blockedArea state when starting a new selection
        setBlockedArea(null);

        setIsMouseDown(true);
        setIsDragging(true);
        setStartCell({ row: rowIndex, col: colIndex });
        setEndCell({ row: rowIndex, col: colIndex });
    };

    const handleMouseOver = (rowIndex: number, colIndex: number): void => {
        if (!isMouseDown || mode !== 'block') return;

        // Update endCell for selection
        setEndCell({ row: rowIndex, col: colIndex });

        // Auto-scroll logic
        if (gridContainerRef.current) {
            const container = gridContainerRef.current;
            const containerRect = container.getBoundingClientRect();

            // Create a temporary element to use for position detection
            const tempElement = document.elementFromPoint(
                containerRect.left + containerRect.width / 2,
                containerRect.top + containerRect.height / 2,
            );

            // If we can find the element, use its position for calculations
            if (tempElement) {
                const cellRect = tempElement.getBoundingClientRect();

                // Threshold values for when to start scrolling
                const threshold = 60;

                // Calculate scroll directions
                let horizontalDirection = 0;
                let verticalDirection = 0;

                // Check right and left edges
                if (cellRect.right > containerRect.right - threshold) {
                    horizontalDirection = 1; // scroll right
                } else if (cellRect.left < containerRect.left + threshold) {
                    horizontalDirection = -1; // scroll left
                }

                // Check bottom and top edges
                if (cellRect.bottom > containerRect.bottom - threshold) {
                    verticalDirection = 1; // scroll down
                } else if (cellRect.top < containerRect.top + threshold) {
                    verticalDirection = -1; // scroll up
                }

                // Update auto-scroll direction
                setAutoScrollDirection({
                    horizontal: horizontalDirection,
                    vertical: verticalDirection,
                });
            }
        }
    };

    const handleMouseUp = (): void => {
        if (!isMouseDown || mode !== 'block' || !startCell || !endCell) return;

        setAutoScrollDirection({ horizontal: 0, vertical: 0 });

        // Process blocked area
        const minRow = Math.min(startCell.row, endCell.row);
        const maxRow = Math.max(startCell.row, endCell.row);
        const minCol = Math.min(startCell.col, endCell.col);
        const maxCol = Math.max(startCell.col, endCell.col);

        const newGrid = [...grid];

        // Determine if we're blocking or unblocking based on the first cell
        const firstCell = newGrid[startCell.row][startCell.col];
        const isBlocking = !firstCell.isBlocked;

        // Flag to determine if any change occurred
        let changesMade = false;

        // First, clear all previously blocked cells
        for (let i = 0; i < totalRows; i++) {
            for (let j = 0; j < totalColumns; j++) {
                if (newGrid[i][j].isBlocked) {
                    newGrid[i][j] = {
                        ...newGrid[i][j],
                        isBlocked: false,
                    };
                    changesMade = true;
                }
            }
        }

        // Now set the new blocked area
        for (let i = minRow; i <= maxRow; i++) {
            for (let j = minCol; j <= maxCol; j++) {
                // Always set to the current blocking state
                newGrid[i][j] = {
                    ...newGrid[i][j],
                    isBlocked: isBlocking,
                };
                changesMade = true;
            }
        }

        // Only update hasChanges if actual changes were made
        if (changesMade) {
            setHasChanges(true);
        }

        // Replace all blocked areas with only the new one
        const newBlockedArea = { minRow, maxRow, minCol, maxCol };
        // Replace instead of adding
        setBlockedAreas(isBlocking ? [newBlockedArea] : []);

        setGrid(newGrid);
        setIsMouseDown(false);
        setIsDragging(false);
        setStartCell(null);
        setEndCell(null);

        // Add an ID to the BlockActions component for scroll targeting
        setTimeout(() => {
            const blockActionsElement = document.getElementById(
                'block-actions-container',
            );
            if (blockActionsElement && sidebarContentRef.current) {
                blockActionsElement.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start',
                });
            }
        }, 100);
    };

    const handleMouseLeave = (): void => {
        if (isMouseDown) {
            handleMouseUp();
        }

        // Always stop auto-scrolling when mouse leaves
        setAutoScrollDirection({ horizontal: 0, vertical: 0 });
    };

    const handleMouseMove = (e: React.MouseEvent<HTMLDivElement>): void => {
        if (!isMouseDown || mode !== 'block' || !gridContainerRef.current)
            return;

        const container = gridContainerRef.current;
        const containerRect = container.getBoundingClientRect();

        // Mouse position relative to viewport
        const mouseX = e.clientX;
        const mouseY = e.clientY;

        // Calculate distances to container edges
        const distanceToRight = containerRect.right - mouseX;
        const distanceToLeft = mouseX - containerRect.left;
        const distanceToBottom = containerRect.bottom - mouseY;
        const distanceToTop = mouseY - containerRect.top;

        // Threshold for when to start scrolling
        const threshold = 60;

        // Determine scroll directions based on mouse position
        let horizontalDirection = 0;
        let verticalDirection = 0;

        // Check horizontal scrolling
        if (distanceToRight < threshold) {
            horizontalDirection = 1; // Scroll right
        } else if (distanceToLeft < threshold) {
            horizontalDirection = -1; // Scroll left
        }

        // Check vertical scrolling
        if (distanceToBottom < threshold) {
            verticalDirection = 1; // Scroll down
        } else if (distanceToTop < threshold) {
            verticalDirection = -1; // Scroll up
        }

        // Update auto-scroll direction
        setAutoScrollDirection({
            horizontal: horizontalDirection,
            vertical: verticalDirection,
        });
    };

    // Function to toggle a single cell's blocked status
    const toggleBlockedCell = (rowIndex: number, colIndex: number): void => {
        const newGrid = [...grid];
        const currentCell = newGrid[rowIndex][colIndex];
        const newIsBlocked = !currentCell.isBlocked;

        // Clear all currently blocked cells first
        for (let i = 0; i < totalRows; i++) {
            for (let j = 0; j < totalColumns; j++) {
                if (newGrid[i][j].isBlocked) {
                    newGrid[i][j] = {
                        ...newGrid[i][j],
                        isBlocked: false,
                    };
                }
            }
        }

        // Toggle the isBlocked flag for the clicked cell
        newGrid[rowIndex][colIndex] = {
            ...currentCell,
            isBlocked: newIsBlocked,
        };

        setGrid(newGrid);
        setHasChanges(true);

        // Replace the blocked areas with the new one
        const newBlockedArea = {
            minRow: rowIndex,
            maxRow: rowIndex,
            minCol: colIndex,
            maxCol: colIndex,
        };

        // Replace instead of adding to the array
        setBlockedAreas(newIsBlocked ? [newBlockedArea] : []);

        // Scroll to block actions for the new selection
        setTimeout(() => {
            const blockActionsElement = document.getElementById(
                'block-actions-container',
            );
            if (blockActionsElement && sidebarContentRef.current) {
                blockActionsElement.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start',
                });
            }
        }, 100);
    };

    // Function to add a seat at the specified position
    const addSeatAtPosition = (rowIndex: number, colIndex: number): void => {
        const newGrid = [...grid];
        // Get the correct row label for this index
        const rowLabel = getAdjustedRowLabel(rowIndex);
        const adjustedColumn = colIndex + 1;

        const newSeat: SeatItem = {
            id: '', // Will be created on backend
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
        setHasChanges(true);
    };

    const deleteSeat = (rowIndex: number, colIndex: number): void => {
        const newGrid = [...grid];
        // Preserve the isBlocked flag when deleting a seat
        const isBlocked = newGrid[rowIndex][colIndex].isBlocked || false;

        newGrid[rowIndex][colIndex] = {
            type: 'empty',
            isBlocked,
        };

        setGrid(newGrid);
        reorderSeatNumbers();
        setHasChanges(true);
    };

    // Function to add seats to all empty cells in the blocked area
    const addSeatsToBlockedArea = (): void => {
        if (blockedAreas.length === 0) return;

        const newGrid = [...grid];
        let changesMade = false;

        // Process all blocked areas
        blockedAreas.forEach((area) => {
            for (let i = area.minRow; i <= area.maxRow; i++) {
                for (let j = area.minCol; j <= area.maxCol; j++) {
                    const cell = newGrid[i][j];
                    if (cell.type === 'empty' && cell.isBlocked) {
                        changesMade = true;
                        const rowLabel = getAdjustedRowLabel(i);
                        const adjustedColumn = j + 1;

                        const newSeat: SeatItem = {
                            id: '', // Empty ID, will be created in backend
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
        });

        if (changesMade) {
            setGrid(newGrid);
            reorderSeatNumbers();
            setHasChanges(true);

            // Clear blocked areas after adding seats
            setBlockedAreas([]);
        }
    };

    // Function to delete all seats in the blocked area
    const deleteSeatsFromBlockedArea = (): void => {
        if (blockedAreas.length === 0) return;

        const newGrid = [...grid];
        let changesMade = false;

        // Process all blocked areas
        blockedAreas.forEach((area) => {
            for (let i = area.minRow; i <= area.maxRow; i++) {
                for (let j = area.minCol; j <= area.maxCol; j++) {
                    const cell = newGrid[i][j];
                    if (cell.type === 'seat' && cell.isBlocked) {
                        changesMade = true;
                        newGrid[i][j] = {
                            type: 'empty',
                            isBlocked: true,
                        };
                    }
                }
            }
        });

        if (changesMade) {
            setGrid(newGrid);
            reorderSeatNumbers();
            setHasChanges(true);

            // Clear blocked areas after deleting seats
            setBlockedAreas([]);
        }
    };

    const reorderSeatNumbers = (): void => {
        const newGrid = [...grid];
        const seatCounters: { [key: string]: number } = {};

        // Process all rows
        for (let i = 0; i < totalRows; i++) {
            // Get the proper row label for this index
            const rowLabel = getAdjustedRowLabel(i);
            seatCounters[rowLabel] = 1;

            for (let j = 0; j < totalColumns; j++) {
                const cell = newGrid[i][j];
                if (cell.type === 'seat' && cell.item) {
                    cell.item.row = rowLabel;
                    cell.item.seat_number = `${rowLabel}${seatCounters[rowLabel]}`;
                    seatCounters[rowLabel]++;
                }
            }
        }

        setGrid(newGrid);
    };

    // Modified handleSave function to add seats to blocked empty spaces
    // Modified handleSave function to respect deleted seats
    const handleSave = (): void => {
        // Create a copy of the grid to work with
        const newGrid = [...grid];

        // Reorder seat numbers to ensure consistency
        const tempGrid = [...newGrid];
        const seatCounters: { [key: string]: number } = {};

        // Process from bottom to top correctly
        for (let i = totalRows - 1; i >= 0; i--) {
            const rowLabel = getAdjustedRowLabel(i);
            seatCounters[rowLabel] = 1;

            for (let j = 0; j < totalColumns; j++) {
                const cell = tempGrid[i][j];
                if (cell.type === 'seat' && cell.item) {
                    cell.item.row = rowLabel;
                    cell.item.seat_number = `${rowLabel}${seatCounters[rowLabel]}`;
                    seatCounters[rowLabel]++;
                }
            }
        }

        // Now collect all seats for the final layout
        const items: SeatItem[] = [];

        for (let i = 0; i < totalRows; i++) {
            const rowLabel = getAdjustedRowLabel(i);

            for (let j = 0; j < totalColumns; j++) {
                const cell = tempGrid[i][j];
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

        onSave(layout);
        setHasChanges(false);
        setBlockedAreas([]);
        // Show success notification
        // setShowSaveSuccess(true);

        // Hide notification after 3 seconds
        // setTimeout(() => {
        //     setShowSaveSuccess(false);
        // }, 3000);
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

    // Build the sidebar content
    const sidebarContent = (
        <>
            <DimensionsPanel
                dimensions={dimensions}
                setDimensions={setDimensions}
            />

            <ModeSelection
                mode={mode}
                onModeChange={handleModeChange}
                modes={['add', 'delete', 'block']}
            />

            {mode === 'block' && (
                <BlockActions
                    onAddSeats={addSeatsToBlockedArea}
                    onDeleteSeats={deleteSeatsFromBlockedArea}
                    onClearSelection={() => setBlockedAreas([])}
                    blockedAreasCount={blockedAreas.length}
                />
            )}
        </>
    );

    // Render the grid
    const renderGrid = () => {
        return (
            <Grid
                onMouseMove={handleMouseMove}
                onMouseUp={handleMouseUp}
                onMouseLeave={handleMouseLeave}
                gridRef={gridContainerRef}
                isDragging={isDragging}
                className="overflow-x-auto" // This is already correct
                // selectionBox={
                //     isDragging && startCell && endCell
                //         ? {
                //               left: Math.min(startCell.col, endCell.col) * 40, // Approximate width of a cell
                //               top: Math.min(startCell.row, endCell.row) * 40, // Approximate height of a cell
                //               width:
                //                   (Math.abs(endCell.col - startCell.col) + 1) *
                //                   40,
                //               height:
                //                   (Math.abs(endCell.row - startCell.row) + 1) *
                //                   40,
                //           }
                //         : null
                // }
            >
                {[...grid].reverse().map((row, reversedIndex) => {
                    const actualRowIndex = grid.length - 1 - reversedIndex;
                    return (
                        <div key={reversedIndex} className="flex gap-1">
                            {row.map((cell, colIndex) => (
                                <SeatCell
                                    key={colIndex}
                                    type={cell.type}
                                    item={cell.item}
                                    isBlocked={cell.isBlocked}
                                    isInBlockedArea={
                                        isDragging &&
                                        mode === 'block' &&
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
                                            Math.max(startCell.col, endCell.col)
                                    }
                                    color={getCellColor(cell)}
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
                                />
                            ))}
                        </div>
                    );
                })}
            </Grid>
        );
    };

    return (
        <>
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
                        >
                            <path d="M3 9h18v10a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V9Z"></path>
                            <path d="M3 9V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2v4"></path>
                            <path d="M12 12v5"></path>
                        </svg>
                    ),
                    title: 'Venue Editor',
                    content: sidebarContent,
                    contentRef: sidebarContentRef,
                    footer: (
                        <SaveButton
                            onClick={handleSave}
                            isDisabled={isDisabled}
                            hasChanges={hasChanges}
                        />
                    ),
                }}
                content={renderGrid()}
                droppedDown={droppedDown}
                handleToggle={handleToggle}
            />

            {/* <NotificationToast
                message="Layout saved successfully!"
                visible={showSaveSuccess}
            /> */}
        </>
    );
};

export default GridSeatEditor;
