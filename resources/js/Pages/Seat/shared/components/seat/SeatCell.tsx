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

    // Define cell background color
    let cellColor = '';
    if (type === 'empty') {
        cellColor = 'bg-gray-100 hover:bg-gray-200';
    } else if (type === 'label') {
        cellColor = 'bg-gray-200';
    } else if (color) {
        cellColor = color;
    }

    // Add border styles
    const borderStyle = isBlocked
        ? 'border-2 border-gray-400'
        : 'border border-gray-200';

    // Add selection highlighting
    const selectionStyle =
        isSelected || isInBlockedArea ? 'ring-2 ring-blue-500' : '';

    // Add cursor and opacity for editable state
    const editableStyle = !isEditable ? 'cursor-not-allowed opacity-75' : '';

    return (
        <div
            onClick={isEditable ? onClick : undefined}
            onMouseDown={isEditable ? onMouseDown : undefined}
            onMouseOver={onMouseOver}
            className={`${baseClasses} ${cellColor} ${borderStyle} ${selectionStyle} ${editableStyle}`}
            draggable={false}
        >
            {type === 'seat' && item?.seat_number}
        </div>
    );
};
