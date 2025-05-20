import React, { MouseEvent } from 'react';

export interface SeatItem {
    id: string;
    seat_number: string;
    row: string | number;
    column: number;
    status: string;
    category?: string;
    ticket_type?: string;
    price?: number;
    // [key: string]: any;
}

interface SeatCellProps {
    type?: 'empty' | 'seat' | 'label';
    item?: SeatItem | null;
    isBlocked?: boolean;
    isSelected?: boolean;
    isInBlockedArea: boolean | null;
    isEditable?: boolean;
    onClick?: () => void;
    onMouseDown?: (event: MouseEvent<HTMLDivElement>) => void;
    onMouseOver?: () => void;
    color?: string;
}

export const SeatCell: React.FC<SeatCellProps> = ({
    type = 'empty',
    item = null,
    isBlocked = false,
    isSelected = false,
    isInBlockedArea = false,
    isEditable = true,
    onClick,
    onMouseDown,
    onMouseOver,
    color = '',
}) => {
    const baseClasses =
        'flex h-8 w-8 cursor-pointer select-none items-center justify-center rounded text-xs font-medium';

    // Determine the cell's visual state based on multiple conditions
    let cellClasses = '';

    // Active selection state (being dragged/currently selected)
    if (isInBlockedArea) {
        cellClasses = `${baseClasses} bg-blue-400 text-white border-2 border-blue-600`;
    }
    // Already blocked state (after selection is complete)
    else if (isBlocked) {
        cellClasses = `${baseClasses} bg-blue-200 text-blue-900 border-2 border-blue-400`;
    }
    // Normal empty or seat state
    else {
        // Define cell background color
        let cellColor = '';
        if (type === 'empty') {
            cellColor = 'bg-gray-100 hover:bg-gray-200';
        } else if (type === 'label') {
            cellColor = 'bg-gray-200';
        } else if (color) {
            cellColor = color;
        }

        // Standard border for non-blocked cells
        const borderStyle = 'border border-gray-200';

        // Selection highlight for manually selected cells (not part of block selection)
        const selectionStyle = isSelected ? 'ring-2 ring-blue-500' : '';

        cellClasses = `${baseClasses} ${cellColor} ${borderStyle} ${selectionStyle}`;
    }

    // Add cursor and opacity for editable state
    const editableStyle = !isEditable ? 'cursor-not-allowed opacity-75' : '';

    return (
        <div
            onClick={isEditable ? onClick : undefined}
            onMouseDown={isEditable ? onMouseDown : undefined}
            onMouseOver={onMouseOver}
            className={`${cellClasses} ${editableStyle}`}
            draggable={false}
        >
            {type === 'seat' && item?.seat_number}
        </div>
    );
};
