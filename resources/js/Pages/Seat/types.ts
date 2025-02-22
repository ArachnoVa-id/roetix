export type SeatStatus = 'available' | 'booked' | 'in_transaction' | 'not_available';
export type Category = 'diamond' | 'gold' | 'silver';
export type ItemType = 'seat' | 'label';

export interface SeatPosition {
    x: number;
    y: number;
}

export interface BaseItem {
  row: string | number;
  column: number;
  type: ItemType;
}

export interface SeatItem {
 type: 'seat';
 seat_id: string;
 seat_number: string;
 row: string | number; // Allow both string/number for row
 column: number;
 status: SeatStatus;
 category: Category;
 price: number;
}

export interface LabelItem extends BaseItem {
  type: 'label';
  text: string;
}

export type LayoutItem = SeatItem | LabelItem;

export interface Layout {
  totalRows: number;
  totalColumns: number;
  items: LayoutItem[];
}

export type Position = string | { x: number; y: number };

export interface Seat {
    seat_id: string;
    // section_id: string;
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

export interface SeatMap {
  row: number;
  column: number;
  type: 'seat' | 'label';
  category?: Category;
  status?: SeatStatus;
  label?: string;
  seat_id?: string;
}

export interface SeatMapConfig {
  totalRows: number;
  totalColumns: number;
  items: SeatMap[];
}