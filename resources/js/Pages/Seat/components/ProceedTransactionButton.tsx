import {
    MidtransCallbacks,
    PaymentRequestGroupedItems,
    ProceedTransactionButtonProps,
    SeatItem,
} from '@/types/seatmap';
import { usePage } from '@inertiajs/react';
import axios from 'axios';
import React, { useState } from 'react';

const ProceedTransactionButton: React.FC<ProceedTransactionButtonProps> = ({
    client,
    selectedSeats,
    taxAmount,
    subtotal,
    total,
    onTransactionStarted,
    toasterFunction,
    snapInitialized,
}) => {
    const user = usePage().props?.auth.user;
    const [isLoading, setIsLoading] = useState<boolean>(false);

    // Function to safely parse price
    const getSafePrice = (price: string | number | undefined): number => {
        if (price === undefined || price === null) return 0;

        if (typeof price === 'number') return price;

        if (typeof price === 'string') {
            // Clean the price string (remove currency symbols, spaces, etc.)
            let cleaned = price.replace(/[^0-9,.]/g, '');

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

    const createCallbacks = (): MidtransCallbacks => {
        return {
            onSuccess: () => {
                toasterFunction.showSuccess('Payment successful!');
                window.location.reload();
            },
            onPending: () => {
                toasterFunction.showSuccess(
                    'Your payment is pending. Please complete the payment.',
                );
                setIsLoading(false);
                window.location.reload();
            },
            onError: () => {
                toasterFunction.showError('Payment failed. Please try again.');
                setIsLoading(false);
            },
            onClose: () => {
                toasterFunction.showSuccess(
                    'Payment window closed. You can resume your payment using the "Resume Payment" button below.',
                );
                setIsLoading(false);
                window.location.reload();
            },
        };
    };

    // Handle payment process
    const handleProceedTransaction = async () => {
        if (selectedSeats.length === 0) {
            toasterFunction.showError(
                'Please select at least one seat to proceed.',
            );
            return;
        }

        if (!snapInitialized) {
            toasterFunction.showError(
                'Payment system is still initializing. Please try again in a moment.',
            );
            return;
        }

        toasterFunction.showSuccess('Preparing your payment...');
        setIsLoading(true);

        try {
            // Prepare the payment data
            const groupedItems = transformSeatsToGroupedItems(selectedSeats);
            const calculatedSubtotal =
                subtotal || calculateTotalAmount(groupedItems);

            // Calculate tax if not provided
            // const tax = 0.01; // 1% tax
            const tax = 0;
            const calculatedTaxAmount = taxAmount || calculatedSubtotal * tax;

            // Calculate total with tax if not provided
            const calculatedTotal =
                total || calculatedSubtotal + calculatedTaxAmount;

            // Create the request payload
            const payload = {
                email: user.email,
                amount: calculatedSubtotal, // Original amount before tax
                tax_amount: calculatedTaxAmount, // Tax amount
                total_with_tax: calculatedTotal, // Total with tax
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
            const response = await axios.post(
                route('payment.charge', client),
                payload,
                config,
            );

            // Handle the response
            if (response.data && response.data.snap_token) {
                // If Midtrans snap.js is loaded
                if (window.snap) {
                    const token = response.data.snap_token;
                    const callbacks = createCallbacks();

                    // Open the Midtrans Snap payment page
                    window.snap.pay(token, callbacks);

                    if (onTransactionStarted) {
                        onTransactionStarted(selectedSeats);
                    }
                } else {
                    console.error('Snap.js is not properly initialized');
                    toasterFunction.showError(
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
                toasterFunction.showError(errorMsg);
            } else {
                toasterFunction.showError('An unexpected error occurred');
            }

            setIsLoading(false);
        }
    };

    return (
        <div>
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
                    Initializing payment system taking longer than usual. Plese
                    contact admin.
                </div>
            )}
        </div>
    );
};

export default ProceedTransactionButton;
