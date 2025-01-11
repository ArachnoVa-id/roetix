export interface SeatTransaction {
    transaction_id: string;
    seat_id: string;
    user_id: string;
    status: 'pending' | 'completed' | 'cancelled' | 'expired';
    reservation_time: string;
    expiry_time: string;
    created_at: string;
    updated_at: string;
}
