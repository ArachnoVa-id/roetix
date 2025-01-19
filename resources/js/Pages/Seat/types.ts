export type SeatStatus = 'available' | 'booked' | 'reserved' | 'in_transaction';
export type Category = 'diamond' | 'gold' | 'silver';

export interface SeatPosition {
    x: number;
    y: number;
}

export type Position = string | { x: number; y: number };

export interface Seat {
    seat_id: string;
    seat_number: string;
    position: Position;
    status: SeatStatus;
    category: Category;
    row: string;
    column: number;
    price: number;
  }

export interface SeatMapSection {
  id: string;
  name: string;
  rows: string[];
  seats: Seat[];
}

export interface SeatMapProps {
  sections: SeatMapSection[];
  onSeatClick?: (seat: Seat) => void;
}

export interface EditorState {
    mode: 'SINGLE' | 'CAT_GROUP' | 'DRAG';
    selectedCategory?: Category;
    selectedSeats: Set<string>;
    isDragging: boolean;
    dragStartPosition?: {
      x: number;
      y: number;
    };
}

export interface DragState {
    seatId: string;
    startPosition: SeatPosition;
    currentPosition: SeatPosition;
}