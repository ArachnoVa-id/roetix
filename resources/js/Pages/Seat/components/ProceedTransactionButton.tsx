import useToaster from '@/hooks/useToaster';
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
    taxAmount,
    subtotal,
    total,
    onTransactionStarted,
}) => {
    const [isLoading, setIsLoading] = useState(false);
    const [snapInitialized, setSnapInitialized] = useState(false);
    const { showSuccess, showError } = useToaster();
    const [transactionInfo, setTransactionInfo] = useState<{
        snap_token: string;
        transaction_id: string;
    } | null>(null);

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
            showError(
                'Payment system could not be loaded. Please try again later.',
            );
        };

        document.head.appendChild(snapScript);

        // Cleanup
        return () => {
            document.head.removeChild(snapScript);
        };
    }, [showError]);

    useEffect(() => {
        // Load saved transaction on component mount
        const savedTransaction = localStorage.getItem('pendingTransaction');
        if (savedTransaction) {
            try {
                const parsed = JSON.parse(savedTransaction);
                setTransactionInfo(parsed.transactionInfo);

                // Notify parent component about pending transaction if needed
                if (onTransactionStarted && parsed.seats) {
                    onTransactionStarted(parsed.seats);
                }
            } catch (e) {
                console.error('Failed to parse saved transaction', e);
                localStorage.removeItem('pendingTransaction');
            }
        }
    }, [onTransactionStarted]);

    useEffect(() => {
        if (transactionInfo) {
            localStorage.setItem(
                'pendingTransaction',
                JSON.stringify({
                    transactionInfo,
                    seats: selectedSeats,
                }),
            );
        } else {
            localStorage.removeItem('pendingTransaction');
        }
    }, [transactionInfo, selectedSeats]);

    const clearTransaction = () => {
        setTransactionInfo(null);
        localStorage.removeItem('pendingTransaction');
        if (onTransactionStarted) {
            onTransactionStarted([]);
        }
    };

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

    const createCallbacks = (token: string): MidtransCallbacks => {
        return {
            onSuccess: (result) => {
                console.log('Payment success:', result);
                showSuccess('Payment successful!');
                clearTransaction(); // Clear the transaction data
                window.location.reload();
            },
            onPending: (result) => {
                console.log('Payment pending:', result);
                showSuccess(
                    'Your payment is pending. Please complete the payment.',
                );
                setIsLoading(false);
            },
            onError: (result) => {
                console.error('Payment error:', result);
                showError('Payment failed. Please try again.');
                setIsLoading(false);
            },
            onClose: () => {
                console.log('Snap payment closed');
                setIsLoading(false);
                // Save transaction information for later resumption
                setTransactionInfo({
                    snap_token: token,
                    transaction_id: transactionInfo?.transaction_id || '',
                });
                showError(
                    'Payment window closed. You can resume your payment using the "Resume Payment" button below.',
                );
            },
        };
    };

    // Handle payment process
    const handleProceedTransaction = async () => {
        if (selectedSeats.length === 0) {
            showError('Please select at least one seat to proceed.');
            return;
        }

        if (!snapInitialized) {
            showError(
                'Payment system is still initializing. Please try again in a moment.',
            );
            return;
        }

        setIsLoading(true);
        showSuccess('Preparing your payment...');

        try {
            // Prepare the payment data
            const groupedItems = transformSeatsToGroupedItems(selectedSeats);
            const calculatedSubtotal =
                subtotal || calculateTotalAmount(groupedItems);

            // Calculate tax if not provided
            const calculatedTaxAmount = taxAmount || calculatedSubtotal * 0.01; // 1% tax

            // Calculate total with tax if not provided
            const calculatedTotal =
                total || calculatedSubtotal + calculatedTaxAmount;

            console.log('Payment data:', {
                groupedItems,
                subtotal: calculatedSubtotal,
                taxAmount: calculatedTaxAmount,
                total: calculatedTotal,
            });

            // Get the current user's email or use a default
            // In a real app, you would get this from your auth system
            const userEmail = 'user@example.com';

            // Create the request payload
            const payload = {
                email: userEmail,
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
                    const token = response.data.snap_token;
                    const callbacks = createCallbacks(token);

                    // Open the Midtrans Snap payment page
                    window.snap.pay(token, callbacks);

                    if (onTransactionStarted) {
                        onTransactionStarted(selectedSeats);
                    }
                } else {
                    console.error('Snap.js is not properly initialized');
                    showError(
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
                showError(errorMsg);
            } else {
                showError('An unexpected error occurred');
            }

            setIsLoading(false);
        }
    };

    const resumePayment = async () => {
        if (!transactionInfo || !window.snap) return;

        setIsLoading(true);
        showSuccess('Preparing your payment...');

        try {
            const config = {
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN':
                        document
                            .querySelector('meta[name="csrf-token"]')
                            ?.getAttribute('content') || '',
                },
            };

            const response = await axios.post(
                '/payment/resume',
                { transaction_id: transactionInfo.transaction_id },
                config,
            );

            if (response.data && response.data.snap_token) {
                const token = response.data.snap_token;
                const callbacks = createCallbacks(token);

                // Open the Midtrans Snap payment page using original transaction ID
                window.snap.pay(token, callbacks);
            } else {
                throw new Error('Invalid response from payment server');
            }
        } catch (err) {
            console.error('Failed to resume payment:', err);

            if (axios.isAxiosError(err)) {
                const errorMsg =
                    err.response?.data?.message ||
                    'Failed to connect to payment server';
                showError(errorMsg);
            } else {
                showError('Failed to resume payment. Please try again.');
            }
        } finally {
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

            {transactionInfo && (
                <button
                    className="ml-2 mt-4 rounded bg-blue-500 px-4 py-2 text-white hover:bg-blue-600"
                    onClick={resumePayment}
                >
                    Resume Payment
                </button>
            )}

            {!snapInitialized && (
                <div className="mt-2 text-sm text-gray-600">
                    Initializing payment system...
                </div>
            )}
        </div>
    );
};

export default ProceedTransactionButton;
