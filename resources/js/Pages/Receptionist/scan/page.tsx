// resources/js/Pages/Receptionist/scan/page.tsx
'use client';

import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import Quagga from '@ericblade/quagga2';
import {
    ArrowPathIcon,
    CameraIcon,
    CheckCircleIcon,
    XCircleIcon,
} from '@heroicons/react/24/solid';
import { usePage } from '@inertiajs/react';
import { Spinner } from '@nextui-org/react';
import { AnimatePresence, motion } from 'framer-motion';
import React, { useCallback, useEffect, useRef, useState } from 'react';

import { EventProps } from '@/types/front-end';
import { PageProps as InertiaPageProps } from '@inertiajs/core'; // <--- Ubah impor di sini

interface TicketOrder {
    id: number;
    ticket_id: number;
    status: 'enabled' | 'scanned' | 'disabled';
    created_at: string;
}

interface Ticket {
    id: number;
    ticket_code: string;
    event_id: number;
    ticket_orders: TicketOrder[];
}

interface ScanResult {
    ticket: Ticket | null;
    ticketOrder: TicketOrder | null;
    message: string;
    status: 'success' | 'error';
}

interface QuaggaError extends Error {
    name: string;
    message: string;
    stack?: string;
}

// Ensure QuaggaDetectedData aligns with QuaggaJSResultObject
// QuaggaJSResultObject has codeResult.code as string | null
interface QuaggaDetectedData {
    codeResult: {
        code: string | null; // Changed to string | null
        format: string;
    };
}

// Updated EventContext to include slug
interface EventContext {
    id: number;
    name: string;
    slug: string; // Added slug property
}

// Extending InertiaPageProps to include our custom props
interface CustomPageProps extends InertiaPageProps {
    appName: string; // Tambahkan ini sesuai AuthenticatedLayout
    event: EventContext;
    props: EventProps; // This likely comes from your EventProps type
    client: string;
    userEndSessionDatetime?: string;
}

const EventScanTicketPage = () => {
    const { appName, event, props, client, userEndSessionDatetime } =
        usePage<CustomPageProps>().props; // Use CustomPageProps

    const videoRef = useRef<HTMLVideoElement>(null);
    const scannerInitialized = useRef(false);
    const [ticketCodeInput, setTicketCodeInput] = useState<string>('');
    const [scanResults, setScanResults] = useState<Ticket[]>([]);
    const [notification, setNotification] = useState<ScanResult | null>(null);
    const [isLoading, setIsLoading] = useState<boolean>(false);
    const [cameraActive, setCameraActive] = useState<boolean>(false);
    const [facingMode, setFacingMode] = useState<'environment' | 'user'>(
        'environment',
    );

    const handleTicketScan = useCallback(
        async (code: string) => {
            if (!code || isLoading) return;

            // Check if event is defined before accessing its properties
            if (!event) {
                console.error(
                    'Event data is undefined, cannot proceed with scan.',
                );
                setNotification({
                    ticket: null,
                    ticketOrder: null,
                    message: 'Event data is missing. Cannot scan ticket.',
                    status: 'error',
                });
                return; // Early return to prevent errors
            }

            setIsLoading(true);
            try {
                const scanUrl = route('client.events.scan.store', {
                    client,
                    event_slug: event.slug, // Access event.slug
                });

                const response = await fetch(scanUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': window.Laravel.csrfToken,
                    },
                    body: JSON.stringify({ ticket_code: code }),
                });

                const data = await response.json();

                if (!response.ok) {
                    throw new Error(
                        data.message || 'Something went wrong during scan.',
                    );
                }

                const newScanResult: ScanResult = {
                    ticket: data.ticket,
                    ticketOrder: data.ticketOrder,
                    message: data.message,
                    status: 'success',
                };
                setNotification(newScanResult);
                setScanResults((prev) => [
                    { ...data.ticket, ticket_orders: [data.ticketOrder] },
                    ...prev,
                ]);
                setTicketCodeInput('');
            } catch (error: unknown) {
                // Use unknown for safety
                let errorMessage = 'An unexpected error occurred.';
                if (error instanceof Error) {
                    errorMessage = error.message;
                } else if (
                    typeof error === 'object' &&
                    error != null &&
                    'message' in error &&
                    typeof (error as { message: string }).message === 'string'
                ) {
                    errorMessage = (error as { message: string }).message; // Correct type assertion
                }
                setNotification({
                    ticket: null,
                    ticketOrder: null,
                    message: errorMessage,
                    status: 'error',
                });
            } finally {
                setIsLoading(false);
                setTimeout(() => setNotification(null), 5000);
            }
        },
        [isLoading, client, event], // Added 'event' to the dependency array
    );
    useEffect(() => {
        if (videoRef.current && cameraActive && !scannerInitialized.current) {
            // Memberikan ID ke elemen parent yang menjadi target Quagga
            // Ini akan membantu Quagga mengatur DOM dengan lebih baik
            const videoContainer = videoRef.current.parentElement;
            if (!videoContainer) {
                console.error('Video container not found for Quagga target.');
                setNotification({
                    message: 'Failed to start camera: Video container missing.',
                    status: 'error',
                    ticket: null,
                    ticketOrder: null,
                });
                setCameraActive(false);
                return;
            }
            videoContainer.id = 'interactive-viewport'; // <-- Tambahkan ID ini

            Quagga.init(
                {
                    inputStream: {
                        name: 'Live',
                        type: 'LiveStream',
                        // Target harus ID dari elemen DOM yang akan menampung video dan canvas
                        // BUKAN elemen video itu sendiri
                        target: '#interactive-viewport', // <-- Gunakan ID container
                        constraints: {
                            width: { min: 640, ideal: 1280 }, // Tambahkan ideal resolution
                            height: { min: 480, ideal: 720 }, // Tambahkan ideal resolution
                            facingMode: facingMode,
                        },
                    },
                    decoder: {
                        readers: [
                            'ean_reader',
                            'code_128_reader',
                            { format: 'qr_code_reader' }, // <--- Pastikan ini sebagai objek, bukan string literal
                        ],
                    },
                    // Atur properti lain jika perlu
                    locator: {
                        patchSize: 'medium', // Default 'medium'
                        halfSample: true, // Default false
                    },
                    numOfWorkers: 0, // Penting untuk debugging di browser, 0 berarti di thread utama
                    frequency: 10, // Seberapa sering memproses frame
                    debug: {
                        drawBoundingBox: true,
                        drawScanline: true,
                        showCanvas: true,
                        showPatches: true,
                    },
                },
                (err: QuaggaError | Error | null) => {
                    if (err) {
                        console.error('Quagga initialization failed:', err);
                        setNotification({
                            message: `Failed to start camera: ${err.message}`,
                            status: 'error',
                            ticket: null,
                            ticketOrder: null,
                        });
                        setCameraActive(false);
                        return;
                    }
                    Quagga.start();
                    scannerInitialized.current = true;
                    console.log('Quagga started successfully.');

                    // Quagga yang akan mengelola elemen <video> dan <canvas> di dalam target
                    // Anda tidak perlu memanggil .play() secara manual jika Quagga berhasil
                    // If you still see the video black, inspect the DOM for Quagga's canvas/video elements
                },
            );

            Quagga.onDetected((data: QuaggaDetectedData) => {
                const code = data.codeResult.code;
                if (code && !isLoading) {
                    console.log('Barcode detected:', code);
                    handleTicketScan(code);
                }
            });

            return () => {
                if (scannerInitialized.current) {
                    Quagga.stop();
                    scannerInitialized.current = false;
                    console.log('Quagga stopped.');
                }
            };
        }
    }, [cameraActive, facingMode, handleTicketScan, isLoading, event?.slug]);

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        handleTicketScan(ticketCodeInput);
    };

    const toggleCamera = () => {
        setCameraActive((prev) => !prev);
        if (scannerInitialized.current && cameraActive) {
            Quagga.stop();
            scannerInitialized.current = false;
        }
    };

    const flipCamera = () => {
        setFacingMode((prev) =>
            prev === 'environment' ? 'user' : 'environment',
        );
        if (scannerInitialized.current) {
            Quagga.stop();
            scannerInitialized.current = false;
        }
    };

    const getStatusBadge = (status: TicketOrder['status']) => {
        let label = 'Unknown';
        let color = 'bg-gray-200 text-gray-800';

        switch (status) {
            case 'enabled':
                label = 'Enabled';
                color = 'bg-green-100 text-green-800';
                break;
            case 'scanned':
                label = 'Scanned';
                color = 'bg-blue-100 text-blue-800';
                break;
            case 'disabled':
                label = 'Disabled';
                color = 'bg-red-100 text-red-800';
                break;
            default:
                break;
        }
        return (
            <span
                className={`${color} rounded-full px-2.5 py-0.5 text-xs font-medium`}
            >
                {label}
            </span>
        );
    };

    // Provide a fallback for event.name if event is undefined
    const eventName = event?.name || 'Loading Event...';

    return (
        <AuthenticatedLayout
            appName={appName}
            client={client}
            props={props}
            userEndSessionDatetime={userEndSessionDatetime}
            event={event} // Pass event prop to AuthenticatedLayout for navigation
            header={
                <h2 className="text-xl font-semibold leading-tight text-gray-800">
                    Scan Ticket for Event: {eventName}
                </h2>
            }
        >
            <div className="container mx-auto max-w-4xl p-4">
                {/* Camera Stream */}
                <div className="relative mb-8 overflow-hidden rounded-lg bg-gray-900 p-4 shadow-xl">
                    {/* CONTAINER UNTUK VIDEO DAN CANVAS QUAGGA */}
                    <div
                        id="interactive-viewport"
                        className="relative flex h-80 w-full items-center justify-center overflow-hidden rounded-md bg-black"
                    >
                        {cameraActive ? (
                            <>
                                {/* Elemen <video> ini akan digantikan atau dimanipulasi oleh Quagga */}
                                {/* Anda tidak perlu memanggil .play() di sini */}
                                <video
                                    ref={videoRef}
                                    autoPlay
                                    playsInline
                                    className="absolute inset-0 h-full w-full object-cover"
                                ></video>
                                <div className="absolute inset-0 flex items-center justify-center">
                                    <div className="h-3/4 w-3/4 rounded-md border-2 border-dashed border-white"></div>
                                </div>
                            </>
                        ) : (
                            <p className="text-lg text-white">
                                Camera is off. Click "Activate Camera" to start
                                scanning.
                            </p>
                        )}
                    </div>

                    <div className="mt-4 flex justify-center space-x-4">
                        <button
                            onClick={toggleCamera}
                            className="flex items-center rounded-md bg-blue-600 px-4 py-2 font-semibold text-white shadow-md transition duration-300 hover:bg-blue-700"
                        >
                            <CameraIcon className="mr-2 h-5 w-5" />
                            {cameraActive
                                ? 'Deactivate Camera'
                                : 'Activate Camera'}
                        </button>
                        {cameraActive && (
                            <button
                                onClick={flipCamera}
                                className="flex items-center rounded-md bg-gray-600 px-4 py-2 font-semibold text-white shadow-md transition duration-300 hover:bg-gray-700"
                            >
                                <ArrowPathIcon className="mr-2 h-5 w-5" />
                                Flip Camera
                            </button>
                        )}
                    </div>
                </div>

                {/* Manual Input Form */}
                <div className="mb-8 rounded-lg bg-white p-6 shadow-xl">
                    <form
                        onSubmit={handleSubmit}
                        className="flex flex-col gap-4 sm:flex-row"
                    >
                        <input
                            type="text"
                            value={ticketCodeInput}
                            onChange={(e) => setTicketCodeInput(e.target.value)}
                            placeholder="Manually enter ticket code"
                            className="flex-grow rounded-md border border-gray-300 p-3 text-gray-900 focus:border-blue-500 focus:ring-blue-500"
                            disabled={isLoading}
                        />
                        <button
                            type="submit"
                            className="flex flex-shrink-0 items-center justify-center rounded-md bg-indigo-600 px-6 py-3 font-semibold text-white shadow-md transition duration-300 hover:bg-indigo-700"
                            disabled={isLoading}
                        >
                            {isLoading ? (
                                <Spinner size="sm" color="white" />
                            ) : (
                                'Submit'
                            )}
                        </button>
                    </form>
                </div>

                {/* Scan Notification */}
                <AnimatePresence>
                    {notification && (
                        <motion.div
                            initial={{ opacity: 0, y: -20 }}
                            animate={{ opacity: 1, y: 0 }}
                            exit={{ opacity: 0, y: -20 }}
                            className={`mb-6 flex items-center space-x-3 rounded-lg p-4 shadow-lg ${
                                notification.status === 'success'
                                    ? 'bg-green-100 text-green-800'
                                    : 'bg-red-100 text-red-800'
                            }`}
                        >
                            {notification.status === 'success' ? (
                                <CheckCircleIcon className="h-6 w-6 text-green-600" />
                            ) : (
                                <XCircleIcon className="h-6 w-6 text-red-600" />
                            )}
                            <p className="font-semibold">
                                {notification.message}
                            </p>
                        </motion.div>
                    )}
                </AnimatePresence>

                {/* Scanned Tickets List */}
                <div className="rounded-lg bg-white p-6 shadow-xl">
                    <h2 className="mb-4 text-2xl font-bold">Scanned Tickets</h2>
                    {scanResults.length === 0 ? (
                        <p className="text-gray-600">No tickets scanned yet.</p>
                    ) : (
                        <div className="overflow-x-auto">
                            <table className="min-w-full divide-y divide-gray-200">
                                <thead className="bg-gray-50">
                                    <tr>
                                        <th
                                            scope="col"
                                            className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500"
                                        >
                                            Ticket Code
                                        </th>
                                        <th
                                            scope="col"
                                            className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500"
                                        >
                                            Status
                                        </th>
                                        <th
                                            scope="col"
                                            className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500"
                                        >
                                            Scanned At
                                        </th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-gray-200 bg-white">
                                    {scanResults.map((ticket) => {
                                        const latestOrder = ticket.ticket_orders
                                            ? ticket.ticket_orders.sort(
                                                  (a, b) =>
                                                      new Date(
                                                          b.created_at,
                                                      ).getTime() -
                                                      new Date(
                                                          a.created_at,
                                                      ).getTime(),
                                              )[0]
                                            : null;
                                        const status =
                                            latestOrder?.status || 'enabled';

                                        return (
                                            <tr key={ticket.id}>
                                                <td className="whitespace-nowrap px-6 py-4 text-sm font-medium text-gray-900">
                                                    {ticket.ticket_code}
                                                </td>
                                                <td className="whitespace-nowrap px-6 py-4 text-sm">
                                                    {getStatusBadge(status)}
                                                </td>
                                                <td className="whitespace-nowrap px-6 py-4 text-sm text-gray-500">
                                                    {latestOrder
                                                        ? new Date(
                                                              latestOrder.created_at,
                                                          ).toLocaleString()
                                                        : 'N/A'}
                                                </td>
                                            </tr>
                                        );
                                    })}
                                </tbody>
                            </table>
                        </div>
                    )}
                </div>
            </div>
        </AuthenticatedLayout>
    );
};

export default EventScanTicketPage;
