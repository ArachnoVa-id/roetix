export interface TicketData {
    date: string;
    type: string;
    seat: string;
    price: string;
}

export interface TicketProps {
    id: string;
    type: string;
    code: string;
    qrStr: string;
    status: string;
    categoryColor?: string; // Add optional categoryColor property
    data: TicketData;
}

export interface SeatInterface {
    seat_id: string;
    seat_number: string;
    row: string;
    column: number;
    status?: string;
    ticket_type?: string;
    ticket_category_id?: string;
    price?: number;
}

export interface OrderInterface {
    order_id: string;
    order_date: string;
    total_price: number;
    status: string;
}

export interface TicketCategoryInterface {
    ticket_category_id: string;
    name: string;
    color: string;
    event_id: string;
}

export interface TicketInterface {
    ticket_id: string;
    ticket_type: string;
    price: number;
    status: string;
    event_id: string;
    seat_id: string;
    id: string;
    order_id?: string;
    ticket_category_id?: string;
    created_at: string;
    updated_at: string;
    seat?: SeatInterface;
    order?: OrderInterface;
    ticketCategory?: TicketCategoryInterface;
    category_color?: string; // Add optional category_color property
}

export interface EventInterface {
    event_id: string;
    name: string;
    slug: string;
    description: string;
    venue_id: string;
    event_variables_id: string;
}

export interface TicketActionEvent extends Event {
    detail: {
        action: string;
        ticketId: string;
        ticketType?: string;
        error?: string;
    };
}

// Add additional interfaces for use in the MyTickets component
export interface MyTicketsPageProps {
    client: string;
    props: import('@/types/front-end').EventProps;
    tickets: TicketProps[];
    event: EventInterface;
}

export interface RowComponentProps {
    idtf: string;
    content: string;
}

export interface TicketComponentProps extends TicketProps {
    eventId: string;
    categoryColor?: string;
    userData?: {
        firstName: string;
        lastName: string;
        email: string;
    };
    eventInfo?: {
        location: string;
        eventDate: string;
    };
}
