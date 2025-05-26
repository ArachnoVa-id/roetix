// resources/js/Pages/Receptionist/ScanTicket.tsx
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { ApiErrorResponse, ApiSuccessResponse } from '@/types/front-end'; // Pastikan path ini benar
import { PageProps as InertiaBasePageProps } from '@inertiajs/core'; // Impor PageProps dasar dari Inertia
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

// Tidak perlu ScanTicketPageInertiaProps jika PageProps global sudah benar
// Kita akan menggunakan PageProps yang diimpor dari '@inertiajs/core'
// yang telah kita perluas di file types/index.d.ts

interface NotificationState {
    type: 'success' | 'error' | null;
    message: string;
}

const ScanTicket: React.FC = () => {
    // Gunakan PageProps yang telah diperluas dari '@inertiajs/core'
    const page = usePage<InertiaBasePageProps>();
    const {
        props: pageConfigProps, // Ini adalah EventProps Anda
        client,
        event, // Ini adalah EventContext Anda, bisa jadi undefined
        appName,
        userEndSessionDatetime,
        // 'ziggy' akan diambil dari page.props.ziggy jika diperlukan
    } = page.props;

    const [ticketCode, setTicketCode] = useState<string>('');
    const [useFrontCamera, setUseFrontCamera] = useState<boolean>(true);
    const [isCameraActive, setIsCameraActive] = useState<boolean>(false);
    const [isScanning, setIsScanning] = useState<boolean>(false);
    const [isLoading, setIsLoading] = useState<boolean>(false);
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
            // Pastikan event ada sebelum melanjutkan
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
                    event_slug: event.slug, // Aman diakses setelah pemeriksaan di atas
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
        [isLoading, client, event, clearNotification], // event ditambahkan sebagai dependensi
    );

    const startQrScanner = useCallback(() => {
        if (
            !videoRef.current ||
            !canvasRef.current ||
            !isScanning ||
            !isCameraActive
        ) {
            if (scanIntervalRef.current) clearInterval(scanIntervalRef.current);
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
        if (scanIntervalRef.current) clearInterval(scanIntervalRef.current);
        scanIntervalRef.current = setInterval(() => {
            if (
                video.readyState === video.HAVE_ENOUGH_DATA &&
                isScanning &&
                isCameraActive
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
                if (code && code.data) {
                    if (scanIntervalRef.current)
                        clearInterval(scanIntervalRef.current);
                    setTicketCode(code.data);
                    submitTicketCode(code.data);
                }
            }
        }, 300);
    }, [isScanning, isCameraActive, clearNotification, submitTicketCode]);

    const stopCamera = useCallback(() => {
        if (currentStreamRef.current) {
            currentStreamRef.current
                .getTracks()
                .forEach((track) => track.stop());
            currentStreamRef.current = null;
        }
        if (scanIntervalRef.current) {
            clearInterval(scanIntervalRef.current);
            scanIntervalRef.current = null;
        }
        if (videoRef.current) {
            videoRef.current.srcObject = null;
        }
        setIsCameraActive(false);
    }, []);

    const startCamera = useCallback(async () => {
        if (currentStreamRef.current) {
            stopCamera();
        }
        if (!videoRef.current) return;
        const constraints: MediaStreamConstraints = {
            video: {
                facingMode: useFrontCamera ? 'user' : 'environment',
            },
        };
        try {
            const stream =
                await navigator.mediaDevices.getUserMedia(constraints);
            currentStreamRef.current = stream;
            if (videoRef.current) {
                videoRef.current.srcObject = stream;
                await videoRef.current.play();
            }
            setIsCameraActive(true);
            setNotification({ type: null, message: '' });
        } catch (err: unknown) {
            console.error('Error starting camera:', err);
            let camMessage =
                'Could not access camera. Please check permissions.';
            if (err instanceof Error) {
                camMessage = `Camera Error: ${err.name} - ${err.message}`;
            }
            setNotification({ type: 'error', message: camMessage });
            clearNotification();
            setIsCameraActive(false);
            setIsScanning(false);
        }
    }, [useFrontCamera, stopCamera, clearNotification]);

    const toggleCameraFacingMode = () => {
        setUseFrontCamera((prev) => !prev);
    };

    useEffect(() => {
        if (isScanning) {
            startCamera();
        } else {
            stopCamera();
        }
        return () => {
            stopCamera();
        };
    }, [isScanning, startCamera, stopCamera]);

    useEffect(() => {
        if (isScanning && isCameraActive) {
            startCamera();
        }
    }, [useFrontCamera, isScanning, isCameraActive, startCamera]);

    useEffect(() => {
        if (isScanning && isCameraActive) {
            startQrScanner();
        } else {
            if (scanIntervalRef.current) {
                clearInterval(scanIntervalRef.current);
            }
        }
        return () => {
            if (scanIntervalRef.current) {
                clearInterval(scanIntervalRef.current);
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
        submitTicketCode(ticketCode);
    };

    // Jika event tidak ada, tampilkan pesan error atau loading
    if (!event) {
        return (
            <AuthenticatedLayout
                appName={appName}
                client={client}
                props={pageConfigProps}
                userEndSessionDatetime={userEndSessionDatetime}
                // event bisa undefined di sini jika memang tidak ada
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

    // --- Sisa JSX dari sini menggunakan 'event' yang sudah pasti ada ---

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
            event={event} // event di sini sudah pasti ada dan memiliki slug
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
                        <div className="mb-6 flex w-full items-center justify-center">
                            <div className="w-full rounded-md md:w-3/4">
                                {isScanning && isCameraActive ? (
                                    <video
                                        ref={videoRef}
                                        width="100%"
                                        height="auto"
                                        autoPlay
                                        playsInline
                                        className="rounded-md border dark:border-gray-600"
                                    />
                                ) : (
                                    <div className="flex aspect-video w-full items-center justify-center rounded-md border bg-gray-200 dark:border-gray-600 dark:bg-gray-700">
                                        <svg
                                            xmlns="http://www.w3.org/2000/svg"
                                            fill="none"
                                            viewBox="0 0 24 24"
                                            strokeWidth={1.5}
                                            stroke="currentColor"
                                            className="h-16 w-16 text-gray-400 dark:text-gray-500"
                                        >
                                            <path
                                                strokeLinecap="round"
                                                strokeLinejoin="round"
                                                d="M15.75 10.5l4.72-4.72a.75.75 0 011.28.53v11.38a.75.75 0 01-1.28.53l-4.72-4.72M4.5 18.75h9a2.25 2.25 0 002.25-2.25v-9a2.25 2.25 0 00-2.25-2.25h-9A2.25 2.25 0 002.25 7.5v9A2.25 2.25 0 004.5 18.75z"
                                            />
                                        </svg>
                                    </div>
                                )}
                                <canvas ref={canvasRef} className="hidden" />
                                <div className="mt-4 flex justify-center gap-x-3">
                                    <button
                                        type="button"
                                        onClick={() =>
                                            setIsScanning((prev) => !prev)
                                        }
                                        className={
                                            isScanning
                                                ? dangerButtonClass
                                                : primaryButtonClass
                                        }
                                        disabled={isLoading}
                                    >
                                        {isScanning
                                            ? 'Stop Camera'
                                            : 'Activate Camera & Scan'}
                                    </button>
                                    {isScanning && isCameraActive && (
                                        <button
                                            type="button"
                                            onClick={toggleCameraFacingMode}
                                            className={successButtonClass}
                                            disabled={isLoading}
                                        >
                                            Flip Camera
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
