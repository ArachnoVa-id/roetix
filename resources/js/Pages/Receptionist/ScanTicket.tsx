import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import {
    ApiErrorResponse, // Generic now
    ApiSuccessResponse, // Generic now
    ScannedTicketData,
} from '@/types/front-end';
import { PageProps } from '@inertiajs/core';

import { Head, usePage } from '@inertiajs/react';
import axios from 'axios';
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

    const [ticketCode, setTicketCode] = useState<string>('');
    const [useFrontCamera, setUseFrontCamera] = useState<boolean>(false);
    const [isCameraActive, setIsCameraActive] = useState<boolean>(false);
    const [isScanning, setIsScanning] = useState<boolean>(false);
    const [isLoading, setIsLoading] = useState<boolean>(false);
    const [cameraError, setCameraError] = useState<string>('');
    const [scannedTickets, setScannedTickets] = useState<ScannedTicket[]>([]);
    const [notification, setNotification] = useState<NotificationState>({
        type: null,
        message: '',
    });
    const [isFetchingHistory, setIsFetchingHistory] = useState<boolean>(true);

    const videoRef = useRef<HTMLVideoElement>(null);
    const canvasRef = useRef<HTMLCanvasElement>(null);
    const currentStreamRef = useRef<MediaStream | null>(null);
    const scanIntervalRef = useRef<NodeJS.Timeout | null>(null);
    const lastScannedCodeRef = useRef<string>('');
    const scanTimeoutRef = useRef<NodeJS.Timeout | null>(null);

    const clearNotification = useCallback(() => {
        if (scanTimeoutRef.current) {
            clearTimeout(scanTimeoutRef.current);
        }
        scanTimeoutRef.current = setTimeout(() => {
            setNotification({ type: null, message: '' });
        }, 3000);
    }, []);

    const fetchScannedTicketsHistory = useCallback(async () => {
        // PERBAIKAN: Pastikan event dan event.slug tersedia sebelum melakukan fetch
        if (!event || !event.slug) {
            console.error(
                'Error: Event data or slug missing for history fetch.',
            );
            setIsFetchingHistory(false);
            setNotification({
                type: 'error',
                message: 'Event data or slug missing for history.',
            });
            clearNotification();
            return;
        }

        setIsFetchingHistory(true);
        try {
            // PERBAIKAN: event_slug dikirim sebagai query parameter karena rute /scanned-history
            const url = route('events.scanned.history', {
                client: client,
                event_slug: event.slug, // Ini akan menjadi ?event_slug=...
            });
            const response =
                await axios.get<ApiSuccessResponse<ScannedTicket[]>>(url);
            if (response.data && response.data.data) {
                setScannedTickets(response.data.data);
            } else {
                setScannedTickets([]);
            }
        } catch (error) {
            console.error('Failed to fetch scanned tickets history:', error);
            setNotification({
                type: 'error',
                message: 'Failed to load scan history.',
            });
            clearNotification();
        } finally {
            setIsFetchingHistory(false);
        }
    }, [client, event, clearNotification]);

    useEffect(() => {
        fetchScannedTicketsHistory();
    }, [fetchScannedTicketsHistory]);

    const addOrUpdateScannedTicket = useCallback(
        (newTicketData: ScannedTicket) => {
            setScannedTickets((prev) => {
                const updatedPrev = prev.filter(
                    (ticket) => ticket.id !== newTicketData.id,
                );
                return [newTicketData, ...updatedPrev];
            });
        },
        [],
    );

    const submitTicketCode = useCallback(
        async (codeToSubmit: string) => {
            // PERBAIKAN: Pastikan event dan event.slug tersedia sebelum melakukan submit
            if (isLoading || !codeToSubmit.trim()) return;
            if (!event || !event.slug) {
                const errorMsg = 'Event data is missing for scanning.';
                setNotification({
                    type: 'error',
                    message: errorMsg,
                });
                addOrUpdateScannedTicket({
                    id: `local-error-${Date.now()}`,
                    ticket_code: codeToSubmit,
                    scanned_at: new Date().toISOString(),
                    status: 'error',
                    message: errorMsg,
                });
                clearNotification();
                return;
            }

            if (lastScannedCodeRef.current === codeToSubmit.trim()) {
                return;
            }
            lastScannedCodeRef.current = codeToSubmit.trim();

            setIsLoading(true);
            setNotification({ type: null, message: '' });

            try {
                // PERBAIKAN: Rute 'client.events.scan.store' sekarang hanya '/scan'
                // Event slug harus dikirim di body
                const url = route('client.events.scan.store', { client });

                const response = await axios.post<
                    ApiSuccessResponse<ScannedTicket>
                >(url, {
                    ticket_code: codeToSubmit.trim(),
                    event_slug: event.slug, // PERBAIKAN: event_slug dikirim di body
                });

                const successMsg =
                    response.data?.message ||
                    `Ticket ${codeToSubmit} scanned successfully!`;
                setNotification({
                    type: 'success',
                    message: successMsg,
                });

                if (response.data?.data) {
                    addOrUpdateScannedTicket(response.data.data);
                }
                setTicketCode('');
            } catch (error: unknown) {
                let errorMessage =
                    'An unknown error occurred while processing the ticket.';
                let ticketStatus: ScannedTicket['status'] = 'error';
                let scannedTicketData: ScannedTicket | undefined = undefined;

                if (axios.isAxiosError(error)) {
                    const responseData = error.response?.data as
                        | ApiErrorResponse<ScannedTicket>
                        | undefined;

                    if (responseData?.message) {
                        errorMessage = responseData.message;
                    }
                    if (error.response?.status === 409 && responseData?.data) {
                        scannedTicketData = responseData.data;
                        ticketStatus = 'error';
                        errorMessage =
                            scannedTicketData.message || errorMessage;
                    }
                } else if (error instanceof Error) {
                    errorMessage = error.message;
                }

                setNotification({ type: 'error', message: errorMessage });

                addOrUpdateScannedTicket(
                    scannedTicketData || {
                        id: `local-error-${Date.now()}`,
                        ticket_code: codeToSubmit.trim(),
                        scanned_at: new Date().toISOString(),
                        status: ticketStatus,
                        message: errorMessage,
                    },
                );

                console.error('Error submitting ticket code:', error);
            } finally {
                setIsLoading(false);
                clearNotification();
                setTimeout(() => {
                    lastScannedCodeRef.current = '';
                }, 2000);
            }
        },
        [isLoading, client, event, clearNotification, addOrUpdateScannedTicket],
    );

    const startQrScanner = useCallback(() => {
        // PERBAIKAN: Pastikan videoRef.current dan canvasRef.current tidak null
        if (
            !videoRef.current ||
            !canvasRef.current ||
            !isScanning ||
            !isCameraActive
        ) {
            if (scanIntervalRef.current) {
                clearInterval(scanIntervalRef.current);
                scanIntervalRef.current = null;
            }
            return;
        }

        // PERBAIKAN: Gunakan null assertion operator '!' setelah pengecekan
        const video = videoRef.current!;
        const canvas = canvasRef.current!;
        const context = canvas.getContext('2d', { willReadFrequently: true });

        if (!context) {
            setNotification({
                type: 'error',
                message: 'Could not get canvas context.',
            });
            clearNotification();
            return;
        }

        if (scanIntervalRef.current) {
            clearInterval(scanIntervalRef.current);
        }

        scanIntervalRef.current = setInterval(() => {
            if (
                video.readyState === video.HAVE_ENOUGH_DATA &&
                isScanning &&
                isCameraActive &&
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
                    code &&
                    code.data.trim() &&
                    code.data.trim() !== lastScannedCodeRef.current
                ) {
                    console.log('QR Code detected:', code.data.trim());
                    setTicketCode(code.data.trim());
                    submitTicketCode(code.data.trim());
                }
            }
        }, 100);
    }, [
        isScanning,
        isCameraActive,
        submitTicketCode,
        clearNotification,
        isLoading,
    ]);

    const stopCamera = useCallback(() => {
        console.log('Stopping camera...');

        if (scanIntervalRef.current) {
            clearInterval(scanIntervalRef.current);
            scanIntervalRef.current = null;
            console.log('Stopped QR scanning interval.');
        }

        if (currentStreamRef.current) {
            currentStreamRef.current.getTracks().forEach((track) => {
                if (track.readyState === 'live') {
                    track.stop();
                    console.log(
                        `Stopped track: ${track.kind} (id: ${track.id})`,
                    );
                }
            });
            currentStreamRef.current = null;
        }

        if (videoRef.current) {
            videoRef.current.srcObject = null;
            videoRef.current.pause();
        }

        setIsCameraActive(false);
        setCameraError('');
        lastScannedCodeRef.current = '';
        console.log('Camera stop process completed.');
    }, []);

    const startCamera = useCallback(async () => {
        console.log('Starting camera...');
        setCameraError('');

        // PERBAIKAN: Early exit jika videoRef.current belum ada
        if (!videoRef.current) {
            console.error('Video ref not available. Cannot start camera.');
            setCameraError('Camera display element not found.');
            setNotification({
                type: 'error',
                message: 'Camera display element not found.',
            });
            clearNotification();
            setIsCameraActive(false);
            setIsScanning(false);
            return;
        }

        // PERBAIKAN: Logika cerdas untuk mencegah restart yang tidak perlu
        if (currentStreamRef.current && isCameraActive) {
            const videoTrack = currentStreamRef.current.getVideoTracks()[0];
            const currentFacingMode = videoTrack?.getSettings().facingMode;
            const desiredFacingMode = useFrontCamera ? 'user' : 'environment';

            if (videoTrack && currentFacingMode === desiredFacingMode) {
                console.log(
                    'Camera already active with desired facing mode, no restart needed.',
                );
                return;
            } else {
                console.log(
                    'Existing camera stream found, stopping it for mode change or restart.',
                );
                stopCamera();
                await new Promise((resolve) => setTimeout(resolve, 100));
            }
        }

        if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
            const error = 'Camera not supported in this browser or environment';
            setCameraError(error);
            setNotification({ type: 'error', message: error });
            clearNotification();
            setIsCameraActive(false);
            setIsScanning(false);
            return;
        }

        const constraints: MediaStreamConstraints = {
            video: {
                facingMode: useFrontCamera ? 'user' : 'environment',
                width: { ideal: 640, max: 1280 },
                height: { ideal: 480, max: 720 },
            },
            audio: false,
        };

        try {
            console.log('Requesting camera with constraints:', constraints);
            const stream =
                await navigator.mediaDevices.getUserMedia(constraints);

            if (!stream) {
                throw new Error('No stream received from getUserMedia');
            }

            console.log('Camera stream obtained:', stream);
            currentStreamRef.current = stream;

            if (videoRef.current) {
                videoRef.current.srcObject = stream;

                await new Promise<void>((resolve, reject) => {
                    const video = videoRef.current!; // PERBAIKAN: Null assertion
                    let resolved = false;

                    const onLoadedMetadata = () => {
                        if (resolved) return;
                        resolved = true;
                        console.log('Video metadata loaded');
                        video.removeEventListener(
                            'loadedmetadata',
                            onLoadedMetadata,
                        );
                        video.removeEventListener('error', onError);
                        resolve();
                    };

                    const onError = (e: Event) => {
                        if (resolved) return;
                        resolved = true;
                        console.error('Video error:', e);
                        video.removeEventListener(
                            'loadedmetadata',
                            onLoadedMetadata,
                        );
                        video.removeEventListener('error', onError);
                        reject(new Error('Video failed to load'));
                    };

                    video.addEventListener('loadedmetadata', onLoadedMetadata);
                    video.addEventListener('error', onError);

                    setTimeout(() => {
                        if (!resolved) {
                            resolved = true;
                            video.removeEventListener(
                                'loadedmetadata',
                                onLoadedMetadata,
                            );
                            video.removeEventListener('error', onError);
                            resolve();
                        }
                    }, 3000);
                });

                await videoRef.current.play();
                console.log('Video playing successfully');
            }

            setIsCameraActive(true);
            setNotification({ type: null, message: '' });
        } catch (err: unknown) {
            console.error('Error starting camera:', err);

            let camMessage = 'Could not access camera. ';

            if (err instanceof Error) {
                if (err.name === 'NotAllowedError') {
                    camMessage +=
                        'Permission denied. Please allow camera access.';
                } else if (err.name === 'NotFoundError') {
                    camMessage += 'No camera found on this device.';
                } else if (err.name === 'NotReadableError') {
                    camMessage +=
                        'Camera is already in use by another application.';
                } else if (err.name === 'OverconstrainedError') {
                    camMessage += 'Camera constraints not supported.';
                } else {
                    camMessage += `${err.name}: ${err.message}`;
                }
            } else {
                camMessage += 'Unknown error occurred.';
            }

            setCameraError(camMessage);
            setNotification({ type: 'error', message: camMessage });
            clearNotification();
            setIsCameraActive(false);
            setIsScanning(false);
        }
    }, [useFrontCamera, stopCamera, clearNotification, isCameraActive]);

    const toggleCameraFacingMode = useCallback(() => {
        console.log('Toggling camera facing mode');
        setUseFrontCamera((prev) => !prev);
    }, []);

    const toggleScanning = useCallback(() => {
        setIsScanning((prev) => {
            const newValue = !prev;
            console.log('Toggle scanning: Setting isScanning to', newValue);
            return newValue;
        });
    }, []);

    useEffect(() => {
        console.log(
            '--- Effect [isScanning] triggered. isScanning:',
            isScanning,
        );
        if (isScanning) {
            startCamera();
        } else {
            stopCamera();
        }

        return () => {
            console.log(
                '--- Effect [isScanning] cleanup. isScanning at cleanup:',
                isScanning,
            );
            stopCamera();
        };
    }, [isScanning, startCamera, stopCamera]);

    useEffect(() => {
        console.log(
            '--- Effect [useFrontCamera] triggered. useFrontCamera:',
            useFrontCamera,
            'isScanning:',
            isScanning,
        );
        // PERBAIKAN: Hanya panggil startCamera jika isScanning aktif, biarkan startCamera yang memutuskan restart
        if (isScanning) {
            console.log('Camera facing mode changed, attempting restart...');
            startCamera();
        }
    }, [useFrontCamera, isScanning, startCamera]);

    useEffect(() => {
        console.log(
            '--- Effect [QR Scanner] triggered. isScanning:',
            isScanning,
            'isCameraActive:',
            isCameraActive,
        );
        if (isScanning && isCameraActive) {
            console.log('Starting QR scanner');
            startQrScanner();
        } else {
            if (scanIntervalRef.current) {
                console.log('Stopping QR scanner');
                clearInterval(scanIntervalRef.current);
                scanIntervalRef.current = null;
            }
        }

        return () => {
            if (scanIntervalRef.current) {
                console.log('QR Scanner cleanup.');
                clearInterval(scanIntervalRef.current);
                scanIntervalRef.current = null;
            }
        };
    }, [isScanning, isCameraActive, startQrScanner]);

    useEffect(() => {
        return () => {
            console.log('Component unmounting. Final camera stop.');
            stopCamera();
            if (scanTimeoutRef.current) {
                clearTimeout(scanTimeoutRef.current);
            }
        };
    }, [stopCamera]);

    const handleManualSubmit = async (e: FormEvent) => {
        e.preventDefault();
        if (!ticketCode.trim()) {
            setNotification({
                type: 'error',
                message: 'Ticket code cannot be empty.',
            });
            clearNotification();
            return;
        }
        submitTicketCode(ticketCode.trim());
    };

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

    const headerStyle = {
        '--header-text-color': pageConfigProps.text_primary_color,
    } as React.CSSProperties;

    return (
        <AuthenticatedLayout
            appName={appName}
            client={client}
            props={pageConfigProps}
            userEndSessionDatetime={userEndSessionDatetime}
            event={event}
            header={
                <div style={headerStyle} className="py-6 sm:py-8">
                    <h2 className="header-dynamic-color text-3xl font-extrabold leading-tight text-white drop-shadow-md md:text-4xl">
                        Scan Ticket for {event.name}
                    </h2>
                    {event.location && (
                        <p className="mt-2 text-base text-gray-200">
                            {event.location}
                        </p>
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
                    {/* Notification Section - Floating/Integrated */}
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
                                        xmlns="http://www.w3.org/2000/svg"
                                        fill="none"
                                        viewBox="0 0 24 24"
                                        strokeWidth={1.5}
                                        stroke="currentColor"
                                        className="mr-3 h-6 w-6"
                                    >
                                        <path
                                            strokeLinecap="round"
                                            strokeLinejoin="round"
                                            d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
                                        />
                                    </svg>
                                ) : (
                                    <svg
                                        xmlns="http://www.w3.org/2000/svg"
                                        fill="none"
                                        viewBox="0 0 24 24"
                                        strokeWidth={1.5}
                                        stroke="currentColor"
                                        className="mr-3 h-6 w-6"
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
                                title="Close notification"
                            >
                                <svg
                                    xmlns="http://www.w3.org/2000/svg"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    strokeWidth={1.5}
                                    stroke="currentColor"
                                    className="h-5 w-5"
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

                    {/* Main content grid - camera and history */}
                    <div className="grid grid-cols-1 gap-8 lg:grid-cols-2">
                        {/* Camera Scanner Section (Left) */}
                        <div className="relative flex flex-col items-center justify-center rounded-2xl border border-white/20 bg-white/10 p-8 text-white shadow-xl backdrop-blur-md">
                            <h3 className="mb-6 text-2xl font-bold">
                                Camera Scanner
                            </h3>

                            {cameraError && (
                                <div className="mb-6 w-full rounded-lg bg-yellow-100/20 p-4 text-yellow-200 backdrop-blur-sm">
                                    <strong className="block text-lg">
                                        Camera Issue:
                                    </strong>{' '}
                                    <p className="text-sm">{cameraError}</p>
                                </div>
                            )}

                            {/* Camera Feed / Placeholder */}
                            <div className="mb-6 aspect-video w-full overflow-hidden rounded-xl border-2 border-white/50 bg-gray-900 shadow-lg">
                                {isScanning && isCameraActive ? (
                                    <video
                                        ref={videoRef}
                                        width="100%"
                                        height="auto"
                                        autoPlay
                                        playsInline
                                        muted
                                        className="h-full w-full object-cover"
                                        style={{
                                            transform: useFrontCamera
                                                ? 'scaleX(-1)'
                                                : 'none',
                                        }}
                                    />
                                ) : (
                                    <div className="flex h-full items-center justify-center bg-gray-800/80">
                                        <div className="text-center">
                                            <svg
                                                xmlns="http://www.w3.org/2000/svg"
                                                fill="none"
                                                viewBox="0 0 24 24"
                                                strokeWidth={1.5}
                                                stroke="currentColor"
                                                className="mx-auto mb-4 h-20 w-20 text-gray-400"
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

                            {/* Camera control buttons */}
                            <div className="mb-8 flex flex-wrap justify-center gap-3">
                                <button
                                    type="button"
                                    onClick={toggleScanning}
                                    className={`rounded-full px-6 py-3 text-sm font-bold uppercase tracking-wide transition-all duration-300 focus:outline-none focus:ring-4 focus:ring-offset-2 ${
                                        isScanning
                                            ? 'bg-red-500 text-white hover:bg-red-600 focus:ring-red-400 active:bg-red-700'
                                            : 'bg-green-500 text-white hover:bg-green-600 focus:ring-green-400 active:bg-green-700'
                                    } shadow-lg disabled:cursor-not-allowed disabled:opacity-50 disabled:shadow-none`}
                                    disabled={isLoading}
                                >
                                    {isScanning
                                        ? 'Stop Camera'
                                        : 'Start Camera & Scan'}
                                </button>
                                {isScanning && (
                                    <button
                                        type="button"
                                        onClick={toggleCameraFacingMode}
                                        className={`rounded-full bg-blue-500 px-6 py-3 text-sm font-bold uppercase tracking-wide text-white shadow-lg transition-all duration-300 hover:bg-blue-600 focus:outline-none focus:ring-4 focus:ring-blue-400 focus:ring-offset-2 active:bg-blue-700 disabled:cursor-not-allowed disabled:opacity-50 disabled:shadow-none`}
                                        disabled={isLoading || !isCameraActive}
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
                                        name="ticket_code_manual"
                                        id="ticket_code_manual"
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
                                        className={`rounded-full bg-gray-500 px-6 py-3 text-sm font-bold uppercase tracking-wide text-white shadow-lg transition-all duration-300 hover:bg-gray-600 focus:outline-none focus:ring-4 focus:ring-gray-400 active:bg-gray-700 disabled:cursor-not-allowed disabled:opacity-50 disabled:shadow-none`}
                                        disabled={
                                            isLoading || !ticketCode.trim()
                                        }
                                        aria-label={
                                            isLoading
                                                ? 'Submitting...'
                                                : 'Submit ticket code'
                                        }
                                        title={
                                            isLoading
                                                ? 'Submitting...'
                                                : 'Submit ticket code'
                                        }
                                    >
                                        {isLoading ? (
                                            <svg
                                                className="mx-auto h-5 w-5 animate-spin text-white"
                                                xmlns="http://www.w3.org/2000/svg"
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

                        {/* Scanned Tickets History (Right) */}
                        <div className="flex flex-col rounded-2xl border border-white/20 bg-white/10 p-8 text-white shadow-xl backdrop-blur-md">
                            <h3 className="mb-6 text-2xl font-bold">
                                Scanned Tickets History ({scannedTickets.length}
                                )
                            </h3>

                            {isFetchingHistory ? (
                                <div className="flex flex-grow flex-col items-center justify-center py-8 text-gray-300">
                                    <svg
                                        className="mx-auto h-16 w-16 animate-spin text-gray-400"
                                        xmlns="http://www.w3.org/2000/svg"
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
                                    {' '}
                                    {/* Adjusted max-height */}
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
                                                    ).toLocaleString()}{' '}
                                                    {/* Use toLocaleString for full date/time */}
                                                </span>
                                            </div>
                                            {ticket.attendee_name && (
                                                <p className="mb-1 text-sm">
                                                    <strong className="font-semibold">
                                                        Attendee:
                                                    </strong>{' '}
                                                    {ticket.attendee_name}
                                                </p>
                                            )}
                                            {ticket.ticket_type && (
                                                <p className="mb-1 text-sm text-gray-300">
                                                    <strong className="font-semibold">
                                                        Type:
                                                    </strong>{' '}
                                                    {ticket.ticket_type}
                                                </p>
                                            )}
                                            <p
                                                className={`text-sm ${
                                                    ticket.status === 'success'
                                                        ? 'text-green-200'
                                                        : 'text-red-200'
                                                }`}
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
