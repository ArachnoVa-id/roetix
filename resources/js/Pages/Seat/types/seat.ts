export interface Seat {
    seat_id: string;
    venue_id: string;
    seat_number: string;
    position: string;
    status: 'available' | 'booked' | 'reserved' | 'in_transaction';
    created_at: string;
    updated_at: string;
}
