import Toaster from '@/Components/novatix/Toaster'; // Import the Toaster component
import useToaster from '@/hooks/useToaster';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import ProceedTransactionButton from '@/Pages/Seat/components/ProceedTransactionButton';
import SeatMapDisplay from '@/Pages/Seat/SeatMapDisplay';
import { Layout, SeatItem } from '@/Pages/Seat/types';
import { EventProps } from '@/types/front-end';
import { Head } from '@inertiajs/react';
import { useEffect, useMemo, useState } from 'react';
interface Venue {
    venue_id: string;
    name: string;
}

declare global {
    interface Window {
        snap?: {
            pay: (token: string, options: any) => void;
        };
        eventTimelines?: Timeline[];
    }
}

interface Event {
    event_id: string;
    name: string;
    event_date: string;
    venue_id: string;
    status: string; // Tambahkan ini
}

interface Timeline {
    timeline_id: string;
    name: string;
    start_date: string;
    end_date: string;
}

interface TicketCategory {
    ticket_category_id: string;
    name: string;
    color: string;
}

interface CategoryPrice {
    timebound_price_id: string;
    ticket_category_id: string;
    timeline_id: string;
    price: number;
}

interface Props {
    client: string;
    layout: Layout;
    event: Event;
    venue: Venue;
    ticketCategories: TicketCategory[];
    currentTimeline?: Timeline;
    categoryPrices?: CategoryPrice[];
    error?: string;
    props: EventProps;
}

export default function Landing({
    client,
    layout,
    event,
    venue,
    ticketCategories = [],
    currentTimeline,
    categoryPrices = [],
    error,
    props,
}: Props) {
    const [selectedSeats, setSelectedSeats] = useState<SeatItem[]>([]);
    const { toasterState, showSuccess, showError, hideToaster } = useToaster();
    const [pendingTransactionSeats, setPendingTransactionSeats] = useState<
        SeatItem[]
    >([]);
    const [isLoadingTransactions, setIsLoadingTransactions] = useState(false);

    // Show error if it exists when component mounts
    useEffect(() => {
        if (error) {
            showError(error);
        }
    }, [error, showError]);

    useEffect(() => {
        // First check localStorage as before
        const savedTransaction = localStorage.getItem('pendingTransaction');
        if (savedTransaction) {
            try {
                const parsed = JSON.parse(savedTransaction);
                if (parsed.seats && Array.isArray(parsed.seats)) {
                    setPendingTransactionSeats(parsed.seats);
                }
            } catch (e) {
                console.error('Failed to parse saved transaction', e);
                localStorage.removeItem('pendingTransaction');
            }
        }

        // Then fetch actual pending transactions from the server
        fetchPendingTransactions();
    }, []);

    const fetchPendingTransactions = async () => {
        setIsLoadingTransactions(true);
        try {
            const response = await fetch('/api/pending-transactions');
            if (!response.ok) {
                throw new Error('Failed to fetch pending transactions');
            }
            const data = await response.json();

            if (data.success && data.pendingTransactions.length > 0) {
                // Get the first pending transaction's seats
                const pendingSeats = data.pendingTransactions[0].seats;
                setPendingTransactionSeats(pendingSeats);

                // Store the transaction ID for resuming payment
                if (pendingSeats.length > 0) {
                    localStorage.setItem(
                        'pendingTransaction',
                        JSON.stringify({
                            transactionInfo: {
                                transaction_id:
                                    data.pendingTransactions[0].order_code,
                                // We'll need to request a new snap token when resuming
                            },
                            seats: pendingSeats,
                        }),
                    );
                }
            }
        } catch (error) {
            console.error('Error fetching pending transactions:', error);
        } finally {
            setIsLoadingTransactions(false);
        }
    };

    const markSeatsAsPendingTransaction = (seats: SeatItem[]) => {
        setPendingTransactionSeats(seats);
    };

    // Tentukan apakah booking diperbolehkan berdasarkan status event
    const isBookingAllowed = useMemo(() => {
        return event && event.status === 'active';
    }, [event]);

    // Tambahkan pesan status yang akan ditampilkan jika booking tidak diizinkan
    const eventStatusMessage = useMemo(() => {
        if (!event) return '';

        switch (event.status) {
            case 'planned':
                return 'This event is not yet ready for booking';
            case 'completed':
                return 'This event does not accept booking anymore';
            case 'cancelled':
                return 'This event has been cancelled';
            default:
                return '';
        }
    }, [event]);

    // Extract ticket types from categories
    const ticketTypes = useMemo(
        () =>
            ticketCategories.length > 0
                ? ticketCategories.map((cat) => cat.name)
                : ['standard', 'VIP', 'Regular'],
        [ticketCategories],
    );

    // Create a map of ticket types to colors for display
    const ticketTypeColors: Record<string, string> = useMemo(() => {
        const colors: Record<string, string> = {};

        // Jika ada kategori tiket dengan warna, gunakan itu
        if (ticketCategories.length > 0) {
            ticketCategories.forEach((category) => {
                // Gunakan nilai hex langsung dari database
                colors[category.name] = category.color;
            });
        } else {
            // Fallback colors dalam hex
            colors['VIP'] = '#FFD54F'; // Kuning
            colors['standard'] = '#90CAF9'; // Biru
            colors['Regular'] = '#A5D6A7'; // Hijau
        }

        return colors;
    }, [ticketCategories]);

    // Function to get price for a seat based on its category and current timeline
    const getSeatPrice = (seat: SeatItem): number => {
        if (!seat.ticket_type || !currentTimeline) {
            return typeof seat.price === 'number'
                ? seat.price
                : parseFloat(seat.price as string) || 0;
        }

        // Find the ticket category ID for this seat's ticket type
        const category = ticketCategories.find(
            (cat) => cat.name === seat.ticket_type,
        );
        if (!category) {
            return typeof seat.price === 'number'
                ? seat.price
                : parseFloat(seat.price as string) || 0;
        }

        // Find the price for this category in the current timeline
        const priceEntry = categoryPrices.find(
            (p) =>
                p.ticket_category_id === category.ticket_category_id &&
                p.timeline_id === currentTimeline.timeline_id,
        );

        if (priceEntry) {
            return priceEntry.price;
        }

        return typeof seat.price === 'number'
            ? seat.price
            : parseFloat(seat.price as string) || 0;
    };

    const handleSeatClick = (seat: SeatItem) => {
        if (!isBookingAllowed) {
            showError('Booking is not allowed at this time');
            return;
        }

        if (seat.status !== 'available') {
            showError('This seat is not available');
            return;
        }

        const exists = selectedSeats.find((s) => s.seat_id === seat.seat_id);
        if (exists) {
            setSelectedSeats(
                selectedSeats.filter((s) => s.seat_id !== seat.seat_id),
            );
            showSuccess(`Seat ${seat.seat_number} removed from selection`);
        } else {
            // Use the dynamic ticket limit from props instead of hardcoded 5
            const ticketLimit = props.ticket_limit || 5; // Fallback to 5 if not set

            if (selectedSeats.length < ticketLimit) {
                // Calculate correct price based on category and timeline
                const updatedSeat = {
                    ...seat,
                    price: getSeatPrice(seat),
                };
                setSelectedSeats([...selectedSeats, updatedSeat]);
                showSuccess(`Seat ${seat.seat_number} added to selection`);
            } else {
                showError(`You can only select up to ${ticketLimit} seats`);
            }
        }
    };

    // Fixed price conversion function that preserves decimal places correctly
    const getSafePrice = (price: string | number | undefined): number => {
        if (price === undefined || price === null) return 0;

        // If it's already a number, return it directly
        if (typeof price === 'number') {
            return price;
        }

        if (typeof price === 'string') {
            // Remove currency symbol, spaces, and non-numeric characters except decimals and commas
            let cleaned = price.replace(/[^0-9,.]/g, '');

            // Handle Indonesian number format: convert "200.000,00" to "200000.00"
            if (cleaned.includes(',') && cleaned.includes('.')) {
                // This is likely Indonesian format with period as thousand separator
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

    // Format currency with proper Indonesian formatting without multiplying the value
    const formatRupiah = (value: number): string => {
        if (isNaN(value) || value === null || value === undefined) {
            return 'Rp 0,00';
        }

        // Format with Indonesian locale (uses period for thousands, comma for decimal)
        return new Intl.NumberFormat('id-ID', {
            style: 'currency',
            currency: 'IDR',
            minimumFractionDigits: 2,
            maximumFractionDigits: 2,
        }).format(value);
    };

    const resumeTransaction = async (transactionId: string) => {
        try {
            const response = await fetch('/payment/resume', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN':
                        document
                            .querySelector('meta[name="csrf-token"]')
                            ?.getAttribute('content') || '',
                },
                body: JSON.stringify({ transaction_id: transactionId }),
            });

            if (!response.ok) {
                throw new Error(
                    `Status: ${response.status}, Status Text: ${response.statusText}`,
                );
            }

            const data = await response.json();
            if (data.snap_token) {
                // Open Midtrans payment window
                if (window.snap) {
                    const callbacks = {
                        onSuccess: () => {
                            showSuccess('Payment successful!');
                            localStorage.removeItem('pendingTransaction');
                            setPendingTransactionSeats([]);
                            window.location.reload();
                        },
                        onPending: () => {
                            showSuccess(
                                'Your payment is pending. Please complete the payment.',
                            );
                        },
                        onError: () => {
                            showError('Payment failed. Please try again.');
                        },
                        onClose: () => {
                            showError(
                                'Payment window closed. You can resume your payment later.',
                            );
                        },
                    };

                    window.snap.pay(data.snap_token, callbacks);
                } else {
                    showError(
                        'Payment system not loaded. Please refresh the page and try again.',
                    );
                }
            } else {
                throw new Error('Invalid response from payment server');
            }
        } catch (error) {
            console.error('Error resuming transaction:', error);
            showError('Failed to resume payment. Please try again.');
        }
    };

    // Calculate prices using useMemo to avoid recalculating on every render
    const { subtotal, taxAmount, total } = useMemo(() => {
        // Calculate subtotal
        const subtotal = selectedSeats.reduce((acc, seat) => {
            const seatPrice = getSafePrice(seat.price);
            return acc + seatPrice;
        }, 0);

        // Calculate tax
        const taxRate = 1; // 1%
        const taxAmount = (subtotal * taxRate) / 100;

        // Calculate total
        const total = subtotal + taxAmount;

        return { subtotal, taxAmount, total };
    }, [selectedSeats]);

    // Status legend
    const statusLegends = [
        { label: 'Booked', color: 'bg-red-500' },
        { label: 'In Transaction', color: 'bg-yellow-500' },
        { label: 'Reserved', color: 'bg-gray-400' },
    ];

    // If we have an error, show it
    if (error) {
        return (
            <AuthenticatedLayout client={client} props={props}>
                <Head title="Error" />
                <div className="py-8">
                    <div className="mx-auto max-w-7xl sm:px-6 lg:px-8">
                        <div
                            className="overflow-hidden shadow-sm sm:rounded-lg"
                            style={{
                                backgroundColor: props.primary_color,
                            }}
                        >
                            <div className="p-6 text-gray-900">
                                <h1 className="text-2xl font-bold text-red-600">
                                    Error
                                </h1>
                                <p className="mt-4">{error}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </AuthenticatedLayout>
        );
    }

    return (
        <AuthenticatedLayout client={client} props={props}>
            <Head title="Book Tickets" />
            {/* Tampilkan pesan status event jika tidak active */}
            {!isBookingAllowed && event && (
                <div className="py-2">
                    <div className="mx-auto w-full sm:px-6 lg:px-8">
                        <div className="overflow-hidden bg-yellow-100 p-4 shadow-md sm:rounded-lg">
                            <p className="text-center font-medium text-yellow-800">
                                {eventStatusMessage}
                            </p>
                        </div>
                    </div>
                </div>
            )}
            {/* <div className="py-8">
                <div className="mx-auto max-w-7xl sm:px-6 lg:px-8">
                    <div
                        className="overflow-hidden shadow-sm sm:rounded-lg"
                        style={{
                            backgroundColor: props.primary_color,
                        }}
                    >
                        <div
                            className="p-6"
                            style={{
                                color: props.text_primary_color,
                            }}
                        >
                            {event && venue ? (
                                <>
                                    <h1 className="text-2xl font-bold">
                                        {event.name}
                                    </h1>
                                    <p
                                        className="text-lg"
                                        style={{
                                            color: props.text_secondary_color,
                                        }}
                                    >
                                        Venue: {venue.name}
                                    </p>
                                    <p
                                        className=""
                                        style={{
                                            color: props.text_secondary_color,
                                        }}
                                    >
                                        Date:{' '}
                                        {new Date(
                                            event.event_date,
                                        ).toLocaleDateString()}
                                    </p>
                                </>
                            ) : (
                                <h1 className="text-2xl font-bold">
                                    {(client ? client + ' : ' : '') +
                                        'Buy Tickets Here'}
                                </h1>
                            )}
                        </div>
                    </div>
                </div>
            </div> */}

            {/* Timeline Information */}
            {/* {currentTimeline && (
                <div className="py-2">
                    <div className="mx-auto w-full sm:px-6 lg:px-8">
                        <div
                            className="overflow-hidden p-4 shadow-md sm:rounded-lg"
                            style={{
                                backgroundColor: props.primary_color,
                                color: props.text_primary_color,
                            }}
                        >
                            <h3 className="mb-3 text-xl font-semibold">
                                Current Ticket Period
                            </h3>
                            <div className="rounded-lg bg-blue-50 p-4 text-blue-800">
                                <div className="text-lg font-medium">
                                    {currentTimeline.name}
                                </div>
                                <div className="text-blue-600">
                                    {new Date(
                                        currentTimeline.start_date,
                                    ).toLocaleDateString()}{' '}
                                    -{' '}
                                    {new Date(
                                        currentTimeline.end_date,
                                    ).toLocaleDateString()}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            )} */}

            <div className="py-4">
                <div className="mx-auto w-full sm:px-6 lg:px-8">
                    <div
                        className="overflow-hidden p-6 shadow-xl sm:rounded-lg"
                        style={{
                            backgroundColor: props.primary_color,
                            color: props.text_primary_color,
                        }}
                    >
                        {/* Three-column grid for A, B, C sections */}
                        <div className="mb-6 grid grid-cols-1 gap-6 md:grid-cols-3">
                            {/* Column A: Event Info */}
                            <div
                                className="flex flex-col justify-start overflow-hidden rounded-xl bg-gradient-to-br p-0 shadow-lg"
                                style={{
                                    backgroundColor: props.secondary_color,
                                    borderLeft: `4px solid ${props.primary_color}`,
                                }}
                            >
                                <div className="p-4">
                                    {event && venue ? (
                                        <>
                                            <h2
                                                className="text-2xl font-bold"
                                                style={{
                                                    color: props.text_primary_color,
                                                }}
                                            >
                                                {event.name}
                                            </h2>
                                            <div className="mt-3 space-y-2">
                                                <div className="flex items-center">
                                                    <svg
                                                        className="mr-2 h-5 w-5"
                                                        fill="none"
                                                        stroke="currentColor"
                                                        viewBox="0 0 24 24"
                                                        xmlns="http://www.w3.org/2000/svg"
                                                        style={{
                                                            color: props.text_secondary_color,
                                                        }}
                                                    >
                                                        <path
                                                            strokeLinecap="round"
                                                            strokeLinejoin="round"
                                                            strokeWidth={2}
                                                            d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"
                                                        />
                                                        <path
                                                            strokeLinecap="round"
                                                            strokeLinejoin="round"
                                                            strokeWidth={2}
                                                            d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"
                                                        />
                                                    </svg>
                                                    <p
                                                        style={{
                                                            color: props.text_secondary_color,
                                                        }}
                                                    >
                                                        <span className="font-medium">
                                                            Venue:
                                                        </span>{' '}
                                                        {venue.name}
                                                    </p>
                                                </div>
                                                <div className="flex items-center">
                                                    <svg
                                                        className="mr-2 h-5 w-5"
                                                        fill="none"
                                                        stroke="currentColor"
                                                        viewBox="0 0 24 24"
                                                        xmlns="http://www.w3.org/2000/svg"
                                                        style={{
                                                            color: props.text_secondary_color,
                                                        }}
                                                    >
                                                        <path
                                                            strokeLinecap="round"
                                                            strokeLinejoin="round"
                                                            strokeWidth={2}
                                                            d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"
                                                        />
                                                    </svg>
                                                    <p
                                                        style={{
                                                            color: props.text_secondary_color,
                                                        }}
                                                    >
                                                        <span className="font-medium">
                                                            D-Day:
                                                        </span>{' '}
                                                        {new Date(
                                                            event.event_date,
                                                        ).toLocaleDateString(
                                                            'en-US',
                                                            {
                                                                weekday:
                                                                    'short',
                                                                year: 'numeric',
                                                                month: 'short',
                                                                day: 'numeric',
                                                            },
                                                        )}
                                                    </p>
                                                </div>
                                            </div>

                                            {/* Additional content for section A */}
                                            <div className="mt-4 border-t border-gray-200 pt-3">
                                                <div className="flex items-center">
                                                    <svg
                                                        className="mr-2 h-5 w-5"
                                                        fill="none"
                                                        stroke="currentColor"
                                                        viewBox="0 0 24 24"
                                                        xmlns="http://www.w3.org/2000/svg"
                                                        style={{
                                                            color: props.text_secondary_color,
                                                        }}
                                                    >
                                                        <path
                                                            strokeLinecap="round"
                                                            strokeLinejoin="round"
                                                            strokeWidth={2}
                                                            d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z"
                                                        />
                                                    </svg>
                                                    <div>
                                                        <p
                                                            className="font-medium"
                                                            style={{
                                                                color: props.text_primary_color,
                                                            }}
                                                        >
                                                            Booking Status
                                                        </p>
                                                        <p
                                                            className="mt-1 text-sm"
                                                            style={{
                                                                color: props.text_secondary_color,
                                                            }}
                                                        >
                                                            {isBookingAllowed
                                                                ? 'Tickets are available for booking'
                                                                : 'Booking is currently unavailable'}
                                                        </p>
                                                    </div>
                                                </div>

                                                <div className="mt-3 flex items-center">
                                                    <svg
                                                        className="mr-2 h-5 w-5"
                                                        fill="none"
                                                        stroke="currentColor"
                                                        viewBox="0 0 24 24"
                                                        xmlns="http://www.w3.org/2000/svg"
                                                        style={{
                                                            color: props.text_secondary_color,
                                                        }}
                                                    >
                                                        <path
                                                            strokeLinecap="round"
                                                            strokeLinejoin="round"
                                                            strokeWidth={2}
                                                            d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2z"
                                                        />
                                                    </svg>
                                                    <div>
                                                        <p
                                                            className="font-medium"
                                                            style={{
                                                                color: props.text_primary_color,
                                                            }}
                                                        >
                                                            Seat Selection
                                                        </p>
                                                        <p
                                                            className="mt-1 text-sm"
                                                            style={{
                                                                color: props.text_secondary_color,
                                                            }}
                                                        >
                                                            You can select up to{' '}
                                                            {props.ticket_limit ||
                                                                5}{' '}
                                                            seats
                                                        </p>
                                                    </div>
                                                </div>
                                            </div>
                                        </>
                                    ) : (
                                        <h2 className="text-xl font-bold">
                                            {(client ? client + ' : ' : '') +
                                                'Buy Tickets Here'}
                                        </h2>
                                    )}
                                </div>
                            </div>

                            {/* Column B: Timeline and Status */}
                            <div
                                className="flex flex-col overflow-hidden rounded-xl bg-gradient-to-br p-0 shadow-lg"
                                style={{
                                    backgroundColor: props.secondary_color,
                                    borderTop: `4px solid ${props.primary_color}`,
                                }}
                            >
                                <div className="w-full p-4">
                                    {/* Timeline section */}
                                    {currentTimeline && (
                                        <div className="mb-4">
                                            <h3
                                                className="mb-2 text-center text-xl font-semibold"
                                                style={{
                                                    color: props.text_primary_color,
                                                }}
                                            >
                                                Event Period
                                            </h3>
                                            <div
                                                className="rounded-lg p-2.5"
                                                style={{
                                                    backgroundColor:
                                                        'rgba(59, 130, 246, 0.1)',
                                                }}
                                            >
                                                <div className="text-center font-semibold text-blue-600">
                                                    {currentTimeline.name}
                                                </div>
                                                <div className="mt-1 flex items-center justify-center text-sm text-blue-500">
                                                    <svg
                                                        className="mr-1 h-4 w-4"
                                                        fill="none"
                                                        stroke="currentColor"
                                                        viewBox="0 0 24 24"
                                                        xmlns="http://www.w3.org/2000/svg"
                                                    >
                                                        <path
                                                            strokeLinecap="round"
                                                            strokeLinejoin="round"
                                                            strokeWidth={2}
                                                            d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"
                                                        />
                                                    </svg>
                                                    {new Date(
                                                        currentTimeline.start_date,
                                                    ).toLocaleDateString()}{' '}
                                                    -{' '}
                                                    {new Date(
                                                        currentTimeline.end_date,
                                                    ).toLocaleDateString()}
                                                </div>
                                            </div>
                                        </div>
                                    )}

                                    {/* Status section */}
                                    <div className="mt-2">
                                        <h3
                                            className="mb-3 text-center text-xl font-semibold"
                                            style={{
                                                color: props.text_primary_color,
                                            }}
                                        >
                                            Event Status
                                        </h3>
                                        {event && (
                                            <div
                                                className="mb-4 flex w-full items-center justify-center rounded-lg bg-opacity-50 p-3"
                                                style={{
                                                    backgroundColor:
                                                        event.status ===
                                                        'active'
                                                            ? 'rgba(34, 197, 94, 0.1)'
                                                            : event.status ===
                                                                'planned'
                                                              ? 'rgba(59, 130, 246, 0.1)'
                                                              : event.status ===
                                                                  'completed'
                                                                ? 'rgba(107, 114, 128, 0.1)'
                                                                : 'rgba(239, 68, 68, 0.1)',
                                                }}
                                            >
                                                <div
                                                    className={`h-5 w-5 rounded-full ${
                                                        event.status ===
                                                        'active'
                                                            ? 'bg-green-500'
                                                            : event.status ===
                                                                'planned'
                                                              ? 'bg-blue-500'
                                                              : event.status ===
                                                                  'completed'
                                                                ? 'bg-gray-500'
                                                                : 'bg-red-500'
                                                    } mr-2 animate-pulse`}
                                                ></div>
                                                <span
                                                    className="font-medium"
                                                    style={{
                                                        color:
                                                            event.status ===
                                                            'active'
                                                                ? '#16a34a'
                                                                : event.status ===
                                                                    'planned'
                                                                  ? '#2563eb'
                                                                  : event.status ===
                                                                      'completed'
                                                                    ? '#4b5563'
                                                                    : '#dc2626',
                                                    }}
                                                >
                                                    {event.status === 'active'
                                                        ? 'Active'
                                                        : event.status ===
                                                            'planned'
                                                          ? 'Planned'
                                                          : event.status ===
                                                              'completed'
                                                            ? 'Completed'
                                                            : 'Cancelled'}
                                                </span>
                                            </div>
                                        )}

                                        {/* Add the status legends */}
                                        <div className="mt-1 flex w-full justify-around p-2">
                                            {statusLegends.map((legend, i) => (
                                                <div
                                                    key={i}
                                                    className="flex items-center"
                                                >
                                                    <div
                                                        className={`h-6 w-6 ${legend.color} mr-1.5 rounded-full`}
                                                    ></div>
                                                    <span
                                                        className="text-sm"
                                                        style={{
                                                            color: props.text_secondary_color,
                                                        }}
                                                    >
                                                        {legend.label}
                                                    </span>
                                                </div>
                                            ))}
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {/* Column C: Ticket Categories */}
                            <div
                                className="flex flex-col justify-start overflow-hidden rounded-xl bg-gradient-to-br p-0 shadow-lg"
                                style={{
                                    backgroundColor: props.secondary_color,
                                    borderRight: `4px solid ${props.primary_color}`,
                                }}
                            >
                                <div className="p-4">
                                    {/* Ticket Categories with Prices */}
                                    <h3
                                        className="mb-3 text-center text-xl font-semibold"
                                        style={{
                                            color: props.text_primary_color,
                                        }}
                                    >
                                        Category & Price
                                    </h3>
                                    <div className="grid grid-cols-2 gap-2">
                                        {ticketTypes.map((type) => {
                                            // Find the category and price information
                                            const category =
                                                ticketCategories.find(
                                                    (cat) => cat.name === type,
                                                );
                                            let price = 0;

                                            if (category && currentTimeline) {
                                                const priceEntry =
                                                    categoryPrices.find(
                                                        (p) =>
                                                            p.ticket_category_id ===
                                                                category.ticket_category_id &&
                                                            p.timeline_id ===
                                                                currentTimeline.timeline_id,
                                                    );
                                                if (priceEntry) {
                                                    price = priceEntry.price;
                                                }
                                            }

                                            return (
                                                <div
                                                    key={type}
                                                    className="flex flex-col rounded-lg p-2 shadow-sm"
                                                    style={{
                                                        backgroundColor: `${ticketTypeColors[type]}20`,
                                                        borderLeft: `3px solid ${ticketTypeColors[type]}`,
                                                    }}
                                                >
                                                    <div className="flex items-center">
                                                        <div
                                                            className="mr-2 h-4 w-4 rounded-full"
                                                            style={{
                                                                backgroundColor:
                                                                    ticketTypeColors[
                                                                        type
                                                                    ],
                                                            }}
                                                        ></div>
                                                        <span
                                                            className="text-sm font-medium"
                                                            style={{
                                                                color: props.text_secondary_color,
                                                            }}
                                                        >
                                                            {type
                                                                .charAt(0)
                                                                .toUpperCase() +
                                                                type.slice(1)}
                                                        </span>
                                                    </div>
                                                    <div
                                                        className="ml-6 text-sm font-bold"
                                                        style={{
                                                            color: props.text_primary_color,
                                                        }}
                                                    >
                                                        {formatRupiah(price)}
                                                    </div>
                                                </div>
                                            );
                                        })}
                                    </div>

                                    {/* Extra description about ticket types */}
                                    {/* <div className="mt-4 rounded-lg bg-blue-50 p-3 text-sm text-blue-800">
                                        <div className="mb-1 font-medium">
                                            Important Information
                                        </div>
                                        <ul className="list-disc space-y-1 pl-5">
                                            <li>
                                                Prices may vary based on current
                                                sales period
                                            </li>
                                            <li>
                                                One person can purchase up to 5
                                                tickets
                                            </li>
                                            <li>
                                                All ticket sales are final - no
                                                refunds
                                            </li>
                                        </ul>
                                    </div> */}
                                </div>
                            </div>
                        </div>

                        {/* Show event status message if not active */}
                        {/* {!isBookingAllowed && event && (
                            <div className="mb-4 overflow-hidden bg-yellow-100 p-3 shadow-md sm:rounded-lg">
                                <p className="text-center font-medium text-yellow-800">
                                    {eventStatusMessage}
                                </p>
                            </div>
                        )} */}

                        {/* Seat Map Section - takes up more vertical space */}
                        <div className="mt-2">
                            <h3 className="mb-2 text-center text-xl font-bold">
                                Seat Map
                            </h3>
                            <div
                                className="rounded-lg border p-2"
                                style={{
                                    backgroundColor: props.secondary_color,
                                    height: '60vh', // Set a fixed height to take up ~60% of viewport height
                                }}
                            >
                                <div className="flex h-full justify-center overflow-auto">
                                    <SeatMapDisplay
                                        config={layout}
                                        props={props}
                                        onSeatClick={handleSeatClick}
                                        selectedSeats={selectedSeats}
                                        ticketTypeColors={ticketTypeColors}
                                        eventStatus={event?.status}
                                    />
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {/* Keep the selected seats section below */}
            <div className="py-4">
                <div className="mx-auto w-full sm:px-6 lg:px-8">
                    <div
                        className="overflow-hidden p-6 shadow-xl sm:rounded-lg"
                        style={{
                            backgroundColor: props.primary_color,
                            color: props.text_primary_color,
                        }}
                    >
                        <h3 className="mb-4 text-xl font-semibold">
                            Selected Seats
                        </h3>
                        {selectedSeats.length === 0 ? (
                            <p>No seats selected.</p>
                        ) : (
                            <div className="space-y-4">
                                {selectedSeats.map((seat) => (
                                    <div
                                        key={seat.seat_id}
                                        className="flex items-center justify-between rounded-lg p-3"
                                        style={{
                                            backgroundColor:
                                                props.secondary_color,
                                            color: props.text_secondary_color,
                                        }}
                                    >
                                        <div>
                                            <p className="font-semibold">
                                                Ticket Type:{' '}
                                                {seat.ticket_type ||
                                                    seat.category ||
                                                    'Standard'}
                                            </p>
                                            <p className="text-sm">
                                                Seat: {seat.seat_number}
                                            </p>
                                        </div>
                                        <div>
                                            <p className="font-semibold">
                                                Price:{' '}
                                                {formatRupiah(
                                                    getSafePrice(seat.price),
                                                )}
                                            </p>
                                        </div>
                                    </div>
                                ))}
                            </div>
                        )}

                        {/* Subtotal, Tax, and Total */}
                        {selectedSeats.length > 0 && (
                            <div
                                className="mt-6 space-y-2 rounded-lg p-4"
                                style={{
                                    backgroundColor: props.secondary_color,
                                    color: props.text_secondary_color,
                                }}
                            >
                                <div className="flex justify-between">
                                    <span className="font-medium">
                                        Subtotal:
                                    </span>
                                    <span>{formatRupiah(subtotal)}</span>
                                </div>
                                <div className="flex justify-between">
                                    <span className="font-medium">
                                        Tax (1%):
                                    </span>
                                    <span>{formatRupiah(taxAmount)}</span>
                                </div>
                                <div className="flex justify-between text-lg font-semibold">
                                    <span>Total:</span>
                                    <span>{formatRupiah(total)}</span>
                                </div>
                            </div>
                        )}

                        {/* Proceed Button */}
                        {selectedSeats.length > 0 && isBookingAllowed && (
                            <ProceedTransactionButton
                                selectedSeats={selectedSeats}
                                taxAmount={taxAmount}
                                subtotal={subtotal}
                                total={total}
                                onTransactionStarted={
                                    markSeatsAsPendingTransaction
                                }
                            />
                        )}

                        {pendingTransactionSeats.length > 0 && (
                            <div className="mt-4 rounded-lg bg-yellow-50 p-3">
                                <h4 className="font-medium text-yellow-800">
                                    Pending Transaction
                                </h4>
                                <p className="text-sm text-yellow-600">
                                    You have a payment in progress for the
                                    following seats:
                                </p>
                                <div className="mt-2">
                                    {pendingTransactionSeats.map((seat) => (
                                        <span
                                            key={seat.seat_id}
                                            className="mr-2 rounded bg-yellow-200 px-2 py-1 text-xs"
                                        >
                                            {seat.seat_number}
                                        </span>
                                    ))}
                                </div>
                                <p className="mt-2 text-sm text-yellow-700">
                                    Please complete your payment to secure these
                                    seats.
                                </p>
                                {/* Resume Payment Button */}
                                <button
                                    className="mt-3 rounded bg-blue-500 px-4 py-2 text-white hover:bg-blue-600"
                                    onClick={() => {
                                        const savedTransaction =
                                            localStorage.getItem(
                                                'pendingTransaction',
                                            );
                                        if (savedTransaction) {
                                            try {
                                                const parsed =
                                                    JSON.parse(
                                                        savedTransaction,
                                                    );
                                                if (
                                                    parsed.transactionInfo &&
                                                    parsed.transactionInfo
                                                        .transaction_id
                                                ) {
                                                    resumeTransaction(
                                                        parsed.transactionInfo
                                                            .transaction_id,
                                                    );
                                                }
                                            } catch (e) {
                                                console.error(
                                                    'Failed to parse transaction info',
                                                    e,
                                                );
                                                showError(
                                                    'Failed to resume payment. Please try again.',
                                                );
                                            }
                                        }
                                    }}
                                >
                                    Resume Payment
                                </button>
                            </div>
                        )}
                    </div>
                </div>
            </div>
            <Toaster
                message={toasterState.message}
                type={toasterState.type}
                isVisible={toasterState.isVisible}
                onClose={hideToaster}
            />
        </AuthenticatedLayout>
    );
}
