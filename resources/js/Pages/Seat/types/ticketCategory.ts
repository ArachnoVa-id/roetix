export interface TicketCategory {
    ticket_category_id: string;
    event_id: string;
    name: string;
    color: string;
    created_at: string;
    updated_at: string;
    current_price?: TimeboundPrice;
}

export interface TimeboundPrice {
    timebound_price_id: string;
    ticket_category_id: string;
    start_date: string;
    end_date: string;
    price: number;
}
