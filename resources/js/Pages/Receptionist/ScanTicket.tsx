import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { ApiErrorResponse, ApiSuccessResponse } from '@/types/front-end';
import { PageProps as InertiaBasePageProps } from '@inertiajs/core';
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

interface ScannedTicket {
    id: string;
    ticketCode: string;
    timestamp: Date;
    status: 'success' | 'error';
    message: string;
}

const ScanTicket: React.FC = () => {
    const page = usePage<InertiaBasePageProps>();
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

    const addScannedTicket = useCallback(
        (ticketCode: string, status: 'success' | 'error', message: string) => {
            const newTicket: ScannedTicket = {
                id: Date.now().toString(),
                ticketCode,
                timestamp: new Date(),
                status,
                message,
            };
            setScannedTickets((prev) => [newTicket, ...prev.slice(0, 49)]); // Keep only last 50 tickets
        },
        [],
    );

    const submitTicketCode = useCallback(
        async (codeToSubmit: string) => {
            if (isLoading || !codeToSubmit.trim()) return;
            if (!event) {
                const errorMsg = 'Event data is missing.';
                setNotification({
                    type: 'error',
                    message: errorMsg,
                });
                addScannedTicket(codeToSubmit, 'error', errorMsg);
                clearNotification();
                return;
            }

            // Prevent scanning the same code twice in a row quickly
            if (lastScannedCodeRef.current === codeToSubmit.trim()) {
                return;
            }
            lastScannedCodeRef.current = codeToSubmit.trim();

            setIsLoading(true);
            setNotification({ type: null, message: '' });

            try {
                const url = route('client.events.scan.store', {
                    client,
                    event_slug: event.slug,
                });

                const response = await axios.post<ApiSuccessResponse>(url, {
                    ticket_code: codeToSubmit.trim(),
                });

                const successMsg =
                    response.data?.message ||
                    `Ticket ${codeToSubmit} scanned successfully!`;
                setNotification({
                    type: 'success',
                    message: successMsg,
                });
                addScannedTicket(codeToSubmit.trim(), 'success', successMsg);
                setTicketCode('');
            } catch (error: unknown) {
                let errorMessage =
                    'An unknown error occurred while processing the ticket.';
                if (axios.isAxiosError(error)) {
                    const responseData = error.response?.data as
                        | ApiErrorResponse
                        | undefined;
                    if (responseData?.message) {
                        errorMessage = responseData.message;
                    } else if (error.message) {
                        errorMessage = error.message;
                    }
                } else if (error instanceof Error) {
                    errorMessage = error.message;
                }
                setNotification({ type: 'error', message: errorMessage });
                addScannedTicket(codeToSubmit.trim(), 'error', errorMessage);
                console.error('Error submitting ticket code:', error);
            } finally {
                setIsLoading(false);
                clearNotification();
                // Reset the last scanned code after a delay
                setTimeout(() => {
                    lastScannedCodeRef.current = '';
                }, 2000);
            }
        },
        [isLoading, client, event, clearNotification, addScannedTicket],
    );

    const startQrScanner = useCallback(() => {
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

        const video = videoRef.current;
        const canvas = canvasRef.current;
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
        }, 100); // Reduced interval for better performance
    }, [
        isScanning,
        isCameraActive,
        submitTicketCode,
        clearNotification,
        isLoading,
    ]);

    const stopCamera = useCallback(() => {
        console.log('Stopping camera...');

        // Clear any ongoing QR scanning interval
        if (scanIntervalRef.current) {
            clearInterval(scanIntervalRef.current);
            scanIntervalRef.current = null;
            console.log('Stopped QR scanning interval.');
        }

        // Stop all tracks in the current stream
        if (currentStreamRef.current) {
            currentStreamRef.current.getTracks().forEach((track) => {
                if (track.readyState === 'live') {
                    // Only stop if track is active
                    track.stop();
                    console.log(
                        `Stopped track: ${track.kind} (id: ${track.id})`,
                    );
                }
            });
            currentStreamRef.current = null; // Clear the stream reference
        }

        // Stop video playback and clear its source
        if (videoRef.current) {
            videoRef.current.srcObject = null;
            videoRef.current.pause();
            // Optional: Call load() to ensure video element is fully reset
            // videoRef.current.load();
        }

        // Reset all camera-related states
        setIsCameraActive(false);
        setCameraError('');
        lastScannedCodeRef.current = ''; // Reset last scanned code on stop
        console.log('Camera stop process completed.');
    }, []);

    const startCamera = useCallback(async () => {
        console.log('Starting camera...');
        setCameraError('');

        // Ensure videoRef.current is available before proceeding
        if (!videoRef.current) {
            console.error('Video ref not available. Cannot start camera.');
            setCameraError('Camera display element not found.');
            setNotification({
                type: 'error',
                message: 'Camera display element not found.',
            });
            clearNotification();
            return;
        }

        // If a stream is already active, stop it before starting a new one
        // This is crucial to prevent multiple active camera streams
        if (currentStreamRef.current) {
            console.log(
                'Existing camera stream found, stopping it before new start.',
            );
            stopCamera();
            // Give a brief moment for the browser to release resources if needed
            await new Promise((resolve) => setTimeout(resolve, 100));
        }

        if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
            const error = 'Camera not supported in this browser or environment';
            setCameraError(error);
            setNotification({ type: 'error', message: error });
            clearNotification();
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

            // Ensure videoRef.current is still valid after async operation
            if (videoRef.current) {
                videoRef.current.srcObject = stream;

                await new Promise<void>((resolve, reject) => {
                    if (!videoRef.current) {
                        reject(new Error('Video ref lost during setup'));
                        return;
                    }

                    const video = videoRef.current;
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

                    // Timeout fallback in case loadedmetadata doesn't fire
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
                    }, 3000); // Max 3 seconds to wait for metadata
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
            setIsCameraActive(false); // Ensure state reflects failure
            setIsScanning(false); // Stop scanning if camera fails to start
        }
    }, [useFrontCamera, stopCamera, clearNotification]);

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

    // Effect for start/stop camera based on isScanning
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

        // Cleanup function for this effect
        return () => {
            console.log(
                '--- Effect [isScanning] cleanup. isScanning at cleanup:',
                isScanning,
            );
            // This stopCamera will run if the component unmounts or if `isScanning` changes
            // If `isScanning` goes from true to false, `stopCamera` is already called above.
            // This is primarily for component unmount.
            stopCamera();
        };
    }, [isScanning, startCamera, stopCamera]);

    // Effect for restart camera when facing mode changes
    useEffect(() => {
        // HANYA restart kamera jika scanning aktif DAN useFrontCamera berubah
        // JANGAN bergantung pada isCameraActive di dependency array karena itu adalah state yang dihasilkan oleh startCamera
        console.log(
            '--- Effect [useFrontCamera] triggered. useFrontCamera:',
            useFrontCamera,
            'isScanning:',
            isScanning,
        );
        if (isScanning) {
            // Hapus `isCameraActive` dari kondisi IF
            console.log('Camera facing mode changed, restarting camera');
            startCamera();
        }
    }, [useFrontCamera, isScanning, startCamera]); // Hapus `isCameraActive` dari dependency array

    // Effect for start QR scanner
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

        // Cleanup for QR scanner interval
        return () => {
            if (scanIntervalRef.current) {
                console.log('QR Scanner cleanup.');
                clearInterval(scanIntervalRef.current);
                scanIntervalRef.current = null;
            }
        };
    }, [isScanning, isCameraActive, startQrScanner]); // `isCameraActive` perlu di sini karena scanner butuh kamera aktif

    // Cleanup on unmount
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

    const clearScannedTickets = () => {
        setScannedTickets([]);
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
                        <div className="bg-white p-6 text-center text-red-600 shadow-sm dark:bg-gray-800 sm:rounded-lg">
                            Event information is missing or could not be loaded.
                            Please try again or contact support.
                        </div>
                    </div>
                </div>
            </AuthenticatedLayout>
        );
    }

    const buttonBaseClass =
        'px-4 py-2 rounded-md font-semibold text-xs uppercase tracking-widest focus:outline-none focus:ring-2 focus:ring-offset-2 transition ease-in-out duration-150 disabled:opacity-50';
    const primaryButtonClass = `${buttonBaseClass} bg-blue-600 text-white hover:bg-blue-700 active:bg-blue-800 focus:border-blue-700 focus:ring-blue-500`;
    const successButtonClass = `${buttonBaseClass} bg-green-600 text-white hover:bg-green-700 active:bg-green-800 focus:border-green-700 focus:ring-green-500`;
    const dangerButtonClass = `${buttonBaseClass} bg-red-600 text-white hover:bg-red-700 active:bg-red-800 focus:border-red-700 focus:ring-red-500`;
    const secondaryButtonClass = `${buttonBaseClass} bg-gray-600 text-white hover:bg-gray-700 active:bg-gray-800 focus:border-gray-700 focus:ring-gray-500`;

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
                <div style={headerStyle}>
                    <h2 className="header-dynamic-color text-xl font-semibold leading-tight">
                        Scan Ticket for {event.name}
                    </h2>
                </div>
            }
        >
            <Head title={`Scan Ticket - ${event.name}`} />
            <div className="py-6">
                <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    {notification.type && (
                        <div
                            className={`mb-4 rounded-md p-3 text-white ${
                                notification.type === 'success'
                                    ? 'bg-green-500'
                                    : 'bg-red-500'
                            }`}
                        >
                            {notification.message}
                        </div>
                    )}

                    <div className="grid grid-cols-1 gap-6 lg:grid-cols-2">
                        {/* Left Side - Camera */}
                        <div className="bg-white p-6 shadow-sm dark:bg-gray-800 sm:rounded-lg">
                            <h3 className="mb-4 text-lg font-semibold text-gray-900 dark:text-gray-100">
                                Camera Scanner
                            </h3>

                            {cameraError && (
                                <div className="mb-4 rounded-md bg-yellow-100 p-3 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200">
                                    <strong>Camera Issue:</strong> {cameraError}
                                </div>
                            )}

                            <div className="mb-4">
                                {/* Modified: Always render the video element, hide when not active */}
                                <video
                                    ref={videoRef}
                                    width="100%"
                                    height="auto"
                                    autoPlay
                                    playsInline
                                    muted
                                    className={`rounded-md border dark:border-gray-600 ${
                                        isScanning && isCameraActive
                                            ? ''
                                            : 'hidden'
                                    }`}
                                    style={{
                                        transform: useFrontCamera
                                            ? 'scaleX(-1)'
                                            : 'none',
                                        maxHeight: '400px',
                                    }}
                                />

                                {/* Show placeholder when camera is not active or scanning */}
                                {!(isScanning && isCameraActive) && (
                                    <div className="flex aspect-video w-full items-center justify-center rounded-md border bg-gray-200 dark:border-gray-600 dark:bg-gray-700">
                                        <div className="text-center">
                                            <svg
                                                xmlns="http://www.w3.org/2000/svg"
                                                fill="none"
                                                viewBox="0 0 24 24"
                                                strokeWidth={1.5}
                                                stroke="currentColor"
                                                className="mx-auto h-16 w-16 text-gray-400 dark:text-gray-500"
                                            >
                                                <path
                                                    strokeLinecap="round"
                                                    strokeLinejoin="round"
                                                    d="M15.75 10.5l4.72-4.72a.75.75 0 011.28.53v11.38a.75.75 0 01-1.28.53l-4.72-4.72M4.5 18.75h9a2.25 2.25 0 002.25-2.25v-9a2.25 2.25 0 00-2.25-2.25h-9A2.25 2.25 0 002.25 7.5v9A2.25 2.25 0 004.5 18.75z"
                                                />
                                            </svg>
                                            <p className="mt-2 text-sm text-gray-500 dark:text-gray-400">
                                                Camera inactive
                                            </p>
                                        </div>
                                    </div>
                                )}
                                <canvas ref={canvasRef} className="hidden" />
                            </div>

                            <div className="flex flex-wrap gap-2">
                                <button
                                    type="button"
                                    onClick={toggleScanning}
                                    className={
                                        isScanning
                                            ? dangerButtonClass
                                            : primaryButtonClass
                                    }
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
                                        className={successButtonClass}
                                        disabled={isLoading || !isCameraActive}
                                    >
                                        Switch to{' '}
                                        {useFrontCamera ? 'Back' : 'Front'}{' '}
                                        Camera
                                    </button>
                                )}
                            </div>

                            {/* Manual Input Form */}
                            <div className="mt-6">
                                <h4 className="text-md mb-3 font-medium text-gray-700 dark:text-gray-300">
                                    Manual Entry
                                </h4>
                                <form
                                    onSubmit={handleManualSubmit}
                                    className="space-y-3"
                                >
                                    <div className="flex gap-2">
                                        <input
                                            type="text"
                                            name="ticket_code_manual"
                                            id="ticket_code_manual"
                                            className="block flex-1 rounded-md border-gray-300 p-2 focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 dark:focus:border-indigo-400 dark:focus:ring-indigo-400 sm:text-sm"
                                            placeholder="Enter ticket code"
                                            value={ticketCode}
                                            onChange={(e) =>
                                                setTicketCode(e.target.value)
                                            }
                                            disabled={isLoading}
                                        />
                                        <button
                                            type="submit"
                                            className={secondaryButtonClass}
                                            disabled={
                                                isLoading || !ticketCode.trim()
                                            }
                                        >
                                            {isLoading ? (
                                                <svg
                                                    className="h-4 w-4 animate-spin"
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
                                    </div>
                                </form>
                            </div>
                        </div>

                        {/* Right Side - Scanned Tickets List */}
                        <div className="bg-white p-6 shadow-sm dark:bg-gray-800 sm:rounded-lg">
                            <div className="mb-4 flex items-center justify-between">
                                <h3 className="text-lg font-semibold text-gray-900 dark:text-gray-100">
                                    Scanned Tickets ({scannedTickets.length})
                                </h3>
                                {scannedTickets.length > 0 && (
                                    <button
                                        onClick={clearScannedTickets}
                                        className="text-sm text-red-600 hover:text-red-800 dark:text-red-400 dark:hover:text-red-200"
                                    >
                                        Clear All
                                    </button>
                                )}
                            </div>

                            <div className="max-h-96 overflow-y-auto">
                                {scannedTickets.length === 0 ? (
                                    <div className="text-center text-gray-500 dark:text-gray-400">
                                        <svg
                                            className="mx-auto h-12 w-12 text-gray-300 dark:text-gray-600"
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
                                        <h3 className="mt-2 text-sm font-medium">
                                            No tickets scanned
                                        </h3>
                                        <p className="mt-1 text-sm">
                                            Start scanning to see results here.
                                        </p>
                                    </div>
                                ) : (
                                    <div className="space-y-2">
                                        {scannedTickets.map((ticket) => (
                                            <div
                                                key={ticket.id}
                                                className={`rounded-lg border-l-4 p-3 ${
                                                    ticket.status === 'success'
                                                        ? 'border-green-500 bg-green-50 dark:bg-green-900/20'
                                                        : 'border-red-500 bg-red-50 dark:bg-red-900/20'
                                                }`}
                                            >
                                                <div className="flex items-center justify-between">
                                                    <div className="flex items-center">
                                                        <div
                                                            className={`mr-2 h-2 w-2 rounded-full ${
                                                                ticket.status ===
                                                                'success'
                                                                    ? 'bg-green-500'
                                                                    : 'bg-red-500'
                                                            }`}
                                                        />
                                                        <span className="font-mono text-sm font-medium text-gray-900 dark:text-gray-100">
                                                            {ticket.ticketCode}
                                                        </span>
                                                    </div>
                                                    <span className="text-xs text-gray-500 dark:text-gray-400">
                                                        {ticket.timestamp.toLocaleTimeString()}
                                                    </span>
                                                </div>
                                                <p
                                                    className={`mt-1 text-xs ${
                                                        ticket.status ===
                                                        'success'
                                                            ? 'text-green-700 dark:text-green-300'
                                                            : 'text-red-700 dark:text-red-300'
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
            </div>
        </AuthenticatedLayout>
    );
};

export default ScanTicket;
