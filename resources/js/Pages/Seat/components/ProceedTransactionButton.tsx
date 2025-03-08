import axios from 'axios';
import React, { useState } from 'react';
import {
    MidtransCallbacks,
    PaymentRequestPayload,
    PaymentResponse,
    ProceedTransactionButtonProps,
    SeatItem,
} from '../types';

const ProceedTransactionButton: React.FC<ProceedTransactionButtonProps> = ({
    selectedSeats,
}) => {
    const [isLoading, setIsLoading] = useState(false);

    // Function to group seats by category
    const transformSeatsToGroupedItems = (seats: SeatItem[]) => {
        const grouped: Record<
            string,
            {
                price: number;
                quantity: number;
                seatNumbers: string[];
            }
        > = {};

        seats.forEach((seat) => {
            const { category, seat_number, price } = seat;

            // Skip seats with undefined category or seat_number
            if (category === undefined || seat_number === undefined) {
                return;
            }

            if (!grouped[category]) {
                grouped[category] = { price, quantity: 0, seatNumbers: [] };
            }

            grouped[category].quantity += 1;
            grouped[category].seatNumbers.push(seat_number);

            return grouped;
        }, {});
    };

    // Calculate total amount
    const calculateTotalAmount = (
        groupedItems: PaymentRequestPayload['grouped_items'],
    ) => {
        return Object.values(groupedItems).reduce(
            (total, item) => total + item.price * item.quantity,
            0,
        );
    };

    // Ensure CSRF protection
    const fetchCsrfToken = async () => {
        try {
            await axios.get('/sanctum/csrf-cookie'); // Laravel Sanctum CSRF setup
        } catch (error) {
            console.error('Failed to fetch CSRF token:', error);
            throw new Error('CSRF token fetch failed');
        }
    };

    // Handle payment process
    const handleProceedTransaction = async () => {
        if (selectedSeats.length === 0) {
            alert('Please select at least one seat.');
            return;
        }

        setIsLoading(true); // Prevent multiple clicks

        const groupedItems = transformSeatsToGroupedItems(selectedSeats);
        const totalAmount = calculateTotalAmount(groupedItems);

        const requestPayload: PaymentRequestPayload = {
            email: 'john.doe@example.com',
            amount: totalAmount,
            grouped_items: groupedItems,
        };

        try {
            // Ensure CSRF token is available
            await fetchCsrfToken();

            // Proceed with payment request
            const response = await axios.post<PaymentResponse>(
                '/payment/charge',
                requestPayload,
            );
            const snapToken = response.data.snap_token;

            if (window.snap) {
                const midtransCallbacks: MidtransCallbacks = {
                    onSuccess: () => {},
                    onPending: () => {},
                    onError: (error) => console.error('Payment failed:', error),
                    onClose: () => {},
                };

                window.snap.pay(snapToken, midtransCallbacks);
            } else {
                alert('Snap.js is not loaded. Please refresh and try again.');
            }
        } catch (error) {
            if (axios.isAxiosError(error)) {
                console.error(
                    'Error creating charge:',
                    error.response?.data || error.message,
                );
                alert(
                    `Error: ${error.response?.data?.message || 'Failed to create transaction'}`,
                );
            } else {
                console.error('Unexpected error:', error);
                alert('An unexpected error occurred. Please try again.');
            }
        } finally {
            setIsLoading(false); // Re-enable button after request
        }
    };

    return (
        <button
            className="mt-4 rounded bg-green-500 px-4 py-2 text-white hover:bg-green-600 disabled:opacity-50"
            disabled={isLoading || selectedSeats.length === 0}
            onClick={handleProceedTransaction}
        >
            {isLoading ? 'Processing...' : 'Proceed Transaction'}
        </button>
    );
};

export default ProceedTransactionButton;
