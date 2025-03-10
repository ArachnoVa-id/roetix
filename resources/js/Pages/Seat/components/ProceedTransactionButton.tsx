import React, { useState } from 'react';
import {
    MidtransCallbacks,
    PaymentRequestGroupedItems,
    PaymentRequestPayload,
    ProceedTransactionButtonProps,
    SeatItem,
} from '../types';

const ProceedTransactionButton: React.FC<ProceedTransactionButtonProps> = ({
    selectedSeats,
}) => {
    const [isLoading, setIsLoading] = useState(false);
    const [error, setError] = useState<string | null>(null);

    // Function to safely parse price
    const getSafePrice = (price: string | number | undefined): number => {
        if (price === undefined || price === null) return 0;

        // If it's already a number, return it directly
        if (typeof price === 'number') {
            return price;
        }

        if (typeof price === 'string') {
            // Remove currency symbol, spaces, and non-numeric characters except decimals and commas
            let cleaned = price.replace(/[^0-9,\.]/g, '');

            // Handle Indonesian number format: convert "200.000,00" to "200000.00"
            if (cleaned.includes(',') && cleaned.includes('.')) {
                // First, remove all periods (thousand separators)
                cleaned = cleaned.replace(/\./g, '');
                // Then replace comma with period for decimal
                cleaned = cleaned.replace(',', '.');
            } else if (cleaned.includes(',')) {
                // Just has a comma - replace with period for standard JS parsing
                cleaned = cleaned.replace(',', '.');
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
            // Convert price safely
            const price = getSafePrice(seat.price);

            // Skip seats with undefined category or seat_number
            if (category === undefined || seat_number === undefined) {
                return;
            }

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
    const calculateTotalAmount = (groupedItems: PaymentRequestGroupedItems) => {
        return Object.values(groupedItems).reduce(
            (total, item) => total + item.price * item.quantity,
            0,
        );
    };

    // Handle payment process
    const handleProceedTransaction = async () => {
        if (selectedSeats.length === 0) {
            alert('Please select at least one seat.');
            return;
        }

        setIsLoading(true);
        setError(null);

        try {
            const groupedItems = transformSeatsToGroupedItems(selectedSeats);
            const totalAmount = calculateTotalAmount(groupedItems);

            console.log('Grouped items:', groupedItems);
            console.log('Total amount calculated:', totalAmount);

            // Ensure email is always provided (use user's email or default)
            const user = {
                email: 'user@example.com', // You should replace this with actual user email
            };

            const requestPayload: PaymentRequestPayload = {
                email: user.email,
                amount: totalAmount,
                grouped_items: groupedItems,
            };

            console.log(
                'Sending payment request with payload:',
                requestPayload,
            );

            // Get the CSRF token directly from the page
            const csrfToken = document
                .querySelector('meta[name="csrf-token"]')
                ?.getAttribute('content');

            // Create a form and submit it (this avoids AJAX and CSRF issues)
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = '/payment/charge';
            form.style.display = 'none';

            // Add CSRF token
            const csrfInput = document.createElement('input');
            csrfInput.type = 'hidden';
            csrfInput.name = '_token';
            csrfInput.value = csrfToken || '';
            form.appendChild(csrfInput);

            // Add payload data
            const emailInput = document.createElement('input');
            emailInput.type = 'hidden';
            emailInput.name = 'email';
            emailInput.value = requestPayload.email;
            form.appendChild(emailInput);

            const amountInput = document.createElement('input');
            amountInput.type = 'hidden';
            amountInput.name = 'amount';
            amountInput.value = requestPayload.amount.toString();
            form.appendChild(amountInput);

            const groupedItemsInput = document.createElement('input');
            groupedItemsInput.type = 'hidden';
            groupedItemsInput.name = 'grouped_items';
            groupedItemsInput.value = JSON.stringify(
                requestPayload.grouped_items,
            );
            form.appendChild(groupedItemsInput);

            // Add the form to the body and submit it
            document.body.appendChild(form);

            // Create a target iframe to receive the response
            const targetFrame = document.createElement('iframe');
            targetFrame.name = 'payment_frame';
            targetFrame.style.display = 'none';
            document.body.appendChild(targetFrame);

            form.target = 'payment_frame';
            form.submit();

            // Listen for response from the iframe
            targetFrame.onload = () => {
                try {
                    // Try to access the iframe content
                    const frameContent =
                        targetFrame.contentWindow?.document.body.innerHTML;

                    if (frameContent) {
                        // Try to parse the JSON response
                        try {
                            const responseData = JSON.parse(frameContent);
                            console.log(
                                'Payment response received:',
                                responseData,
                            );

                            if (responseData.snap_token) {
                                if (window.snap) {
                                    const midtransCallbacks: MidtransCallbacks =
                                        {
                                            onSuccess: () => {
                                                console.log(
                                                    'Payment successful',
                                                );
                                                alert('Payment successful!');
                                                window.location.reload();
                                            },
                                            onPending: () => {
                                                console.log('Payment pending');
                                                alert(
                                                    'Payment is pending. Please complete your payment.',
                                                );
                                                setIsLoading(false);
                                            },
                                            onError: (error) => {
                                                console.error(
                                                    'Payment failed:',
                                                    error,
                                                );
                                                alert(
                                                    'Payment failed. Please try again.',
                                                );
                                                setIsLoading(false);
                                            },
                                            onClose: () => {
                                                console.log(
                                                    'Payment window closed',
                                                );
                                                setIsLoading(false);
                                            },
                                        };
                                    window.snap.pay(
                                        responseData.snap_token,
                                        midtransCallbacks,
                                    );
                                } else {
                                    setError(
                                        'Snap.js is not loaded. Please refresh and try again.',
                                    );
                                    setIsLoading(false);
                                }
                            } else {
                                setError('No snap token received from server');
                                setIsLoading(false);
                            }
                        } catch (e) {
                            // Couldn't parse as JSON, might be an error page
                            console.error(
                                'Could not parse response:',
                                frameContent,
                            );
                            setError('Received invalid response from server');
                            setIsLoading(false);
                        }
                    } else {
                        setError('Empty response received');
                        setIsLoading(false);
                    }
                } catch (e) {
                    // Security error - can't access iframe content due to same-origin policy
                    console.error('Could not access iframe content:', e);
                    setError('Could not process the payment response');
                    setIsLoading(false);
                }

                // Clean up
                setTimeout(() => {
                    document.body.removeChild(form);
                    document.body.removeChild(targetFrame);
                }, 1000);
            };
        } catch (error) {
            setIsLoading(false);
            console.error('Unexpected error:', error);
            setError('An unexpected error occurred');
            alert('An unexpected error occurred. Please try again.');
        }
    };

    return (
        <div>
            {error && (
                <div className="mb-4 rounded-md bg-red-50 p-3 text-red-600">
                    {error}
                </div>
            )}
            <button
                className="mt-4 rounded bg-green-500 px-4 py-2 text-white hover:bg-green-600 disabled:opacity-50"
                disabled={isLoading || selectedSeats.length === 0}
                onClick={handleProceedTransaction}
            >
                {isLoading ? 'Processing...' : 'Proceed Transaction'}
            </button>
        </div>
    );
};

export default ProceedTransactionButton;
