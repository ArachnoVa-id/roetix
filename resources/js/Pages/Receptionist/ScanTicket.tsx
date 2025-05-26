// resources/js/Pages/Receptionist/ScanTicket.tsx
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
    const [useFrontCamera, setUseFrontCamera] = useState<boolean>(false); // Ubah ke false untuk menggunakan back camera sebagai default
    const [isCameraActive, setIsCameraActive] = useState<boolean>(false);
    const [isScanning, setIsScanning] = useState<boolean>(false);
    const [isLoading, setIsLoading] = useState<boolean>(false);
    const [cameraError, setCameraError] = useState<string>(''); // Tambahkan state untuk error kamera
    const [notification, setNotification] = useState<NotificationState>({
        type: null,
        message: '',
    });

    const videoRef = useRef<HTMLVideoElement>(null);
    const canvasRef = useRef<HTMLCanvasElement>(null);
    const currentStreamRef = useRef<MediaStream | null>(null);
    const scanIntervalRef = useRef<NodeJS.Timeout | null>(null);

    const clearNotification = useCallback(() => {
        setTimeout(() => setNotification({ type: null, message: '' }), 3000);
    }, []);

    const submitTicketCode = useCallback(
        async (codeToSubmit: string) => {
            if (isLoading) return;
            if (!event) {
                setNotification({
                    type: 'error',
                    message: 'Event data is missing.',
                });
                clearNotification();
                return;
            }
            setIsLoading(true);
            setNotification({ type: null, message: '' });

            try {
                const url = route('client.events.scan.store', {
                    client,
                    event_slug: event.slug,
                });

                const response = await axios.post<ApiSuccessResponse>(url, {
                    ticket_code: codeToSubmit,
                });
                setNotification({
                    type: 'success',
                    message:
                        response.data?.message ||
                        `Ticket ${codeToSubmit} scanned successfully!`,
                });
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
                console.error('Error submitting ticket code:', error);
            } finally {
                setIsLoading(false);
                clearNotification();
            }
        },
        [isLoading, client, event, clearNotification],
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
                video.videoHeight > 0
            ) {
                canvas.height = video.videoHeight;
                canvas.width = video.videoWidth;
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

                if (code && code.data.trim()) {
                    if (scanIntervalRef.current) {
                        clearInterval(scanIntervalRef.current);
                        scanIntervalRef.current = null;
                    }
                    setTicketCode(code.data.trim());
                    submitTicketCode(code.data.trim());
                }
            }
        }, 300);
    }, [isScanning, isCameraActive, submitTicketCode, clearNotification]);

    const stopCamera = useCallback(() => {
        console.log('Stopping camera...');

        // Stop all tracks
        if (currentStreamRef.current) {
            currentStreamRef.current.getTracks().forEach((track) => {
                track.stop();
                console.log(`Stopped track: ${track.kind}`);
            });
            currentStreamRef.current = null;
        }

        // Clear scanning interval
        if (scanIntervalRef.current) {
            clearInterval(scanIntervalRef.current);
            scanIntervalRef.current = null;
        }

        // Clear video source
        if (videoRef.current) {
            videoRef.current.srcObject = null;
            videoRef.current.pause();
        }

        setIsCameraActive(false);
        setCameraError('');
    }, []);

    const startCamera = useCallback(async () => {
        console.log('Starting camera...');
        setCameraError('');

        // Stop existing camera first
        if (currentStreamRef.current) {
            stopCamera();
        }

        if (!videoRef.current) {
            console.error('Video ref not available');
            return;
        }

        // Check if getUserMedia is supported
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
                width: { ideal: 1280 },
                height: { ideal: 720 },
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

                // Wait for the video to load metadata
                await new Promise<void>((resolve, reject) => {
                    if (!videoRef.current) {
                        reject(new Error('Video ref lost during setup'));
                        return;
                    }

                    const video = videoRef.current;

                    const onLoadedMetadata = () => {
                        console.log('Video metadata loaded');
                        video.removeEventListener(
                            'loadedmetadata',
                            onLoadedMetadata,
                        );
                        video.removeEventListener('error', onError);
                        resolve();
                    };

                    const onError = (e: Event) => {
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
                });

                // Start playing the video
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
    }, [useFrontCamera, stopCamera, clearNotification]);

    const toggleCameraFacingMode = useCallback(() => {
        console.log('Toggling camera facing mode');
        setUseFrontCamera((prev) => !prev);
    }, []);

    const toggleScanning = useCallback(() => {
        setIsScanning((prev) => {
            const newValue = !prev;
            console.log('Toggle scanning:', newValue);
            return newValue;
        });
    }, []);

    // Effect untuk start/stop camera berdasarkan isScanning
    useEffect(() => {
        console.log('Scanning state changed:', isScanning);
        if (isScanning) {
            startCamera();
        } else {
            stopCamera();
        }

        return () => {
            stopCamera();
        };
    }, [isScanning, startCamera, stopCamera]);

    // Effect untuk restart camera ketika facing mode berubah
    useEffect(() => {
        if (isScanning && isCameraActive) {
            console.log('Camera facing mode changed, restarting camera');
            startCamera();
        }
    }, [useFrontCamera, isScanning, isCameraActive, startCamera]);

    // Effect untuk start QR scanner
    useEffect(() => {
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
                clearInterval(scanIntervalRef.current);
                scanIntervalRef.current = null;
            }
        };
    }, [isScanning, isCameraActive, startQrScanner]);

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
        'px-4 py-2 rounded-md font-semibold text-xs uppercase tracking-widest focus:outline-none focus:ring-2 focus:ring-offset-2 transition ease-in-out duration-150';
    const primaryButtonClass = `${buttonBaseClass} bg-blue-600 text-white hover:bg-blue-700 active:bg-blue-800 focus:border-blue-700 focus:ring-blue-500`;
    const successButtonClass = `${buttonBaseClass} bg-green-600 text-white hover:bg-green-700 active:bg-green-800 focus:border-green-700 focus:ring-green-500`;
    const dangerButtonClass = `${buttonBaseClass} bg-red-600 text-white hover:bg-red-700 active:bg-red-800 focus:border-red-700 focus:ring-red-500`;

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
            <div className="py-12">
                <div className="mx-auto max-w-3xl sm:px-6 lg:px-8">
                    <div className="bg-white p-6 shadow-sm dark:bg-gray-800 sm:rounded-lg">
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

                        {/* Camera Error Display */}
                        {cameraError && (
                            <div className="mb-4 rounded-md bg-yellow-100 p-3 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200">
                                <strong>Camera Issue:</strong> {cameraError}
                            </div>
                        )}

                        <div className="mb-6 flex w-full items-center justify-center">
                            <div className="w-full rounded-md md:w-3/4">
                                {isScanning && isCameraActive ? (
                                    <video
                                        ref={videoRef}
                                        width="100%"
                                        height="auto"
                                        autoPlay
                                        playsInline
                                        muted
                                        className="rounded-md border dark:border-gray-600"
                                        style={{
                                            transform: useFrontCamera
                                                ? 'scaleX(-1)'
                                                : 'none',
                                        }}
                                    />
                                ) : (
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
                                <div className="mt-4 flex justify-center gap-x-3">
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
                                            disabled={
                                                isLoading || !isCameraActive
                                            }
                                        >
                                            Switch to{' '}
                                            {useFrontCamera ? 'Back' : 'Front'}{' '}
                                            Camera
                                        </button>
                                    )}
                                </div>
                            </div>
                        </div>
                        <form
                            onSubmit={handleManualSubmit}
                            className="space-y-4"
                        >
                            <div>
                                <label
                                    htmlFor="ticket_code_manual"
                                    className="block text-sm font-medium text-gray-700 dark:text-gray-300"
                                >
                                    Or Enter Ticket Code Manually
                                </label>
                                <div className="mt-1 flex rounded-md shadow-sm">
                                    <input
                                        type="text"
                                        name="ticket_code_manual"
                                        id="ticket_code_manual"
                                        className="block w-full rounded-l-md border-gray-300 p-2 focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 dark:focus:border-indigo-400 dark:focus:ring-indigo-400 sm:text-sm"
                                        placeholder="Enter ticket code"
                                        value={ticketCode}
                                        onChange={(e) =>
                                            setTicketCode(e.target.value)
                                        }
                                        disabled={isLoading}
                                    />
                                    <button
                                        type="submit"
                                        className={`${primaryButtonClass} rounded-l-none rounded-r-md`}
                                        disabled={
                                            isLoading || !ticketCode.trim()
                                        }
                                    >
                                        {isLoading ? (
                                            <svg
                                                className="-ml-1 mr-3 h-5 w-5 animate-spin text-white"
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
                                            'Submit Code'
                                        )}
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
};

export default ScanTicket;
