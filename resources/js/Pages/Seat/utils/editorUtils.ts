import { Position, SeatMapSection } from '@/types/seatmap';

export const updateSeatPositions = (
    sections: SeatMapSection[],
    seatId: string,
    newPosition: Position,
): SeatMapSection[] => {
    return sections.map((section) => ({
        ...section,
        seats: section.seats.map((seat) =>
            seat.seat_id === seatId ? { ...seat, position: newPosition } : seat,
        ),
    }));
};

export const serializePosition = (pos: { x: number; y: number }): string => {
    return `${pos.x},${pos.y}`;
};

export const parsePosition = (pos: string): { x: number; y: number } | null => {
    const [x, y] = pos.split(',').map(Number);
    if (isNaN(x) || isNaN(y)) return null;
    return { x, y };
};

export const calculateGridPosition = (
    clientX: number,
    clientY: number,
    gridRef: HTMLElement,
    cellSize: number = 24,
): { row: number; col: number } => {
    const rect = gridRef.getBoundingClientRect();
    const x = clientX - rect.left;
    const y = clientY - rect.top;

    return {
        row: Math.floor(y / cellSize),
        col: Math.floor(x / cellSize),
    };
};
