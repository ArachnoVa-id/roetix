// types/ticket.ts
export interface TicketData {
    date: string;
    type: string;
    seat: string;
    price: string;
}

export interface TicketProps {
    id: string;
    ticketType: string;
    ticketCode: string;
    ticketURL: string;
    status: string;
    ticketData: TicketData;
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
}

export interface EventInterface {
    event_id: string;
    name: string;
    slug: string;
    description: string;
    venue_id: string;
    event_variables_id: string;
}

// Add additional interfaces for use in the MyTickets component
export interface MyTicketsPageProps {
    client: string;
    props: import('@/types/front-end').EventProps;
    tickets: TicketProps[];
    event: EventInterface;
}
