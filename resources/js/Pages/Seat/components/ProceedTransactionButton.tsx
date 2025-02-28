import React from 'react';
import axios from 'axios';
import {
    ProceedTransactionButtonProps,
    PaymentRequestPayload,
    PaymentResponse,
    MidtransCallbacks,
    SeatItem,
} from '../types';

const ProceedTransactionButton: React.FC<ProceedTransactionButtonProps> = ({ selectedSeats }) => {

    const transformSeatsToGroupedItems = (seats: SeatItem[]) => {
        const grouped: Record<string, { price: number; quantity: number; seatNumbers: string[] }> = {};

        seats.forEach(seat => {
            const { category, seat_number, price } = seat;

            if (!grouped[category]) {
                grouped[category] = {
                    price: price ?? 50000,
                    quantity: 0,
                    seatNumbers: [],
                };
            }

            grouped[category].quantity += 1;
            grouped[category].seatNumbers.push(seat_number);
        });

        return grouped;
    };

    // Fungsi hitung total amount
    const calculateTotalAmount = (groupedItems: PaymentRequestPayload['grouped_items']) => {
        return Object.values(groupedItems).reduce((total, item) => {
            return total + (item.price * item.quantity);
        }, 0);
    };

    const handleProceedTransaction = async () => {
        if (selectedSeats.length === 0) {
            alert('Please select at least one seat.');
            return;
        }

        const groupedItems = transformSeatsToGroupedItems(selectedSeats);
        const totalAmount = calculateTotalAmount(groupedItems);

        const requestPayload: PaymentRequestPayload = {
            email: 'john.doe@example.com',
            amount: totalAmount,
            grouped_items: groupedItems,
        };

        console.log('Request Payload:', requestPayload);

        try {
            const response = await axios.post<PaymentResponse>('/payment/charge', requestPayload);

            const snapToken = response.data.snap_token;

            if (window.snap) {
                const midtransCallbacks: MidtransCallbacks = {
                    onSuccess: (result) => console.log('Payment success:', result),
                    onPending: (result) => console.log('Payment pending:', result),
                    onError: (error) => console.error('Payment failed:', error),
                    onClose: () => console.log('Payment popup closed'),
                };

                window.snap.pay(snapToken, midtransCallbacks);
            } else {
                alert('Snap.js is not loaded');
            }
        } catch (error: any) {
            console.error('Error creating charge:', error.response?.data || error.message);
        }
    };

    return (
        <button
            className="mt-4 rounded bg-green-500 px-4 py-2 text-white hover:bg-green-600 disabled:opacity-50"
            disabled={selectedSeats.length === 0}
            onClick={handleProceedTransaction}
        >
            Proceed Transaction
        </button>
    );
};

export default ProceedTransactionButton;
