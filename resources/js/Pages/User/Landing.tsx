import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import Toaster from '@/Components/novatix/Toaster'; // Import the Toaster component
import TextInput from '@/Components/TextInput';
import useToaster from '@/hooks/useToaster';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import ProceedTransactionButton from '@/Pages/Seat/components/ProceedTransactionButton';
import {
    LandingProps,
    MidtransCallbacks,
    PendingTransactionResponseItem,
    SeatItem,
} from '@/types/seatmap';
import { Head, router, useForm } from '@inertiajs/react';
import axios from 'axios';
import mqtt from 'mqtt';
import { useEffect, useMemo, useRef, useState } from 'react';
import Mqttclient from '../Seat/components/Mqttclient';
import SeatMapDisplay from '../Seat/SeatMapDisplay';

interface TicketUpdate {
    id: string;
    status: string;
    seat_id?: string;
    ticket_category_id?: number;
    ticket_type?: string;
}

type MerchForm = {
    user_full_name: string;
    user_id_no: string;
    user_address: string;
    user_email: string;
    user_phone_num: string;
    user_sizes: string[];
    accessor: string;
};

export default function Landing({
    appName,
    client,
    layout,
    event,
    venue,
    ticketCategories = [],
    currentTimeline,
    categoryPrices = [],
    error,
    props,
    ownedTicketCount,
    userEndSessionDatetime,
    paymentGateway,
}: LandingProps) {
    const { data, setData, errors } = useForm<MerchForm>({
        user_full_name: '',
        user_id_no: '',
        user_address: '',
        user_email: '',
        user_phone_num: '',
        user_sizes: [],
        accessor: '',
    });

    const [disabledByForm, setDisabledByForm] = useState<boolean>(false);
    const [selectedSeats, setSelectedSeats] = useState<SeatItem[]>([]);

    useEffect(() => {
        setDisabledByForm(
            selectedSeats.length > 0 &&
                (!data.user_full_name ||
                    !data.user_id_no ||
                    !data.user_phone_num ||
                    !data.user_email ||
                    (selectedSeats.filter(
                        (seat) => seat.ticket_type?.toLowerCase() === 'nobles',
                    ).length > 0 &&
                        (!data.user_address ||
                            selectedSeats
                                .filter(
                                    (seat) =>
                                        seat.ticket_type?.toLowerCase() ===
                                        'nobles',
                                )
                                .some(
                                    (seat, idx) =>
                                        !data.user_sizes ||
                                        !data.user_sizes[idx] ||
                                        data.user_sizes[idx].trim() === '',
                                )))),
        );
    }, [data, selectedSeats]);

    const { toasterState, showSuccess, showError, hideToaster } = useToaster();
    const [pendingTransactions, setPendingTransactions] = useState<
        PendingTransactionResponseItem[]
    >([]);

    // usestate untuk layout yang diterima dari mqtt
    const [layoutItems, setLayoutItems] = useState(layout?.items || []);
    const [layoutState, setLayoutState] = useState(layout);

    useEffect(() => {
        const mqttclient = mqtt.connect('wss://broker.emqx.io:8084/mqtt');

        mqttclient.on('connect', () => {
            mqttclient.subscribe('novatix/midtrans/defaultcode');
        });

        mqttclient.on('message', (topic, message) => {
            try {
                const payload = JSON.parse(message.toString());
                const updates = payload.data as TicketUpdate[];

                const updatedItems = layoutItems.map((item) => {
                    if (!('id' in item)) return item;

                    const update = updates.find(
                        (updateItem) =>
                            updateItem.seat_id?.replace(/,/g, '') === item.id,
                    );

                    if (update) {
                        return {
                            ...item,
                            status: update.status,
                        };
                    }

                    return item;
                });

                setLayoutItems(updatedItems);
                setLayoutState((prevLayout) => ({
                    ...prevLayout,
                    items: updatedItems,
                }));
            } catch (error) {
                console.error('Error parsing MQTT message:', error);
            }
        });

        return () => {
            mqttclient.end();
        };
    });

    // Show error if it exists when component mounts
    useEffect(() => {
        if (error) {
            showError(error);
        }
    }, [error, showError]);

    const refShowError = useRef(showError);
    useEffect(() => {
        const fetchPendingTransactions = async (): Promise<void> => {
            try {
                // Tambahkan timestamp untuk mencegah caching
                const response = await fetch(route('payment.pending', client));

                if (!response.ok) {
                    console.error('Server response:', await response.text());
                    throw new Error(
                        `Failed to fetch pending transactions: ${response.status} ${response.statusText}`,
                    );
                }

                const data = (await response.json()) as {
                    success: boolean;
                    pendingTransactions: PendingTransactionResponseItem[];
                };

                if (data.success && data.pendingTransactions.length > 0) {
                    // Set pending transactions
                    setPendingTransactions(data.pendingTransactions);
                }
            } catch (error) {
                console.error('Error fetching pending transactions:', error);
                refShowError.current(
                    'Failed to load pending transactions. Please refresh the page.',
                );
            }
        };

        fetchPendingTransactions();
    }, [client]);

    const [snapInitialized, setSnapInitialized] = useState<boolean>(
        paymentGateway !== 'midtrans',
    );
    // Initialize Midtrans Snap on component mount
    const showErrorRef = useRef(showError);
    const showSuccessRef = useRef(showSuccess);

    useEffect(() => {
        const fetchAndInitializeSnap = async () => {
            setSnapInitialized(false);

            let clientKey = null;
            let isProduction = null;

            try {
                const response = await axios.get('api/payment/get-client');
                clientKey = response.data.client_key;
                isProduction = response.data.is_production;
            } catch (error) {
                if (paymentGateway !== 'midtrans') return;
                console.error('Failed to fetch client key:', error);
                showErrorRef.current(
                    'Failed to fetch client key. Please try again later.',
                );
                return;
            }

            if (!clientKey) {
                if (paymentGateway !== 'midtrans') return;
                showErrorRef.current(
                    'System payment is not yet activated. Please contact admin.',
                );
                return;
            }

            showSuccessRef.current(
                'System payment has been activated. You may purchase your tickets.',
            );

            // Load Snap.js
            const snapScript = document.createElement('script');
            snapScript.src = isProduction
                ? 'https://app.midtrans.com/snap/snap.js'
                : 'https://app.sandbox.midtrans.com/snap/snap.js';
            snapScript.setAttribute('data-client-key', clientKey);
            snapScript.onload = () => {
                setSnapInitialized(true);
            };
            snapScript.onerror = () => {
                console.error('Failed to load Midtrans Snap');
                showErrorRef.current(
                    'Payment system could not be loaded. Please try again later.',
                );
            };

            document.head.appendChild(snapScript);
        };

        if (paymentGateway !== 'midtrans') {
            setSnapInitialized(true);
            return;
        } else fetchAndInitializeSnap();
    }, [paymentGateway]); // Only run once when the component mounts

    const createCallbacks = (): MidtransCallbacks => {
        return {
            onSuccess: () => {
                showSuccess('Payment successful!');
                window.location.reload();
            },
            onPending: () => {
                showSuccess(
                    'Your payment is pending. Please complete the payment.',
                );
                window.location.reload();
            },
            onError: () => {
                showError('Payment failed. Please try again.');
            },
            onClose: () => {
                showSuccess(
                    'Payment window closed. You can resume your payment using the "Resume Payment" button below.',
                );
                window.location.reload();
            },
        };
    };

    const resumePayment = async (accessor: string, payment_gateway: string) => {
        showSuccess('Preparing your payment...');

        try {
            switch (payment_gateway) {
                case 'midtrans':
                    if (!window.snap) return;
                    window.snap.pay(accessor, createCallbacks());
                    break;
                case 'faspay':
                case 'tripay':
                    window.location.href = accessor;
                    break;
                default:
                    throw new Error('Unsupported payment gateway');
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
        }
    };

    interface CancellingStack {
        order_id: string;
        cancelling: boolean;
    }
    const [cancellingStack, setCancellingStack] = useState<CancellingStack[]>(
        [],
    );

    const addItemToStack = (orderId: string) => {
        const newItem = {
            order_id: orderId,
            cancelling: true,
        };
        setCancellingStack((prevStack) => [...prevStack, newItem]);
    };

    const removeItemFromStack = (orderId: string) => {
        setCancellingStack((prevStack) =>
            prevStack.filter((item) => item.order_id !== orderId),
        );
    };

    const cancelPayment = async (order_ids: string[]) => {
        if (!order_ids) return;

        showSuccess('Cancelling your payment...');

        try {
            const response = await axios.post(route('payment.cancel', client), {
                order_ids,
            });

            if (response.data.success) {
                // logic publish
                const updated_tickets: { seat_id: string; status: string }[] =
                    [];

                for (const transaction of pendingTransactions) {
                    for (const seat of transaction.seats) {
                        updated_tickets.push({
                            seat_id: seat.seat_id,
                            status: 'available',
                        });
                    }
                }

                const message = JSON.stringify({
                    event: 'update_ticket_status',
                    data: updated_tickets,
                });

                Mqttclient.publish(
                    'novatix/midtrans/defaultcode',
                    message,
                    { qos: 1 },
                    (err) => {
                        if (err) {
                            console.error('MQTT Publish Error:', err);
                        } else {
                            // console.log('MQTT Message Sent:', message);
                        }
                    },
                );

                showSuccess('Payment cancelled successfully');
                window.location.reload();
            } else {
                showError(response.data.message || 'Failed to cancel payment');
            }
        } catch (err) {
            console.error('Failed to cancel payment:', err);

            if (axios.isAxiosError(err)) {
                const errorMsg =
                    err.response?.data?.message ||
                    'Failed to connect to payment server';
                showError(errorMsg);
            } else {
                showError('Failed to cancel payment. Please try again.');
            }
        }
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
                : ['unset'],
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
            colors['unset'] = '#FFF';
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
                p.ticket_category_id === category.id &&
                p.timeline_id === currentTimeline.id,
        );

        if (priceEntry) {
            return priceEntry.price;
        }

        return typeof seat.price === 'number'
            ? seat.price
            : parseFloat(seat.price as string) || 0;
    };

    const handleSeatClick = (seat: SeatItem) => {
        const exists = selectedSeats.find((s) => s.id === seat.id);
        if (exists) {
            setSelectedSeats(selectedSeats.filter((s) => s.id !== seat.id));
            showSuccess(`Seat ${seat.seat_number} removed from selection`);

            return;
        }

        if (!isBookingAllowed) {
            showError('Booking is not allowed at this time');
            return;
        }

        if (seat.status !== 'available') {
            showError('This seat is not available');
            return;
        }

        if (pendingTransactions.length != 0) {
            showError(
                'You have pending transactions. Please complete or cancel them first.',
            );
            return;
        }

        const selectedTicketCount = selectedSeats.length;
        const ticketLimit = props.ticket_limit || 0; // Fallback to 0 if not set
        if (selectedTicketCount + 1 + ownedTicketCount > ticketLimit) {
            showError(
                'You have reached the maximum number of tickets you can purchase',
            );
            return;
        }

        // Calculate correct price based on category and timeline
        const updatedSeat = {
            ...seat,
            price: getSeatPrice(seat),
        };
        setSelectedSeats([...selectedSeats, updatedSeat]);
        showSuccess(`Seat ${seat.seat_number} added to selection`);
    };

    const deselectAllSeats = () => {
        setSelectedSeats([]);
        showSuccess('All selected seats have been deselected');
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

    // Calculate prices using useMemo to avoid recalculating on every render
    const { subtotal, taxAmount, total } = useMemo(() => {
        // Calculate subtotal
        const subtotal = selectedSeats.reduce((acc, seat) => {
            const seatPrice = getSafePrice(seat.price);
            return acc + seatPrice;
        }, 0);

        // Calculate tax
        const taxRate = 0; // 0%
        const taxAmount = (subtotal * taxRate) / 100;

        // Calculate total
        const total = subtotal + taxAmount;

        return { subtotal, taxAmount, total };
    }, [selectedSeats]);

    // Status legend
    const statusLegends = [
        { label: 'Available', color: 'bg-white' },
        { label: 'Booked', color: 'bg-red-500' },
        { label: 'In Transaction', color: 'bg-yellow-500' },
        { label: 'Reserved', color: 'bg-gray-400' },
    ];

    if (error) {
        return (
            <AuthenticatedLayout
                appName={appName}
                client={client}
                props={props}
                userEndSessionDatetime={userEndSessionDatetime}
            >
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
                                <h1 className="text-lg font-bold text-red-600">
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
        <AuthenticatedLayout
            appName={appName}
            client={client}
            props={props}
            userEndSessionDatetime={userEndSessionDatetime}
        >
            <Head title={'Book Tickets | ' + event.name} />
            <div className="flex w-full flex-col gap-4 py-4">
                {/* Tampilkan pesan status event jika tidak active */}
                {!isBookingAllowed && event && (
                    <div className="mx-auto w-fit sm:px-6 lg:px-8">
                        <div
                            className="overflow-hidden p-3 shadow-md sm:rounded-lg"
                            style={{
                                backgroundColor: props.secondary_color,
                                color: props.text_primary_color,
                            }}
                        >
                            <p className="text-center font-medium">
                                {eventStatusMessage}
                            </p>
                        </div>
                    </div>
                )}
                <div className="mx-auto w-full max-w-7xl sm:px-6 lg:px-8">
                    <div
                        className="flex flex-col rounded-lg p-6 shadow-xl"
                        style={{
                            backgroundColor: props.primary_color,
                            color: props.text_primary_color,
                        }}
                    >
                        {/* Three-column grid for A, B, C sections */}
                        <div className="mb-6 flex w-full flex-col gap-4 md:flex-row">
                            {/* Column A: Event Info */}
                            <div
                                className="flex w-full flex-col justify-start gap-3 overflow-hidden rounded-xl bg-gradient-to-br p-3 shadow-lg md:w-[35%]"
                                style={{
                                    backgroundColor: props.secondary_color,
                                    borderRight: `4px solid ${props.primary_color}`,
                                }}
                            >
                                {event && venue ? (
                                    <>
                                        <div className="flex items-center justify-between">
                                            <h2
                                                className="text-xl font-bold"
                                                style={{
                                                    color: props.text_primary_color,
                                                }}
                                            >
                                                {event.name}
                                            </h2>
                                            {/* Status section */}
                                            <div className="w-fit">
                                                {event && (
                                                    <div
                                                        className="flex w-full items-center justify-center rounded-lg bg-opacity-50 px-2"
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
                                                            className={`h-2 w-2 rounded-full ${
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
                                                            className="text-sm font-medium"
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
                                                            {event.status ===
                                                            'active'
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
                                            </div>
                                        </div>
                                        <div className="flex grow items-start">
                                            <div className="flex w-full flex-col items-stretch gap-4">
                                                <div className="flex w-full justify-between gap-1 text-sm">
                                                    <div className="flex">
                                                        <svg
                                                            className="mr-1 mt-[2px] h-4 w-4"
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
                                                        <div className="flex flex-col">
                                                            <p
                                                                className="font-bold"
                                                                style={{
                                                                    color: props.text_secondary_color,
                                                                }}
                                                            >
                                                                Venue
                                                            </p>
                                                            <p
                                                                style={{
                                                                    color: props.text_secondary_color,
                                                                }}
                                                            >
                                                                {venue.name}
                                                            </p>
                                                        </div>
                                                    </div>
                                                    <div className="flex justify-end text-end">
                                                        <div className="flex flex-col">
                                                            <p
                                                                className="font-bold"
                                                                style={{
                                                                    color: props.text_secondary_color,
                                                                }}
                                                            >
                                                                D-Day
                                                            </p>
                                                            <p
                                                                style={{
                                                                    color: props.text_secondary_color,
                                                                }}
                                                            >
                                                                {new Date(
                                                                    event.event_date,
                                                                ).toLocaleString(
                                                                    'en-US',
                                                                    {
                                                                        weekday:
                                                                            'short',
                                                                        day: 'numeric',
                                                                        month: 'long',
                                                                        year: 'numeric',
                                                                        hour: '2-digit',
                                                                        minute: '2-digit',
                                                                        hour12: false,
                                                                    },
                                                                )}{' '}
                                                                WIB
                                                            </p>
                                                        </div>
                                                        <svg
                                                            className="ml-1 mt-[2px] h-4 w-4"
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
                                                    </div>
                                                </div>
                                                {/* Timeline section */}
                                                {currentTimeline && (
                                                    <div
                                                        className="w-full rounded-lg p-1 px-3"
                                                        style={{
                                                            backgroundColor:
                                                                'rgba(59, 130, 246, 0.1)',
                                                        }}
                                                    >
                                                        <div className="flex items-center justify-end text-sm font-semibold text-blue-600">
                                                            <p>
                                                                {
                                                                    currentTimeline.name
                                                                }
                                                            </p>
                                                            <svg
                                                                className="ml-2 h-4 w-4"
                                                                fill="none"
                                                                stroke="currentColor"
                                                                viewBox="0 0 24 24"
                                                                xmlns="http://www.w3.org/2000/svg"
                                                            >
                                                                <path
                                                                    strokeLinecap="round"
                                                                    strokeLinejoin="round"
                                                                    strokeWidth={
                                                                        2
                                                                    }
                                                                    d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"
                                                                />
                                                            </svg>
                                                        </div>
                                                        <div
                                                            className="flex w-full items-center justify-end text-xs text-blue-500"
                                                            style={{
                                                                color: props.text_secondary_color,
                                                            }}
                                                        >
                                                            {new Date(
                                                                currentTimeline.start_date,
                                                            ).toLocaleString(
                                                                'en-US',
                                                                {
                                                                    weekday:
                                                                        'short',
                                                                    day: 'numeric',
                                                                    month: 'long',
                                                                    year: 'numeric',
                                                                    hour: '2-digit',
                                                                    minute: '2-digit',
                                                                    hour12: false,
                                                                },
                                                            )}{' '}
                                                            WIB -{' '}
                                                            {new Date(
                                                                currentTimeline.end_date,
                                                            ).toLocaleString(
                                                                'en-US',
                                                                {
                                                                    weekday:
                                                                        'short',
                                                                    day: 'numeric',
                                                                    month: 'long',
                                                                    year: 'numeric',
                                                                    hour: '2-digit',
                                                                    minute: '2-digit',
                                                                    hour12: false,
                                                                },
                                                            )}{' '}
                                                            WIB
                                                        </div>
                                                    </div>
                                                )}
                                            </div>
                                        </div>

                                        {/* Additional content for section A */}
                                        <hr
                                            className="border-[1.5px]"
                                            style={{
                                                borderColor:
                                                    props.text_primary_color,
                                            }}
                                        />
                                        <div className="flex justify-between">
                                            <div className="flex items-center">
                                                <svg
                                                    className="-mb-1 -mt-1 ml-1 mr-2 h-4 w-4"
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

                                                <p
                                                    className="text-xs leading-[1.1]"
                                                    style={{
                                                        color: props.text_secondary_color,
                                                    }}
                                                >
                                                    {isBookingAllowed
                                                        ? 'Available for booking'
                                                        : 'Unavailable for booking'}
                                                </p>
                                            </div>

                                            <div className="flex items-center">
                                                <p
                                                    className="text-right text-xs leading-[1.1]"
                                                    style={{
                                                        color: props.text_secondary_color,
                                                    }}
                                                >
                                                    You can select up to{' '}
                                                    {props.ticket_limit || 0}{' '}
                                                    seats
                                                </p>

                                                <svg
                                                    className="-mb-1 -mt-1 ml-2 mr-1 h-4 w-4"
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
                                            </div>
                                        </div>
                                    </>
                                ) : (
                                    <h2 className="text-lg font-bold">
                                        {(client ? client + ' : ' : '') +
                                            'Buy Tickets Here'}
                                    </h2>
                                )}
                            </div>

                            {/* Column B: Ticket Categories */}
                            <div
                                className="flex w-full flex-col justify-start gap-3 overflow-hidden rounded-xl bg-gradient-to-br p-3 shadow-lg md:w-[65%]"
                                style={{
                                    backgroundColor: props.secondary_color,
                                    borderRight: `4px solid ${props.primary_color}`,
                                }}
                            >
                                {/* Ticket Categories with Prices */}
                                <h3
                                    className="text-center text-lg font-semibold"
                                    style={{
                                        color: props.text_primary_color,
                                    }}
                                >
                                    Category & Price
                                </h3>
                                <div className="flex flex-wrap gap-2">
                                    {ticketTypes.map((type) => {
                                        // Find the category and price information
                                        const category = ticketCategories.find(
                                            (cat) => cat.name === type,
                                        );
                                        let price = 0;

                                        if (category && currentTimeline) {
                                            const priceEntry =
                                                categoryPrices.find(
                                                    (p) =>
                                                        p.ticket_category_id ===
                                                            category.id &&
                                                        p.timeline_id ===
                                                            currentTimeline.id,
                                                );
                                            if (priceEntry) {
                                                price = priceEntry.price;
                                            }
                                        }

                                        return (
                                            <div
                                                key={type}
                                                className="flex grow rounded-lg p-3 shadow-sm"
                                                style={{
                                                    backgroundColor: `${ticketTypeColors[type]}20`,
                                                    borderLeft: `3px solid ${ticketTypeColors[type]}`,
                                                }}
                                            >
                                                <div
                                                    className="mr-2 h-4 w-4 rounded-full"
                                                    style={{
                                                        backgroundColor:
                                                            ticketTypeColors[
                                                                type
                                                            ],
                                                    }}
                                                />
                                                <div className="flex w-full flex-col gap-1">
                                                    <span
                                                        className="text-xs font-medium leading-[.8]"
                                                        style={{
                                                            color: props.text_secondary_color,
                                                        }}
                                                    >
                                                        {type
                                                            .charAt(0)
                                                            .toUpperCase() +
                                                            type.slice(1)}
                                                    </span>
                                                    <div
                                                        className="text-xs font-bold leading-[.8]"
                                                        style={{
                                                            color: props.text_primary_color,
                                                        }}
                                                    >
                                                        {formatRupiah(price)}
                                                    </div>
                                                </div>
                                            </div>
                                        );
                                    })}
                                </div>
                                <hr
                                    className="border-[1.5px]"
                                    style={{
                                        borderColor: props.text_primary_color,
                                    }}
                                />
                                {/* Add the status legends */}
                                <div className="flex w-full items-center justify-center gap-4">
                                    <p className="text-xs leading-[.8]">
                                        Border Color:{' '}
                                    </p>
                                    {statusLegends.map((legend, i) => (
                                        <div
                                            key={i}
                                            className="flex items-center"
                                        >
                                            <div
                                                className={`h-3 w-3 ${legend.color} mr-1.5 rounded-full`}
                                            ></div>
                                            <span
                                                className="text-xs leading-[.8]"
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
                        {/* Seat Map Section - takes up more vertical space */}
                        <div
                            className="relative flex h-[80vh] flex-col items-center gap-2 rounded-lg p-3"
                            style={{
                                backgroundColor: props.secondary_color,
                            }}
                        >
                            {/* Header + Button */}
                            <div className="flex w-full flex-col items-center justify-center">
                                <button
                                    className={
                                        'absolute left-4 top-4 rounded-lg bg-red-500 px-4 text-lg text-white duration-200 hover:bg-red-600 max-md:static ' +
                                        (selectedSeats.length > 0
                                            ? 'opacity-100 max-md:mt-1'
                                            : 'pointer-events-none h-0 opacity-0')
                                    }
                                    onClick={deselectAllSeats}
                                >
                                    Clear Selection
                                </button>
                                <h3 className="h-fit text-center text-lg font-bold">
                                    Seat Map
                                </h3>
                            </div>

                            {/* Scrollable Seat Map */}
                            <div className="flex h-full w-full flex-1 overflow-hidden">
                                <div className="flex w-full justify-center overflow-auto">
                                    <SeatMapDisplay
                                        config={layoutState}
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

            {/* Form Mandatory */}
            <div
                className={
                    'w-full pb-4 ' +
                    // if there exist category NOBLES in selection show, else hide
                    (selectedSeats.length > 0 ? 'block' : 'hidden')
                }
            >
                <div className="mx-auto w-full max-w-7xl sm:px-6 lg:px-8">
                    <div
                        className="overflow-hidden p-6 shadow-xl sm:rounded-lg"
                        style={{
                            backgroundColor: props.primary_color,
                            color: props.text_primary_color,
                        }}
                    >
                        <h3 className="mb-4 text-lg font-semibold">
                            Fill your details for Confirmation
                        </h3>
                        {/* Form here, just edit data, no submit */}
                        <form
                            className="space-y-4"
                            onSubmit={(e) => e.preventDefault()}
                        >
                            <div className="min-w-[250px] flex-1">
                                <InputLabel
                                    htmlFor="fullname"
                                    value="Full Name"
                                    style={{
                                        color: props.text_primary_color,
                                    }}
                                />
                                <TextInput
                                    id="fullname"
                                    className="mt-1 block w-full"
                                    value={data.user_full_name}
                                    onChange={(e) =>
                                        setData(
                                            'user_full_name',
                                            e.target.value,
                                        )
                                    }
                                    style={{
                                        color: props.text_secondary_color,
                                    }}
                                />
                                <InputError
                                    className="mt-2"
                                    message={errors.user_full_name}
                                />
                            </div>

                            <div className="min-w-[250px] flex-1">
                                <InputLabel
                                    htmlFor="user_id_no"
                                    value="ID Number (NIK/KTP/SIM)"
                                    style={{
                                        color: props.text_primary_color,
                                    }}
                                />
                                <TextInput
                                    id="user_id_no"
                                    className="mt-1 block w-full"
                                    value={data.user_id_no}
                                    onChange={(e) =>
                                        setData('user_id_no', e.target.value)
                                    }
                                    style={{
                                        color: props.text_secondary_color,
                                    }}
                                />
                                <InputError
                                    className="mt-2"
                                    message={errors.user_id_no}
                                />
                            </div>

                            <div className="min-w-[250px] flex-1">
                                <InputLabel
                                    htmlFor="user_email"
                                    value="Email Address"
                                    style={{
                                        color: props.text_primary_color,
                                    }}
                                />
                                <TextInput
                                    id="user_email"
                                    className="mt-1 block w-full"
                                    value={data.user_email}
                                    onChange={(e) =>
                                        setData('user_email', e.target.value)
                                    }
                                    style={{
                                        color: props.text_secondary_color,
                                    }}
                                />
                                <InputError
                                    className="mt-2"
                                    message={errors.user_email}
                                />
                            </div>

                            <div className="min-w-[250px] flex-1">
                                <InputLabel
                                    htmlFor="user_phone_num"
                                    value="Phone Number"
                                    style={{
                                        color: props.text_primary_color,
                                    }}
                                />
                                <TextInput
                                    id="user_phone_num"
                                    className="mt-1 block w-full"
                                    value={data.user_phone_num}
                                    onChange={(e) =>
                                        setData(
                                            'user_phone_num',
                                            e.target.value,
                                        )
                                    }
                                    style={{
                                        color: props.text_secondary_color,
                                    }}
                                />
                                <InputError
                                    className="mt-2"
                                    message={errors.user_phone_num}
                                />
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            {/* Form NOBLES */}
            <div
                className={
                    'w-full pb-4 ' +
                    // if there exist category NOBLES in selection show, else hide
                    (selectedSeats.some(
                        (seat) => seat.ticket_type?.toLowerCase() === 'nobles',
                    )
                        ? 'block'
                        : 'hidden')
                }
            >
                <div className="mx-auto w-full max-w-7xl sm:px-6 lg:px-8">
                    <div
                        className="overflow-hidden p-6 shadow-xl sm:rounded-lg"
                        style={{
                            backgroundColor: props.primary_color,
                            color: props.text_primary_color,
                        }}
                    >
                        <h3 className="mb-4 text-lg font-semibold">
                            Fill your details for T-Shirt
                        </h3>
                        {/* Form here, just edit data, no submit */}
                        <form
                            className="space-y-4"
                            onSubmit={(e) => e.preventDefault()}
                        >
                            <div className="min-w-[250px] flex-1">
                                <InputLabel
                                    htmlFor="user_address"
                                    value="Address"
                                    style={{
                                        color: props.text_primary_color,
                                    }}
                                />
                                <TextInput
                                    id="user_address"
                                    className="mt-1 block w-full"
                                    value={data.user_address}
                                    onChange={(e) =>
                                        setData('user_address', e.target.value)
                                    }
                                    style={{
                                        color: props.text_secondary_color,
                                    }}
                                />
                                <InputError
                                    className="mt-2"
                                    message={errors.user_address}
                                />
                            </div>

                            {/* Make an array of number inputs as much as NOBLES categories selected */}
                            <div className="flex flex-col gap-4 md:flex-row">
                                {selectedSeats
                                    .filter(
                                        (seat) =>
                                            seat.ticket_type?.toLowerCase() ===
                                            'nobles',
                                    )
                                    .map((seat, index) => (
                                        <div
                                            key={index}
                                            className="w-full flex-1 md:w-fit"
                                        >
                                            <InputLabel
                                                htmlFor={`user_size_${index}`}
                                                value={`T-Shirt Size ${index + 1}`}
                                                style={{
                                                    color: props.text_primary_color,
                                                }}
                                            />
                                            <select
                                                id={`user_size_${index}`}
                                                className="mt-1 block w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500"
                                                value={
                                                    data.user_sizes?.[index] ||
                                                    ''
                                                }
                                                onChange={(e) =>
                                                    setData(
                                                        `user_sizes`,
                                                        ((
                                                            prevSizes: string[],
                                                        ) => {
                                                            const newSizes = [
                                                                ...prevSizes,
                                                            ];
                                                            newSizes[index] =
                                                                e.target.value.trim();
                                                            return newSizes;
                                                        })(
                                                            data.user_sizes ||
                                                                [],
                                                        ),
                                                    )
                                                }
                                                style={{
                                                    color: props.text_secondary_color,
                                                }}
                                            >
                                                <option value="">
                                                    Select Size
                                                </option>
                                                {[
                                                    'S',
                                                    'M',
                                                    'L',
                                                    'XL',
                                                    'XXL',
                                                ].map((size) => (
                                                    <option
                                                        key={size}
                                                        value={size}
                                                    >
                                                        {size}
                                                    </option>
                                                ))}
                                            </select>
                                            <InputError
                                                className="mt-2"
                                                message={
                                                    errors.user_sizes?.[index]
                                                }
                                            />
                                        </div>
                                    ))}
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            {/* Keep the selected seats section below */}
            <div className="w-full pb-4">
                <div className="mx-auto w-full max-w-7xl sm:px-6 lg:px-8">
                    <div
                        className="overflow-hidden p-6 shadow-xl sm:rounded-lg"
                        style={{
                            backgroundColor: props.primary_color,
                            color: props.text_primary_color,
                        }}
                    >
                        <h3 className="mb-4 text-lg font-semibold">
                            {pendingTransactions.length > 0 ? (
                                <span>Pending Transactions</span>
                            ) : (
                                <span>Selected Seats</span>
                            )}
                        </h3>

                        {pendingTransactions.length > 0 ? (
                            <div className="space-y-4">
                                {pendingTransactions.map(
                                    (transaction, transactionIndex) => {
                                        return (
                                            <div
                                                key={transactionIndex}
                                                className="space-y-4"
                                            >
                                                <h4 className="text-base font-semibold">
                                                    {'Transaction: ' +
                                                        transaction.order_code}
                                                </h4>
                                                {transaction.seats.map(
                                                    (seat) => (
                                                        <div
                                                            key={seat.id}
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
                                                                        'Unset'}
                                                                </p>
                                                                <p className="text-sm">
                                                                    Seat:{' '}
                                                                    {
                                                                        seat.seat_number
                                                                    }
                                                                </p>
                                                            </div>
                                                            <div>
                                                                <p className="font-semibold">
                                                                    Price:{' '}
                                                                    {formatRupiah(
                                                                        getSafePrice(
                                                                            seat.price,
                                                                        ),
                                                                    )}
                                                                </p>
                                                            </div>
                                                        </div>
                                                    ),
                                                )}
                                                {/* Subtotal, Tax, and Total */}
                                                <div
                                                    className="mt-6 space-y-2 rounded-lg p-3"
                                                    style={{
                                                        backgroundColor:
                                                            props.secondary_color,
                                                        color: props.text_secondary_color,
                                                    }}
                                                >
                                                    <div className="flex justify-between">
                                                        <span className="font-medium">
                                                            Subtotal:
                                                        </span>
                                                        <span>
                                                            {formatRupiah(
                                                                transaction.seats.reduce(
                                                                    (
                                                                        acc,
                                                                        seat,
                                                                    ) => {
                                                                        const seatPrice =
                                                                            getSafePrice(
                                                                                seat.price,
                                                                            );
                                                                        return (
                                                                            acc +
                                                                            seatPrice
                                                                        );
                                                                    },
                                                                    0,
                                                                ),
                                                            )}
                                                        </span>
                                                    </div>
                                                    <div className="flex justify-between">
                                                        <span className="font-medium">
                                                            Tax (0%):
                                                        </span>
                                                        <span>
                                                            {formatRupiah(
                                                                taxAmount,
                                                            )}
                                                        </span>
                                                    </div>
                                                    <div className="flex justify-between text-lg font-semibold">
                                                        <span>Total:</span>
                                                        <span>
                                                            {formatRupiah(
                                                                // total from pending
                                                                transaction.seats.reduce(
                                                                    (
                                                                        acc,
                                                                        seat,
                                                                    ) => {
                                                                        const seatPrice =
                                                                            getSafePrice(
                                                                                seat.price,
                                                                            );
                                                                        return (
                                                                            acc +
                                                                            seatPrice
                                                                        );
                                                                    },
                                                                    0,
                                                                ),
                                                            )}
                                                        </span>
                                                    </div>
                                                </div>

                                                <div className="mt-4 flex gap-2">
                                                    {/* Resume Button */}
                                                    {isBookingAllowed && (
                                                        <button
                                                            className="rounded bg-blue-500 px-4 py-2 text-white hover:bg-blue-600"
                                                            onClick={() =>
                                                                resumePayment(
                                                                    transaction.accessor,
                                                                    transaction.payment_gateway,
                                                                )
                                                            }
                                                        >
                                                            Resume Payment
                                                        </button>
                                                    )}
                                                    {/* Cancel Button */}
                                                    {isBookingAllowed && (
                                                        <div className="flex gap-2">
                                                            {cancellingStack.some(
                                                                (item) =>
                                                                    item.order_id ===
                                                                    transaction.order_id,
                                                            ) ? (
                                                                <>
                                                                    <button
                                                                        className="rounded bg-red-500 px-4 py-2 text-white hover:bg-red-600"
                                                                        onClick={() =>
                                                                            cancelPayment(
                                                                                [
                                                                                    transaction.order_id,
                                                                                ],
                                                                            )
                                                                        }
                                                                    >
                                                                        Confirm
                                                                        Cancel
                                                                    </button>
                                                                    <button
                                                                        className="rounded bg-gray-500 px-4 py-2 text-white hover:bg-gray-600"
                                                                        onClick={() =>
                                                                            removeItemFromStack(
                                                                                transaction.order_id,
                                                                            )
                                                                        }
                                                                    >
                                                                        Abort
                                                                        Cancellation
                                                                    </button>
                                                                </>
                                                            ) : (
                                                                <>
                                                                    <button
                                                                        className="rounded bg-red-500 px-4 py-2 text-white hover:bg-red-600"
                                                                        onClick={() =>
                                                                            addItemToStack(
                                                                                transaction.order_id,
                                                                            )
                                                                        }
                                                                    >
                                                                        Cancel
                                                                        Payment
                                                                    </button>
                                                                </>
                                                            )}
                                                        </div>
                                                    )}
                                                </div>
                                            </div>
                                        );
                                    },
                                )}
                            </div>
                        ) : selectedSeats.length === 0 ? (
                            <p>No seats selected.</p>
                        ) : (
                            <>
                                <div className="space-y-4">
                                    {selectedSeats.map((seat) => (
                                        <div
                                            key={seat.id}
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
                                                        'Unset'}
                                                </p>
                                                <p className="text-sm">
                                                    Seat: {seat.seat_number}
                                                </p>
                                            </div>
                                            <div>
                                                <p className="font-semibold">
                                                    Price:{' '}
                                                    {formatRupiah(
                                                        getSafePrice(
                                                            seat.price,
                                                        ),
                                                    )}
                                                </p>
                                            </div>
                                        </div>
                                    ))}
                                </div>
                                {/* Subtotal, Tax, and Total */}
                                {selectedSeats.length > 0 && (
                                    <div
                                        className="mt-6 space-y-2 rounded-lg p-3"
                                        style={{
                                            backgroundColor:
                                                props.secondary_color,
                                            color: props.text_secondary_color,
                                        }}
                                    >
                                        <div className="flex justify-between">
                                            <span className="font-medium">
                                                Subtotal:
                                            </span>
                                            <span>
                                                {formatRupiah(subtotal)}
                                            </span>
                                        </div>
                                        <div className="flex justify-between">
                                            <span className="font-medium">
                                                Tax (0%):
                                            </span>
                                            <span>
                                                {formatRupiah(taxAmount)}
                                            </span>
                                        </div>
                                        <div className="flex justify-between text-lg font-semibold">
                                            <span>Total:</span>
                                            <span>{formatRupiah(total)}</span>
                                        </div>
                                    </div>
                                )}
                            </>
                        )}

                        {/* Proceed Button */}
                        {pendingTransactions.length === 0 &&
                            selectedSeats.length > 0 &&
                            isBookingAllowed && (
                                <ProceedTransactionButton
                                    callback={async (accessor) => {
                                        await new Promise((resolve, reject) => {
                                            router.post(
                                                route(
                                                    'client.formRegistration',
                                                    {
                                                        client,
                                                    },
                                                ),
                                                { ...data, accessor },
                                                {
                                                    preserveScroll: true,
                                                    onSuccess: () => {
                                                        resolve(true);
                                                    },
                                                    onError: (error) => {
                                                        showError(
                                                            'Failed to proceed with payment. Error: ' +
                                                                error,
                                                        );
                                                        reject(error);
                                                    },
                                                },
                                            );
                                        });
                                    }}
                                    disabled={disabledByForm}
                                    client={client}
                                    selectedSeats={selectedSeats}
                                    taxAmount={taxAmount}
                                    subtotal={subtotal}
                                    total={total}
                                    toasterFunction={{
                                        toasterState,
                                        showSuccess,
                                        showError,
                                        hideToaster,
                                    }}
                                    snapInitialized={snapInitialized}
                                    paymentGateway={paymentGateway}
                                />
                            )}

                        {/* Disabled by form notification */}
                        <div
                            className={
                                'mt-4 text-sm text-red-500 ' +
                                (disabledByForm ? 'block' : 'hidden')
                            }
                        >
                            <p>
                                Please fill out the form above before proceeding
                                with payment.
                            </p>
                        </div>
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
