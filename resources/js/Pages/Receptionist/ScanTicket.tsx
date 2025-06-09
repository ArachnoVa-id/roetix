import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import {
    ApiErrorResponse,
    ApiSuccessResponse,
    ScannedTicketData,
} from '@/types/front-end';
import { PageProps } from '@inertiajs/core';
import { Head, usePage } from '@inertiajs/react';
import axios from 'axios'; // Keep AxiosError import, it's used in type guard
import jsQR from 'jsqr';
import React, {
    FormEvent,
    useCallback,
    useEffect,
    useRef,
    useState,
} from 'react';
import { route } from 'ziggy-js';

interface NotificationState {
    type: 'success' | 'error' | null;
    message: string;
}

type ScannedTicket = ScannedTicketData;

const ScanTicket: React.FC = () => {
    const page = usePage<PageProps>();
    const {
        props: pageConfigProps,
        client,
        event,
        appName,
        userEndSessionDatetime,
    } = page.props;

    // State management
    const [ticketCode, setTicketCode] = useState<string>('');
    const [useFrontCamera, setUseFrontCamera] = useState<boolean>(false);
    const [isScanning, setIsScanning] = useState<boolean>(false);
    const [isLoading, setIsLoading] = useState<boolean>(false);
    const [cameraError, setCameraError] = useState<string>('');
    const [scannedTickets, setScannedTickets] = useState<ScannedTicket[]>([]);
    const [notification, setNotification] = useState<NotificationState>({
        type: null,
        message: '',
    });
    const [isFetchingHistory, setIsFetchingHistory] = useState<boolean>(true);

    // Refs
    const videoRef = useRef<HTMLVideoElement>(null);
    const canvasRef = useRef<HTMLCanvasElement>(null);
    const streamRef = useRef<MediaStream | null>(null);
    const scanIntervalRef = useRef<NodeJS.Timeout | null>(null);
    const lastScannedCodeRef = useRef<string>('');
    const notificationTimeoutRef = useRef<NodeJS.Timeout | null>(null);
    const isCameraStartingRef = useRef<boolean>(false);

    // Utility functions
    const clearNotification = useCallback(() => {
        if (notificationTimeoutRef.current) {
            clearTimeout(notificationTimeoutRef.current);
        }
        notificationTimeoutRef.current = setTimeout(() => {
            setNotification({ type: null, message: '' });
        }, 3000);
    }, []);

    const showNotification = useCallback(
        (type: 'success' | 'error', message: string) => {
            setNotification({ type, message });
            clearNotification();
        },
        [clearNotification],
    );

    const addOrUpdateScannedTicket = useCallback(
        (newTicketData: ScannedTicket) => {
            setScannedTickets((prev) => {
                const filtered = prev.filter(
                    (ticket) => ticket.id !== newTicketData.id,
                );
                return [newTicketData, ...filtered];
            });
        },
        [],
    );

    // API calls (moved before camera/QR scanning as submitTicketCode is a dependency)
    const submitTicketCode = useCallback(
        async (codeToSubmit: string) => {
            if (isLoading || !codeToSubmit.trim() || !event?.slug) return;

            // Prevent rapid re-scanning
            if (lastScannedCodeRef.current === codeToSubmit.trim()) {
                console.log('Skipping duplicate scan:', codeToSubmit);
                return;
            }
            lastScannedCodeRef.current = codeToSubmit.trim();

            setIsLoading(true);
            setNotification({ type: null, message: '' });

            try {
                const url = route('client.scan.store', { client });
                const response = await axios.post<
                    ApiSuccessResponse<ScannedTicket>
                >(url, {
                    ticket_code: codeToSubmit.trim(),
                    event_slug: event.slug,
                });

                const successMsg =
                    response.data?.message ||
                    `Ticket ${codeToSubmit} scanned successfully!`;
                showNotification('success', successMsg);

                if (response.data?.data) {
                    addOrUpdateScannedTicket(response.data.data);
                }
                setTicketCode('');
            } catch (error: unknown) {
                // Changed to unknown
                let errorMessage =
                    'An unknown error occurred while processing the ticket.';
                const ticketStatus: ScannedTicket['status'] = 'error';
                let scannedTicketData: ScannedTicket | undefined = undefined;

                if (axios.isAxiosError(error)) {
                    // Using type guard
                    const responseData = error.response?.data as
                        | ApiErrorResponse<ScannedTicket>
                        | undefined;
                    if (responseData?.message) {
                        errorMessage = responseData.message;
                    }
                    if (error.response?.status === 409 && responseData?.data) {
                        scannedTicketData = responseData.data;
                        errorMessage =
                            scannedTicketData.message || errorMessage;
                    }
                }

                showNotification('error', errorMessage);
                addOrUpdateScannedTicket(
                    scannedTicketData || {
                        id: `local-error-${Date.now()}`,
                        ticket_code: codeToSubmit.trim(),
                        scanned_at: new Date().toISOString(),
                        status: ticketStatus,
                        message: errorMessage,
                    },
                );
            } finally {
                setIsLoading(false);
                // Allow re-scanning after delay
                setTimeout(() => {
                    lastScannedCodeRef.current = '';
                }, 2000);
            }
        },
        [
            isLoading,
            client,
            event?.slug,
            showNotification,
            addOrUpdateScannedTicket,
        ],
    );

    // Camera management
    const stopCamera = useCallback(async () => {
        console.log('Stopping camera...');

        // Stop QR scanning
        if (scanIntervalRef.current) {
            clearInterval(scanIntervalRef.current);
            scanIntervalRef.current = null;
        }

        // Clear video element SEBELUM stop stream
        if (videoRef.current) {
            videoRef.current.pause();
            videoRef.current.srcObject = null;
            // TAMBAHKAN INI UNTUK FORCE CLEANUP
            videoRef.current.load();
        }

        // Stop camera stream
        if (streamRef.current) {
            streamRef.current.getTracks().forEach((track) => {
                console.log('Stopping track:', track.kind, track.readyState);
                track.stop();
            });
            streamRef.current = null;
        }

        setCameraError('');
        lastScannedCodeRef.current = '';
        isCameraStartingRef.current = false;
        await new Promise((resolve) => setTimeout(resolve, 300));
    }, []);

    const startCamera = useCallback(async () => {
        if (isCameraStartingRef.current) {
            console.log('Camera already starting, skipping...');
            return;
        }

        isCameraStartingRef.current = true;
        console.log('Starting camera...');
        setCameraError('');

        try {
            // Stop any existing camera first
            await stopCamera();

            // Wait a bit to ensure cleanup
            await new Promise((resolve) => setTimeout(resolve, 500));

            if (!navigator.mediaDevices?.getUserMedia) {
                throw new Error('Camera not supported in this browser');
            }

            const constraints: MediaStreamConstraints = {
                video: {
                    facingMode: useFrontCamera ? 'user' : 'environment',
                    width: { ideal: 640, max: 1280 },
                    height: { ideal: 480, max: 720 },
                },
                audio: false,
            };

            const stream =
                await navigator.mediaDevices.getUserMedia(constraints);
            streamRef.current = stream;

            if (videoRef.current) {
                videoRef.current.srcObject = stream;
                videoRef.current.load();

                // Wait for video to be ready
                await new Promise<void>((resolve, reject) => {
                    const video = videoRef.current!;
                    const timeout = setTimeout(
                        () => reject(new Error('Video load timeout')),
                        10000,
                    );

                    const onLoadedMetadata = () => {
                        clearTimeout(timeout);
                        video.removeEventListener(
                            'loadedmetadata',
                            onLoadedMetadata,
                        );
                        resolve();
                    };
                    const onCanPlay = () => {
                        clearTimeout(timeout);
                        video.removeEventListener('canplay', onCanPlay);
                        video.removeEventListener(
                            'loadedmetadata',
                            onLoadedMetadata,
                        );
                        resolve();
                    };

                    video.addEventListener('loadedmetadata', onLoadedMetadata);
                    video.addEventListener('canplay', onCanPlay);
                });

                await videoRef.current.play();
            }

            console.log('Camera started successfully');
        } catch (error: unknown) {
            // Changed to unknown
            console.error('Error starting camera:', error);

            let message = 'Could not access camera. ';
            if (error instanceof DOMException) {
                // Type guard for DOMException
                if (error.name === 'NotAllowedError') {
                    message += 'Permission denied. Please allow camera access.';
                } else if (error.name === 'NotFoundError') {
                    message += 'No camera found on this device.';
                } else if (error.name === 'NotReadableError') {
                    message +=
                        'Camera is already in use by another application.';
                } else {
                    message += error.message || 'Unknown error occurred.';
                }
            } else if (error instanceof Error) {
                // Type guard for generic Error
                message += error.message || 'Unknown error occurred.';
            } else {
                message += 'An unexpected error occurred.';
            }

            setCameraError(message);
            showNotification('error', message);
        } finally {
            isCameraStartingRef.current = false;
        }
    }, [useFrontCamera, stopCamera, showNotification]);

    // QR Code scanning (now defined after submitTicketCode)
    const startQrScanning = useCallback(() => {
        if (!videoRef.current || !canvasRef.current || isLoading) {
            return;
        }

        const video = videoRef.current;
        const canvas = canvasRef.current;
        const context = canvas.getContext('2d', { willReadFrequently: true });

        if (!context) {
            showNotification('error', 'Could not get canvas context.');
            return;
        }

        if (scanIntervalRef.current) {
            clearInterval(scanIntervalRef.current);
        }

        scanIntervalRef.current = setInterval(() => {
            if (
                video.readyState === video.HAVE_ENOUGH_DATA &&
                video.videoWidth > 0 &&
                video.videoHeight > 0 &&
                !isLoading
            ) {
                canvas.width = video.videoWidth;
                canvas.height = video.videoHeight;
                context.drawImage(video, 0, 0, canvas.width, canvas.height);

                const imageData = context.getImageData(
                    0,
                    0,
                    canvas.width,
                    canvas.height,
                );
                const code = jsQR(
                    imageData.data,
                    imageData.width,
                    imageData.height,
                    {
                        inversionAttempts: 'dontInvert',
                    },
                );

                if (
                    code?.data?.trim() &&
                    code.data.trim() !== lastScannedCodeRef.current
                ) {
                    console.log('QR Code detected:', code.data.trim());
                    submitTicketCode(code.data.trim());
                }
            }
        }, 200); // Reduced frequency to 200ms
    }, [isLoading, showNotification, submitTicketCode]); // Added submitTicketCode dependency

    const fetchScannedTicketsHistory = useCallback(async () => {
        if (!event?.slug) {
            console.error('Event slug missing for history fetch.');
            setIsFetchingHistory(false);
            showNotification('error', 'Event data missing for history.');
            return;
        }

        setIsFetchingHistory(true);
        try {
            const url = route('client.scanned.history', {
                client: client,
                event_slug: event.slug,
            });

            const response =
                await axios.get<ApiSuccessResponse<ScannedTicket[]>>(url);

            if (response.data?.data) {
                setScannedTickets(response.data.data);
            } else {
                setScannedTickets([]);
            }
        } catch (error) {
            console.error('Failed to fetch scanned tickets history:', error);
            showNotification('error', 'Failed to load scan history.');
            setScannedTickets([]);
        } finally {
            setIsFetchingHistory(false);
        }
    }, [client, event?.slug, showNotification]);

    // Event handlers
    const toggleScanning = useCallback(async () => {
        setIsScanning((prev) => !prev);
    }, []);

    const toggleCameraFacingMode = useCallback(() => {
        setUseFrontCamera((prev) => !prev);
    }, []);

    const handleManualSubmit = async (e: FormEvent) => {
        e.preventDefault();
        if (!ticketCode.trim()) {
            showNotification('error', 'Ticket code cannot be empty.');
            return;
        }
        submitTicketCode(ticketCode.trim());
    };

    // Effects
    useEffect(() => {
        fetchScannedTicketsHistory();
    }, [fetchScannedTicketsHistory]);

    // Main camera control effect
    useEffect(() => {
        let mounted = true;

        const handleCameraControl = async () => {
            if (isScanning) {
                await startCamera();
                if (mounted) {
                    startQrScanning();
                }
            } else {
                if (scanIntervalRef.current) {
                    clearInterval(scanIntervalRef.current);
                    scanIntervalRef.current = null;
                }
                await stopCamera();
            }
        };

        handleCameraControl();

        return () => {
            mounted = false;
            if (scanIntervalRef.current) {
                clearInterval(scanIntervalRef.current);
                scanIntervalRef.current = null;
            }
            stopCamera(); // Ensure stopCamera is called when the component unmounts
        };
    }, [isScanning, useFrontCamera, startCamera, startQrScanning, stopCamera]);

    // Cleanup on unmount
    useEffect(() => {
        return () => {
            if (streamRef.current) {
                streamRef.current.getTracks().forEach((track) => track.stop());
            }
            if (scanIntervalRef.current) {
                clearInterval(scanIntervalRef.current);
            }
            if (notificationTimeoutRef.current) {
                clearTimeout(notificationTimeoutRef.current);
            }
        };
    }, []);

    if (!event) {
        return (
            <AuthenticatedLayout
                appName={appName}
                client={client}
                props={pageConfigProps}
                userEndSessionDatetime={userEndSessionDatetime}
            >
                <Head title="Error - Event Not Found" />
                <div className="py-12">
                    <div className="mx-auto max-w-3xl sm:px-6 lg:px-8">
                        <div className="rounded-lg bg-white p-6 text-center text-red-600 shadow-sm dark:bg-gray-800">
                            Event information is missing or could not be loaded.
                            Please try again or contact support.
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
            props={pageConfigProps}
            userEndSessionDatetime={userEndSessionDatetime}
            header={
                <div style={{ color: pageConfigProps.text_primary_color }}>
                    <h2 className="header-dynamic-color text-3xl font-extrabold leading-tight drop-shadow-md md:text-4xl">
                        Scan Ticket for {event.name}
                    </h2>
                    {event.location && (
                        <p className="mt-2 text-base">{event.location}</p>
                    )}
                </div>
            }
        >
            <Head title={`Scan Ticket - ${event.name}`} />
            <div
                className="py-8 text-white md:py-12"
                style={{
                    backgroundColor: pageConfigProps.secondary_color,
                    backgroundImage: `url(${pageConfigProps.texture})`,
                    backgroundRepeat: 'repeat',
                    backgroundSize: 'auto',
                }}
            >
                <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    {/* Notification */}
                    {notification.type && (
                        <div
                            className={`mb-8 flex items-center justify-between rounded-xl p-4 text-white shadow-lg transition-all duration-300 ${
                                notification.type === 'success'
                                    ? 'bg-green-500/90'
                                    : 'bg-red-500/90'
                            } backdrop-blur-sm`}
                        >
                            <div className="flex items-center">
                                {notification.type === 'success' ? (
                                    <svg
                                        className="mr-3 h-6 w-6"
                                        fill="none"
                                        viewBox="0 0 24 24"
                                        strokeWidth={1.5}
                                        stroke="currentColor"
                                    >
                                        <path
                                            strokeLinecap="round"
                                            strokeLinejoin="round"
                                            d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
                                        />
                                    </svg>
                                ) : (
                                    <svg
                                        className="mr-3 h-6 w-6"
                                        fill="none"
                                        viewBox="0 0 24 24"
                                        strokeWidth={1.5}
                                        stroke="currentColor"
                                    >
                                        <path
                                            strokeLinecap="round"
                                            strokeLinejoin="round"
                                            d="M9.75 9.75l4.5 4.5m0-4.5l-4.5 4.5M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
                                        />
                                    </svg>
                                )}
                                <span className="text-base font-semibold">
                                    {notification.message}
                                </span>
                            </div>
                            <button
                                onClick={() =>
                                    setNotification({ type: null, message: '' })
                                }
                                className="ml-4 text-white opacity-80 transition-opacity hover:opacity-100"
                                aria-label="Close notification"
                            >
                                <svg
                                    className="h-5 w-5"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    strokeWidth={1.5}
                                    stroke="currentColor"
                                >
                                    <path
                                        strokeLinecap="round"
                                        strokeLinejoin="round"
                                        d="M6 18L18 6M6 6l12 12"
                                    />
                                </svg>
                            </button>
                        </div>
                    )}

                    {/* Main content grid */}
                    <div className="grid grid-cols-1 gap-8 lg:grid-cols-2">
                        {/* Camera Scanner Section */}
                        <div className="relative flex flex-col items-center justify-center rounded-2xl border border-white/20 bg-white/10 p-8 text-white shadow-xl backdrop-blur-md">
                            <h3 className="mb-6 text-2xl font-bold">
                                Camera Scanner
                            </h3>

                            {cameraError && (
                                <div className="mb-6 w-full rounded-lg bg-yellow-100/20 p-4 text-yellow-200 backdrop-blur-sm">
                                    <strong className="block text-lg">
                                        Camera Issue:
                                    </strong>
                                    <p className="text-sm">{cameraError}</p>
                                </div>
                            )}

                            {/* Camera Feed */}
                            <div className="relative mb-6 aspect-video w-full overflow-hidden rounded-xl border-2 border-white/50 bg-gray-900 shadow-lg">
                                <video
                                    ref={videoRef}
                                    className={`h-full w-full object-cover ${isScanning && streamRef.current ? '' : 'hidden'} ${useFrontCamera ? 'scale-x-[-1]' : ''}`}
                                    autoPlay
                                    playsInline
                                    muted
                                />

                                {(!isScanning || !streamRef.current) && (
                                    <div className="absolute inset-0 flex h-full items-center justify-center bg-gray-800/80">
                                        <div className="text-center">
                                            <svg
                                                className="mx-auto mb-4 h-20 w-20 text-gray-400"
                                                fill="none"
                                                viewBox="0 0 24 24"
                                                strokeWidth={1.5}
                                                stroke="currentColor"
                                            >
                                                <path
                                                    strokeLinecap="round"
                                                    strokeLinejoin="round"
                                                    d="M15.75 10.5l4.72-4.72a.75.75 0 011.28.53v11.38a.75.75 0 01-1.28.53l-4.72-4.72M4.5 18.75h9a2.25 2.25 0 002.25-2.25v-9a2.25 2.25 0 00-2.25-2.25h-9A2.25 2.25 0 002.25 7.5v9A2.25 2.25 0 004.5 18.75z"
                                                />
                                            </svg>
                                            <p className="text-lg text-gray-300">
                                                Camera inactive
                                            </p>
                                            <p className="mt-1 text-sm text-gray-400">
                                                Click "Start Camera & Scan"
                                            </p>
                                        </div>
                                    </div>
                                )}
                                <canvas ref={canvasRef} className="hidden" />
                            </div>

                            {/* Camera Controls */}
                            <div className="mb-8 flex flex-wrap justify-center gap-3">
                                <button
                                    type="button"
                                    onClick={toggleScanning}
                                    className={`rounded-full px-6 py-3 text-sm font-bold uppercase tracking-wide transition-all duration-300 focus:outline-none focus:ring-4 focus:ring-offset-2 ${
                                        isScanning
                                            ? 'bg-red-500 text-white hover:bg-red-600 focus:ring-red-400'
                                            : 'bg-green-500 text-white hover:bg-green-600 focus:ring-green-400'
                                    } shadow-lg disabled:cursor-not-allowed disabled:opacity-50`}
                                    disabled={
                                        isLoading || isCameraStartingRef.current
                                    }
                                >
                                    {isScanning
                                        ? 'Stop Camera'
                                        : 'Start Camera & Scan'}
                                </button>

                                {isScanning && (
                                    <button
                                        type="button"
                                        onClick={toggleCameraFacingMode}
                                        className="rounded-full bg-blue-500 px-6 py-3 text-sm font-bold uppercase tracking-wide text-white shadow-lg transition-all duration-300 hover:bg-blue-600 focus:outline-none focus:ring-4 focus:ring-blue-400 disabled:cursor-not-allowed disabled:opacity-50"
                                        disabled={
                                            isLoading ||
                                            isCameraStartingRef.current
                                        }
                                    >
                                        Switch to{' '}
                                        {useFrontCamera ? 'Back' : 'Front'}{' '}
                                        Camera
                                    </button>
                                )}
                            </div>

                            {/* Manual Input Form */}
                            <div className="w-full border-t border-white/20 pt-8">
                                <h4 className="mb-4 text-xl font-bold">
                                    Manual Ticket Entry
                                </h4>
                                <form
                                    onSubmit={handleManualSubmit}
                                    className="flex gap-3"
                                >
                                    <input
                                        type="text"
                                        className="block flex-1 rounded-full border border-white/30 bg-white/20 p-3 text-white placeholder-gray-300 focus:border-blue-300 focus:ring-blue-300"
                                        placeholder="Enter ticket code"
                                        value={ticketCode}
                                        onChange={(e) =>
                                            setTicketCode(e.target.value)
                                        }
                                        disabled={isLoading}
                                    />
                                    <button
                                        type="submit"
                                        className="rounded-full bg-gray-500 px-6 py-3 text-sm font-bold uppercase tracking-wide text-white shadow-lg transition-all duration-300 hover:bg-gray-600 focus:outline-none focus:ring-4 focus:ring-gray-400 disabled:cursor-not-allowed disabled:opacity-50"
                                        disabled={
                                            isLoading || !ticketCode.trim()
                                        }
                                    >
                                        {isLoading ? (
                                            <svg
                                                className="mx-auto h-5 w-5 animate-spin text-white"
                                                fill="none"
                                                viewBox="0 0 24 24"
                                            >
                                                <circle
                                                    className="opacity-25"
                                                    cx="12"
                                                    cy="12"
                                                    r="10"
                                                    stroke="currentColor"
                                                    strokeWidth="4"
                                                ></circle>
                                                <path
                                                    className="opacity-75"
                                                    fill="currentColor"
                                                    d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
                                                ></path>
                                            </svg>
                                        ) : (
                                            'Submit'
                                        )}
                                    </button>
                                </form>
                            </div>
                        </div>

                        {/* Scanned Tickets History */}
                        <div className="flex flex-col rounded-2xl border border-white/20 bg-white/10 p-8 text-white shadow-xl backdrop-blur-md">
                            <h3 className="mb-6 text-2xl font-bold">
                                Scanned Tickets History ({scannedTickets.length}
                                )
                            </h3>

                            {isFetchingHistory ? (
                                <div className="flex flex-grow flex-col items-center justify-center py-8 text-gray-300">
                                    <svg
                                        className="mx-auto h-16 w-16 animate-spin text-gray-400"
                                        fill="none"
                                        viewBox="0 0 24 24"
                                    >
                                        <circle
                                            className="opacity-25"
                                            cx="12"
                                            cy="12"
                                            r="10"
                                            stroke="currentColor"
                                            strokeWidth="4"
                                        ></circle>
                                        <path
                                            className="opacity-75"
                                            fill="currentColor"
                                            d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
                                        ></path>
                                    </svg>
                                    <p className="mt-4 text-lg font-medium">
                                        Loading scan history...
                                    </p>
                                </div>
                            ) : scannedTickets.length === 0 ? (
                                <div className="flex flex-grow flex-col items-center justify-center py-8 text-gray-300">
                                    <svg
                                        className="mx-auto mb-4 h-20 w-20 text-gray-400"
                                        fill="none"
                                        viewBox="0 0 24 24"
                                        stroke="currentColor"
                                    >
                                        <path
                                            strokeLinecap="round"
                                            strokeLinejoin="round"
                                            strokeWidth={2}
                                            d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"
                                        />
                                    </svg>
                                    <h3 className="text-xl font-medium">
                                        No tickets scanned yet
                                    </h3>
                                    <p className="mt-2 text-sm text-gray-400">
                                        Start scanning or manually enter a
                                        ticket code.
                                    </p>
                                </div>
                            ) : (
                                <div className="max-h-[calc(100vh-250px)] space-y-4 overflow-y-auto pr-2">
                                    {scannedTickets.map((ticket) => (
                                        <div
                                            key={ticket.id}
                                            className={`rounded-lg border-l-4 p-4 shadow-md transition-transform duration-150 hover:scale-[1.01] ${
                                                ticket.status === 'success'
                                                    ? 'border-green-400 bg-green-500/20'
                                                    : 'border-red-400 bg-red-500/20'
                                            }`}
                                        >
                                            <div className="mb-2 flex items-center justify-between">
                                                <div className="flex items-center">
                                                    <div
                                                        className={`mr-3 h-3 w-3 rounded-full ${
                                                            ticket.status ===
                                                            'success'
                                                                ? 'bg-green-400'
                                                                : 'bg-red-400'
                                                        }`}
                                                    />
                                                    <span className="font-mono text-base font-bold">
                                                        {ticket.ticket_code}
                                                    </span>
                                                </div>
                                                <span className="text-xs text-gray-300">
                                                    {new Date(
                                                        ticket.scanned_at,
                                                    ).toLocaleString()}
                                                </span>
                                            </div>
                                            {ticket.attendee_name && (
                                                <p className="mb-1 text-sm">
                                                    <strong>Attendee:</strong>{' '}
                                                    {ticket.attendee_name}
                                                </p>
                                            )}
                                            {ticket.ticket_type && (
                                                <p className="mb-1 text-sm text-gray-300">
                                                    <strong>Type:</strong>{' '}
                                                    {ticket.ticket_type}
                                                </p>
                                            )}
                                            <p
                                                className={`text-sm ${ticket.status === 'success' ? 'text-green-200' : 'text-red-200'}`}
                                            >
                                                {ticket.message}
                                            </p>
                                        </div>
                                    ))}
                                </div>
                            )}
                        </div>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
};

export default ScanTicket;
