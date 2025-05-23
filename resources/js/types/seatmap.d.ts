import { Toaster } from '@/hooks/useToaster';

type SeatStatus = 'available' | 'booked' | 'reserved' | 'in_transaction';

export interface SeatPosition {
    x: number;
    y: number;
}

export interface BaseItem {
    row: string | number;
    column: number;
}

export interface SeatItem extends BaseItem {
    id: string;
    seat_number: string;
    status: string;
    ticket_type?: string;
    category?: string;
    price: number | string | undefined;
}

export interface LabelItem extends BaseItem {
    type: 'label';
    text: string;
}

type LayoutItem = SeatItem | LabelItem;

export interface Layout {
    totalRows: number;
    totalColumns: number;
    items: LayoutItem[];
}

type Position = string | { x: number; y: number };

export interface Seat {
    id: string;
    seat_number: string;
    position: Position;
    status: SeatStatus;
    ticket_type?: string;
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
    selectedCategory?: string;
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
    category?: string;
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
    client: string;
    selectedSeats: SeatItem[];
    taxAmount?: number;
    subtotal?: number;
    total?: number;
    onTransactionStarted?: (seats: SeatItem[]) => void;
    toasterFunction: Toaster;
    snapInitialized: boolean;
    paymentGateway: string;
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
    transaction_id: string;
    new_order_id?: string;
}

// Property sets yang valid untuk MidtransError
export interface MidtransErrorDetail {
    message: string;
    status_code: string;
    error_messages?: string[];
}

// Tambahkan properti lain yang mungkin ada dengan union type
type MidtransError = MidtransErrorDetail & Record<string, unknown>;

export interface MidtransTransactionResultBase {
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

// Tambahkan properti lain dengan Record
type MidtransTransactionResult = MidtransTransactionResultBase &
    Record<string, unknown>;

// Buat export  interface untuk callback yang lebih spesifik
export interface MidtransCallbacks {
    onSuccess: (result: MidtransTransactionResult) => void;
    onPending: (result: MidtransTransactionResult) => void;
    onError: (error: MidtransError | Error | unknown) => void;
    onClose: () => void;
}

// Tambahkan export  interface baru untuk transaksi yang tersimpan
export interface PendingTransactionInfo {
    transaction_id: string;
    timestamp?: number;
}

export interface SavedTransaction {
    transactionInfo: PendingTransactionInfo;
    seats: SeatItem[];
}

// export  interface untuk Timeline
export interface Timeline {
    id: string;
    name: string;
    start_date: string;
    end_date: string;
}

export interface Venue {
    id: string;
    name: string;
}

export interface Event {
    id: string;
    name: string;
    event_date: string;
    venue_id: string;
    status: string;
    // ijin nambahin slug
    slug: string;
}

export interface TicketCategory {
    id: string;
    name: string;
    color: string;
}

export interface CategoryPrice {
    id: string;
    ticket_category_id: string;
    timeline_id: string;
    price: number;
}

export interface PendingTransactionResponseItem {
    snap_token: string;
    order_id: string;
    order_code: string;
    total_price: string;
    seats: SeatItem[];
}

export type SelectionMode = 'SINGLE' | 'MULTIPLE' | 'CATEGORY' | 'DRAG';

export interface UpdatedSeats {
    id: string;
    status: string;
    ticket_type: string;
    price: number;
}

export interface SeatMapEditorProps {
    layout: Layout;
    onSave: (updatedSeats: UpdatedSeats[]) => void;
    ticketTypes: string[];
    categoryColors?: Record<string, string>;
    currentTimeline?: {
        id: string;
        name: string;
        start_date: string;
        end_date: string;
    };
    // Add categoryPrices prop
    categoryPrices?: Record<string, number>;
}

export interface SeatMapDisplayProps {
    config: Layout;
    onSeatClick?: (seat: SeatItem) => void;
    selectedSeats?: SeatItem[];
    ticketTypeColors?: Record<string, string>;
    props: EventProps;
    currentTimeline?: Timeline;
    eventStatus?: string; // Tambahkan ini
}

export interface LandingProps {
    client: string;
    layout: Layout;
    event: Event;
    venue: Venue;
    ticketCategories: TicketCategory[];
    currentTimeline?: Timeline;
    categoryPrices?: CategoryPrice[];
    error?: string;
    props: EventProps;
    ownedTicketCount: number;
    userEndSessionDatetime: string;
    paymentGateway: string;
}
