// resources/js/Pages/Receptionist/scan/page.tsx
'use client';

import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import Quagga from '@ericblade/quagga2';
import {
    ArrowPathIcon, // Added for QR toggle button
    Bars3BottomLeftIcon,
    CameraIcon,
    CheckCircleIcon,
    QrCodeIcon,
    XCircleIcon,
} from '@heroicons/react/24/solid';
import { usePage } from '@inertiajs/react';
import { Spinner } from '@nextui-org/react';
// import { QrScanner } from '@yudiel/react-qr-scanner'; // Import the new QR scanner
import { AnimatePresence, motion } from 'framer-motion';
import React, { useCallback, useEffect, useRef, useState } from 'react';

import { EventProps } from '@/types/front-end';
import { PageProps as InertiaPageProps } from '@inertiajs/core';

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

interface QuaggaDetectedData {
    codeResult: {
        code: string | null;
        format: string;
    };
}

interface EventContext {
    id: number;
    name: string;
    slug: string;
}

interface CustomPageProps extends InertiaPageProps {
    appName: string;
    event: EventContext;
    props: EventProps;
    client: string;
    userEndSessionDatetime?: string;
}

const EventScanTicketPage = () => {
    const { appName, event, props, client, userEndSessionDatetime } =
        usePage<CustomPageProps>().props;

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
    const [isQrMode, setIsQrMode] = useState<boolean>(false); // New state for QR mode

    const handleTicketScan = useCallback(
        async (code: string) => {
            if (!code || isLoading) return;

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
                return;
            }

            setIsLoading(true);
            try {
                const scanUrl = route('client.events.scan.store', {
                    client,
                    event_slug: event.slug,
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
                let errorMessage = 'An unexpected error occurred.';
                if (error instanceof Error) {
                    errorMessage = error.message;
                } else if (
                    typeof error === 'object' &&
                    error != null &&
                    'message' in error &&
                    typeof (error as { message: string }).message === 'string'
                ) {
                    errorMessage = (error as { message: string }).message;
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
        [isLoading, client, event],
    );

    // --- Quagga (Barcode) useEffect ---
    useEffect(() => {
        if (
            videoRef.current &&
            cameraActive &&
            !isQrMode &&
            !scannerInitialized.current
        ) {
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
            videoContainer.id = 'interactive-viewport-barcode'; // Unique ID for Quagga

            Quagga.init(
                {
                    inputStream: {
                        name: 'Live',
                        type: 'LiveStream',
                        target: '#interactive-viewport-barcode',
                        constraints: {
                            width: { min: 640, ideal: 1280 },
                            height: { min: 480, ideal: 720 },
                            facingMode: facingMode,
                        },
                    },
                    decoder: {
                        readers: [
                            {
                                format: 'ean_reader',
                                config: { supplements: [] },
                            },
                            {
                                format: 'code_128_reader',
                                config: { supplements: [] },
                            },
                        ],
                    },
                    locator: {
                        patchSize: 'medium',
                        halfSample: true,
                    },
                    numOfWorkers: 0,
                    frequency: 10,
                    debug: true,
                },
                (err: QuaggaError | Error | null) => {
                    if (err) {
                        console.error('Quagga initialization failed:', err);
                        setNotification({
                            message: `Failed to start barcode scanner: ${err.message}`,
                            status: 'error',
                            ticket: null,
                            ticketOrder: null,
                        });
                        setCameraActive(false);
                        return;
                    }
                    Quagga.start();
                    scannerInitialized.current = true;
                    console.log('Quagga started successfully for barcodes.');
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
        } else if (scannerInitialized.current && (isQrMode || !cameraActive)) {
            // Stop Quagga if switching to QR mode or camera is deactivated
            Quagga.stop();
            scannerInitialized.current = false;
            console.log(
                'Quagga stopped due to mode switch or camera deactivation.',
            );
        }
    }, [
        cameraActive,
        facingMode,
        handleTicketScan,
        isLoading,
        event?.slug,
        isQrMode,
    ]);

    // --- QR Scanner handlers ---
    // const handleQrScan = (result: string) => {
    //     if (result && !isLoading) {
    //         console.log('QR Code detected:', result);
    //         handleTicketScan(result);
    //     }
    // };

    // const handleQrError = (err: Error) => {
    //     console.error('QR Scanner Error:', err);
    //     setNotification({
    //         message: `QR Scanner Error: ${err.message}`,
    //         status: 'error',
    //         ticket: null,
    //         ticketOrder: null,
    //     });
    // };

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        handleTicketScan(ticketCodeInput);
    };

    const toggleCamera = () => {
        setCameraActive((prev) => !prev);
        // Stop any active scanner when toggling camera off
        if (scannerInitialized.current && cameraActive) {
            Quagga.stop();
            scannerInitialized.current = false;
        }
    };

    const flipCamera = () => {
        setFacingMode((prev) =>
            prev === 'environment' ? 'user' : 'environment',
        );
        // Re-initialize scanner after flipping camera
        if (scannerInitialized.current) {
            Quagga.stop();
            scannerInitialized.current = false;
        }
    };

    const toggleScanMode = () => {
        setIsQrMode((prev) => !prev);
        // Stop any active scanner when switching modes to allow proper re-initialization
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

    const eventName = event?.name || 'Loading Event...';

    return (
        <AuthenticatedLayout
            appName={appName}
            client={client}
            props={props}
            userEndSessionDatetime={userEndSessionDatetime}
            event={event}
            header={
                <h2 className="text-xl font-semibold leading-tight text-gray-800">
                    Scan Ticket for Event: {eventName}
                </h2>
            }
        >
            <div className="container mx-auto max-w-4xl p-4">
                {/* Camera Stream / Scanner Area */}
                <div className="relative mb-8 overflow-hidden rounded-lg bg-gray-900 p-4 shadow-xl">
                    <div
                        id={
                            isQrMode
                                ? 'interactive-viewport-qr'
                                : 'interactive-viewport-barcode'
                        }
                        className="relative flex h-80 w-full items-center justify-center overflow-hidden rounded-md bg-black"
                    >
                        {/* {cameraActive ? (
                            isQrMode ? (
                                <QrScanner
                                    onDecode={handleQrScan}
                                    onError={handleQrError}
                                    constraints={{
                                        facingMode: facingMode,
                                    }}
                                    containerStyle={{
                                        width: '100%',
                                        height: '100%',
                                        display: 'flex',
                                        justifyContent: 'center',
                                        alignItems: 'center',
                                    }}
                                    videoContainerStyle={{
                                        width: '100%',
                                        height: '100%',
                                    }}
                                    videoStyle={{
                                        objectFit: 'cover',
                                    }}
                                />
                            ) : (
                                <>
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
                            )
                        ) : (
                            <p className="text-lg text-white">
                                Camera is off. Click "Activate Camera" to start
                                scanning.
                            </p>
                        )} */}
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
                        {cameraActive && (
                            <button
                                onClick={toggleScanMode}
                                className={`flex items-center rounded-md px-4 py-2 font-semibold text-white shadow-md transition duration-300 ${
                                    isQrMode
                                        ? 'bg-purple-600 hover:bg-purple-700'
                                        : 'bg-green-600 hover:bg-green-700'
                                }`}
                            >
                                {isQrMode ? (
                                    <>
                                        <Bars3BottomLeftIcon className="mr-2 h-5 w-5" />
                                        Switch to Barcode
                                    </>
                                ) : (
                                    <>
                                        <QrCodeIcon className="mr-2 h-5 w-5" />
                                        Switch to QR Code
                                    </>
                                )}
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
