export type SeatStatus = 'available' | 'booked' | 'reserved' | 'in_transaction';
export type Category = 'standard' | 'VIP';
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

export interface SeatItem extends BaseItem {
    seat_id: string;
    seat_number: string;
    status: string;
    ticket_type?: string;
    category?: Category;
    price: number | string | undefined;
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
    seat_number: string;
    position: Position;
    status: SeatStatus;
    ticket_type?: string;
    category?: Category;
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
    ticket_type?: string;
    status?: SeatStatus;
    label?: string;
    seat_id?: string;
}

export interface SeatMapConfig {
    totalRows: number;
    totalColumns: number;
    items: SeatMap[];
}

export interface ProceedTransactionButtonProps {
    selectedSeats: SeatItem[];
    taxAmount?: number; // Optional tax amount
    subtotal?: number; // Optional subtotal
    total?: number; // Optional total with tax
}

export interface GroupedItem {
    price: number;
    quantity: number;
    seatNumbers: string[];
}
export interface PaymentRequestGroupedItems {
    [ticket_type: string]: GroupedItem;
}

export interface PaymentRequestPayload {
    email: string;
    amount: number;
    grouped_items: PaymentRequestGroupedItems;
}

export interface PaymentResponse {
    snap_token: string;
}

export interface MidtransTransactionResult {
    order_id: string;
    transaction_status:
        | 'settlement'
        | 'pending'
        | 'deny'
        | 'expire'
        | 'cancel'
        | 'failure'
        | 'refund'
        | 'partial_refund';
    transaction_id: string;
    payment_type: string;
    gross_amount: string;
    fraud_status: 'accept' | 'deny' | 'challenge';
    status_message: string;
    status_code: string;
}
export interface MidtransError {
    message: string;
    status_code: string;
    error_messages?: string[];
}
export interface MidtransCallbacks {
    onSuccess: (result: MidtransTransactionResult) => void;
    onPending: (result: MidtransTransactionResult) => void;
    onError: (error: MidtransError) => void;
    onClose: () => void;
}
