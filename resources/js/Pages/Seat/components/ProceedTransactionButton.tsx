import axios from 'axios';
import React, { useEffect, useState } from 'react';
import {
    MidtransCallbacks,
    PaymentRequestGroupedItems,
    ProceedTransactionButtonProps,
    SeatItem,
} from '../types';

const ProceedTransactionButton: React.FC<ProceedTransactionButtonProps> = ({
    selectedSeats,
}) => {
    const [isLoading, setIsLoading] = useState(false);
    const [error, setError] = useState<string | null>(null);
    const [snapInitialized, setSnapInitialized] = useState(false);

    // Initialize Midtrans Snap on component mount
    useEffect(() => {
        // Check if Snap is already loaded
        if (window.snap) {
            setSnapInitialized(true);
            return;
        }

        // Load Snap.js
        const snapScript = document.createElement('script');
        snapScript.src = 'https://app.sandbox.midtrans.com/snap/snap.js';
        snapScript.setAttribute(
            'data-client-key',
            process.env.MIDTRANS_CLIENT_KEY || '',
        );
        snapScript.onload = () => {
            console.log('Midtrans Snap loaded successfully');
            setSnapInitialized(true);
        };
        snapScript.onerror = () => {
            console.error('Failed to load Midtrans Snap');
            setError(
                'Payment system could not be loaded. Please try again later.',
            );
        };

        document.head.appendChild(snapScript);

        // Cleanup
        return () => {
            document.head.removeChild(snapScript);
        };
    }, []);

    // Function to safely parse price
    const getSafePrice = (price: string | number | undefined): number => {
        if (price === undefined || price === null) return 0;

        if (typeof price === 'number') return price;

        if (typeof price === 'string') {
            // Clean the price string (remove currency symbols, spaces, etc.)
            let cleaned = price.replace(/[^0-9,\.]/g, '');

            // Handle Indonesian number format (periods for thousands, comma for decimal)
            if (cleaned.includes(',') && cleaned.includes('.')) {
                cleaned = cleaned.replace(/\./g, ''); // Remove thousands separators
                cleaned = cleaned.replace(',', '.'); // Convert decimal comma to point
            } else if (cleaned.includes(',')) {
                cleaned = cleaned.replace(',', '.'); // Convert comma to decimal point
            }

            const numericPrice = parseFloat(cleaned);
            return isNaN(numericPrice) ? 0 : numericPrice;
        }

        return 0;
    };

    // Function to group seats by category
    const transformSeatsToGroupedItems = (
        seats: SeatItem[],
    ): PaymentRequestGroupedItems => {
        const grouped: PaymentRequestGroupedItems = {};

        seats.forEach((seat) => {
            const { category, seat_number } = seat;
            const price = getSafePrice(seat.price);

            if (!category || !seat_number) return;

            if (!grouped[category]) {
                grouped[category] = {
                    price: price,
                    quantity: 0,
                    seatNumbers: [],
                };
            }

            grouped[category].quantity += 1;
            grouped[category].seatNumbers.push(seat_number);
        });

        return grouped;
    };

    // Calculate total amount
    const calculateTotalAmount = (
        groupedItems: PaymentRequestGroupedItems,
    ): number => {
        return Object.values(groupedItems).reduce(
            (total, item) => total + item.price * item.quantity,
            0,
        );
    };

    // Handle payment process
    const handleProceedTransaction = async () => {
        if (selectedSeats.length === 0) {
            alert('Please select at least one seat to proceed.');
            return;
        }

        if (!snapInitialized) {
            setError(
                'Payment system is still initializing. Please try again in a moment.',
            );
            return;
        }

        setIsLoading(true);
        setError(null);

        try {
            // Prepare the payment data
            const groupedItems = transformSeatsToGroupedItems(selectedSeats);
            const totalAmount = calculateTotalAmount(groupedItems);

            console.log('Payment data:', {
                groupedItems,
                totalAmount,
            });

            // Get the current user's email or use a default
            // In a real app, you would get this from your auth system
            const userEmail = 'user@example.com';

            // Create the request payload
            const payload = {
                email: userEmail,
                amount: totalAmount,
                grouped_items: groupedItems,
            };

            // Set up axios for the request
            const config = {
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
            };

            // Send the payment request
            console.log('Sending payment request');
            const response = await axios.post(
                '/payment/charge',
                payload,
                config,
            );
            console.log('Payment response:', response.data);

            // Handle the response
            if (response.data && response.data.snap_token) {
                // If Midtrans snap.js is loaded
                if (window.snap) {
                    const callbacks: MidtransCallbacks = {
                        onSuccess: (result) => {
                            console.log('Payment success:', result);
                            alert('Payment successful!');
                            window.location.reload();
                        },
                        onPending: (result) => {
                            console.log('Payment pending:', result);
                            alert(
                                'Your payment is pending. Please complete the payment.',
                            );
                            setIsLoading(false);
                        },
                        onError: (result) => {
                            console.error('Payment error:', result);
                            setError('Payment failed. Please try again.');
                            setIsLoading(false);
                        },
                        onClose: () => {
                            console.log('Snap payment closed');
                            setIsLoading(false);
                        },
                    };

                    // Open the Midtrans Snap payment page
                    window.snap.pay(response.data.snap_token, callbacks);
                } else {
                    console.error('Snap.js is not properly initialized');
                    setError(
                        'Payment gateway not loaded. Please refresh the page and try again.',
                    );
                    setIsLoading(false);
                }
            } else {
                throw new Error('Invalid response from payment server');
            }
        } catch (err) {
            console.error('Payment error:', err);

            if (axios.isAxiosError(err)) {
                const errorMsg =
                    err.response?.data?.message ||
                    'Failed to connect to payment server';
                setError(errorMsg);
            } else {
                setError('An unexpected error occurred');
            }

            setIsLoading(false);
        }
    };

    return (
        <div>
            {error && (
                <div className="mb-4 mt-2 rounded-md bg-red-50 p-3 text-red-600">
                    {error}
                </div>
            )}
            <button
                className="mt-4 rounded bg-green-500 px-4 py-2 text-white hover:bg-green-600 disabled:opacity-50"
                disabled={
                    isLoading || selectedSeats.length === 0 || !snapInitialized
                }
                onClick={handleProceedTransaction}
            >
                {isLoading ? 'Processing...' : 'Proceed Transaction'}
            </button>
            {!snapInitialized && (
                <div className="mt-2 text-sm text-gray-600">
                    Initializing payment system...
                </div>
            )}
        </div>
    );
};

export default ProceedTransactionButton;
