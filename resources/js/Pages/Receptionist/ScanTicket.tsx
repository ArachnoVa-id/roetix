import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { ApiErrorResponse, ApiSuccessResponse } from '@/types/front-end';
import { PageProps } from '@inertiajs/core';
import { Head, usePage } from '@inertiajs/react';
import axios from 'axios';
import jsQR from 'jsqr';
import React, { useCallback, useEffect, useRef, useState } from 'react';
import { route } from 'ziggy-js';

// Types
interface NotificationState {
    type: 'success' | 'error' | null;
    message: string;
}

interface TicketValidationData {
    ticket_code: string;
    attendee_name?: string;
    ticket_type?: string;
    ticket_price?: number;
    order_code?: string;
    order_date?: string;
    order_created_at?: string;
    order_paid_at?: string;
    buyer_email?: string;
    buyer_name?: string;
    buyer_phone?: string;
    buyer_id_number?: string;
    buyer_sizes?: string;
    seat_number?: string;
    seat_row?: string;
    event_name?: string;
    event_location?: string;
    event_date?: string;
    event_time?: string;
    status: string;
    scanned_at?: string;
    scanned_by_id?: string;
    scanned_by_name?: string;
    scanned_by_email?: string;
    scanned_by_full_name?: string;
}

interface ScannedTicketData extends TicketValidationData {
    id: string;
    ticket_id?: string;
    scanned_at: string;
    status: string;
    message: string;
    ticket_color?: string;
    seat_position?: string;
    order_id?: string;
    total_price?: number;
    payment_gateway?: string;
    buyer_id?: string;
    buyer_whatsapp?: string;
    buyer_address?: string;
    buyer_gender?: string;
    buyer_birth_date?: string;
    event_id?: string;
    event_slug?: string;
    scanned_by_id?: string;
    scanned_by_name?: string;
    scanned_by_email?: string;
    scanned_by_full_name?: string;
}

interface ConfirmationModalState {
    isOpen: boolean;
    ticketData: TicketValidationData | null;
    isAlreadyScanned?: boolean;
}

interface DetailModalState {
    isOpen: boolean;
    ticketData: ScannedTicketData | null;
}

// Custom Hooks
const useNotification = () => {
    const [notification, setNotification] = useState<NotificationState>({
        type: null,
        message: '',
    });
    const timeoutRef = useRef<NodeJS.Timeout | null>(null);

    const showNotification = useCallback(
        (type: 'success' | 'error', message: string) => {
            if (timeoutRef.current) clearTimeout(timeoutRef.current);
            setNotification({ type, message });
            timeoutRef.current = setTimeout(
                () => setNotification({ type: null, message: '' }),
                3000,
            );
        },
        [],
    );

    const clearNotification = useCallback(() => {
        setNotification({ type: null, message: '' });
    }, []);

    return { notification, showNotification, clearNotification };
};

const useCamera = (
    showNotification: (type: 'success' | 'error', message: string) => void,
) => {
    const [isScanning, setIsScanning] = useState(false);
    const [useFrontCamera, setUseFrontCamera] = useState(false);
    const [cameraError, setCameraError] = useState('');
    const videoRef = useRef<HTMLVideoElement>(null);
    const streamRef = useRef<MediaStream | null>(null);
    const isCameraStartingRef = useRef(false);

    const stopCamera = useCallback(async () => {
        if (streamRef.current) {
            streamRef.current.getTracks().forEach((track) => track.stop());
            streamRef.current = null;
        }
        if (videoRef.current) {
            videoRef.current.srcObject = null;
            videoRef.current.load();
        }
        setCameraError('');
        isCameraStartingRef.current = false;
    }, []);

    const startCamera = useCallback(async () => {
        if (isCameraStartingRef.current) return;
        isCameraStartingRef.current = true;
        setCameraError('');

        try {
            await stopCamera();
            await new Promise((resolve) => setTimeout(resolve, 300));

            const stream = await navigator.mediaDevices.getUserMedia({
                video: {
                    facingMode: useFrontCamera ? 'user' : 'environment',
                    width: { ideal: 640, max: 1280 },
                    height: { ideal: 480, max: 720 },
                },
                audio: false,
            });

            streamRef.current = stream;
            if (videoRef.current) {
                videoRef.current.srcObject = stream;
                await videoRef.current.play();
            }
        } catch (error: unknown) {
            // const err = error as Error;
            const domErr = error as DOMException;

            const message =
                domErr.name === 'NotAllowedError'
                    ? 'Camera permission denied. Please allow camera access.'
                    : domErr.name === 'NotFoundError'
                      ? 'No camera found on this device.'
                      : 'Could not access camera.';

            setCameraError(message);
            showNotification('error', message);
            await stopCamera();
        } finally {
            isCameraStartingRef.current = false;
        }
    }, [useFrontCamera, showNotification, stopCamera]);

    const toggleScanning = useCallback(
        () => setIsScanning((prev) => !prev),
        [],
    );
    const toggleCameraFacingMode = useCallback(
        () => setUseFrontCamera((prev) => !prev),
        [],
    );

    return {
        isScanning,
        useFrontCamera,
        cameraError,
        videoRef,
        streamRef,
        startCamera,
        stopCamera,
        toggleScanning,
        toggleCameraFacingMode,
    };
};

const useQRScanner = (
    videoRef: React.RefObject<HTMLVideoElement>,
    streamRef: React.RefObject<MediaStream | null>,
    isLoading: boolean,
    onCodeScanned: (code: string) => void,
) => {
    const canvasRef = useRef<HTMLCanvasElement>(null);
    const scanIntervalRef = useRef<NodeJS.Timeout | null>(null);
    const lastScannedCodeRef = useRef('');

    const startQrScanning = useCallback(() => {
        if (!videoRef.current || !canvasRef.current || isLoading) return;

        const video = videoRef.current;
        const canvas = canvasRef.current;
        const context = canvas.getContext('2d', { willReadFrequently: true });
        if (!context) return;

        if (scanIntervalRef.current) clearInterval(scanIntervalRef.current);

        scanIntervalRef.current = setInterval(() => {
            if (
                video.readyState >= 3 &&
                video.videoWidth > 0 &&
                !video.paused &&
                !isLoading
            ) {
                try {
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
                        lastScannedCodeRef.current = code.data.trim();
                        onCodeScanned(code.data.trim());
                        // Reset after delay to allow re-scanning
                        setTimeout(() => {
                            lastScannedCodeRef.current = '';
                        }, 2000);
                    }
                } catch (error) {
                    console.warn('QR scanning error:', error);
                }
            }
        }, 200);
    }, [videoRef, isLoading, onCodeScanned]);

    const stopQrScanning = useCallback(() => {
        if (scanIntervalRef.current) {
            clearInterval(scanIntervalRef.current);
            scanIntervalRef.current = null;
        }
    }, []);

    return { canvasRef, startQrScanning, stopQrScanning };
};

// Components
const NotificationBanner: React.FC<{
    notification: NotificationState;
    onClose: () => void;
}> = ({ notification, onClose }) => {
    if (!notification.type) return null;

    const isSuccess = notification.type === 'success';
    const bgColor = isSuccess ? 'bg-green-500/90' : 'bg-red-500/90';
    const icon = isSuccess ? (
        <path
            strokeLinecap="round"
            strokeLinejoin="round"
            d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
        />
    ) : (
        <path
            strokeLinecap="round"
            strokeLinejoin="round"
            d="M9.75 9.75l4.5 4.5m0-4.5l-4.5 4.5M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
        />
    );

    return (
        <div
            className={`mb-8 flex items-center justify-between rounded-xl p-4 text-white shadow-lg transition-all duration-300 ${bgColor} backdrop-blur-sm`}
        >
            <div className="flex items-center">
                <svg
                    className="mr-3 h-6 w-6"
                    fill="none"
                    viewBox="0 0 24 24"
                    strokeWidth={1.5}
                    stroke="currentColor"
                >
                    {icon}
                </svg>
                <span className="text-base font-semibold">
                    {notification.message}
                </span>
            </div>
            <button
                onClick={onClose}
                className="ml-4 text-white opacity-80 transition-opacity hover:opacity-100"
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
    );
};

const CameraFeed: React.FC<{
    videoRef: React.RefObject<HTMLVideoElement>;
    canvasRef: React.RefObject<HTMLCanvasElement>;
    isScanning: boolean;
    useFrontCamera: boolean;
    hasStream: boolean;
}> = ({ videoRef, canvasRef, isScanning, useFrontCamera, hasStream }) => (
    <div className="relative mb-6 aspect-video w-full overflow-hidden rounded-xl border-2 border-white/50 bg-gray-900 shadow-lg">
        <video
            ref={videoRef}
            className={`h-full w-full object-cover ${isScanning && hasStream ? '' : 'hidden'} ${useFrontCamera ? 'scale-x-[-1]' : ''}`}
            autoPlay
            playsInline
            muted
        />
        {(!isScanning || !hasStream) && (
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
                    <p className="text-lg text-gray-300">Camera inactive</p>
                    <p className="mt-1 text-sm text-gray-400">
                        Click "Start Camera & Scan"
                    </p>
                </div>
            </div>
        )}
        <canvas ref={canvasRef} className="hidden" />
    </div>
);

const ManualInputForm: React.FC<{
    ticketCode: string;
    setTicketCode: (code: string) => void;
    onSubmit: (e: React.FormEvent) => void;
    isLoading: boolean;
    textPrimaryColor: string;
    primaryColor: string;
    textSecondaryColor: string;
}> = ({
    ticketCode,
    setTicketCode,
    onSubmit,
    isLoading,
    textPrimaryColor,
    primaryColor,
    textSecondaryColor,
}) => (
    <div className="w-full border-t border-white/20 pt-8">
        <h4
            className="mb-4 text-xl font-bold"
            style={{ color: textPrimaryColor }}
        >
            Manual Ticket Entry
        </h4>
        <form onSubmit={onSubmit} className="flex gap-3">
            <input
                type="text"
                style={{ color: textSecondaryColor }}
                className="block flex-1 rounded-full border border-white/30 bg-white/20 p-3 placeholder-gray-300 focus:border-blue-300 focus:ring-blue-300"
                placeholder="Enter ticket code"
                value={ticketCode}
                onChange={(e) => setTicketCode(e.target.value)}
                disabled={isLoading}
            />
            <button
                type="submit"
                style={{
                    backgroundColor: primaryColor,
                    color: textSecondaryColor,
                }}
                className="rounded-full px-6 py-3 text-sm font-bold uppercase tracking-wide shadow-lg transition-all duration-300 hover:opacity-80 focus:outline-none focus:ring-4 focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                disabled={isLoading || !ticketCode.trim()}
            >
                {isLoading ? 'Validating...' : 'Validate'}
            </button>
        </form>
    </div>
);

// Confirmation Modal Component
const ConfirmationModal: React.FC<{
    isOpen: boolean;
    ticketData: TicketValidationData | null;
    onConfirm: () => void;
    onCancel: () => void;
    isLoading: boolean;
    isAlreadyScanned?: boolean;
}> = ({
    isOpen,
    ticketData,
    onConfirm,
    onCancel,
    isLoading,
    isAlreadyScanned = false,
}) => {
    if (!isOpen || !ticketData) return null;

    const formatCurrency = (amount: number) =>
        new Intl.NumberFormat('id-ID', {
            style: 'currency',
            currency: 'IDR',
            minimumFractionDigits: 0,
        }).format(amount);

    return (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50 p-4">
            <div className="max-h-[90vh] w-full max-w-2xl overflow-hidden rounded-xl bg-white shadow-2xl">
                <div
                    className={`p-6 text-white ${isAlreadyScanned ? 'bg-orange-500' : 'bg-blue-500'}`}
                >
                    <h3 className="text-xl font-bold">
                        {isAlreadyScanned
                            ? 'Ticket Already Scanned'
                            : 'Confirm Ticket Scan'}
                    </h3>
                    <p
                        className={`mt-1 ${isAlreadyScanned ? 'text-orange-100' : 'text-blue-100'}`}
                    >
                        {isAlreadyScanned
                            ? 'This ticket has already been scanned previously'
                            : 'Please verify the ticket information before scanning'}
                    </p>
                    {isAlreadyScanned && ticketData.scanned_at && (
                        <p className="mt-2 text-sm text-orange-100">
                            <strong>Scanned at:</strong>{' '}
                            {new Date(ticketData.scanned_at).toLocaleString()}
                        </p>
                    )}
                    {isAlreadyScanned && ticketData.scanned_by_full_name && (
                        <p className="mt-2 text-sm text-orange-100">
                            <strong>Scanned by:</strong>{' '}
                            {ticketData.scanned_by_full_name}{' '}
                            {ticketData.scanned_by_email &&
                                `(${ticketData.scanned_by_email})`}
                        </p>
                    )}
                </div>
                <div className="max-h-[60vh] overflow-y-auto p-6">
                    <div className="grid grid-cols-1 gap-6 md:grid-cols-2">
                        {/* Ticket Information */}
                        <div className="space-y-4">
                            <h4 className="border-b pb-2 font-semibold text-gray-900">
                                Ticket Information
                            </h4>
                            <div>
                                <label className="block text-sm font-medium text-gray-700">
                                    Ticket Code
                                </label>
                                <p className="font-mono text-lg font-bold text-blue-600">
                                    {ticketData.ticket_code}
                                </p>
                            </div>
                            {ticketData.attendee_name && (
                                <div>
                                    <label className="block text-sm font-medium text-gray-700">
                                        Attendee Name
                                    </label>
                                    <p className="text-gray-900">
                                        {ticketData.attendee_name}
                                    </p>
                                </div>
                            )}
                            {ticketData.ticket_type && (
                                <div>
                                    <label className="block text-sm font-medium text-gray-700">
                                        Ticket Type
                                    </label>
                                    <p className="text-gray-900">
                                        {ticketData.ticket_type}
                                    </p>
                                </div>
                            )}
                            {ticketData.ticket_price && (
                                <div>
                                    <label className="block text-sm font-medium text-gray-700">
                                        Price
                                    </label>
                                    <p className="font-semibold text-gray-900">
                                        {formatCurrency(
                                            ticketData.ticket_price,
                                        )}
                                    </p>
                                </div>
                            )}
                            {ticketData.seat_number && (
                                <div>
                                    <label className="block text-sm font-medium text-gray-700">
                                        Seat
                                    </label>
                                    <p className="text-gray-900">
                                        {ticketData.seat_number}
                                        {ticketData.seat_row &&
                                            ` (Row ${ticketData.seat_row})`}
                                    </p>
                                </div>
                            )}
                        </div>

                        {/* Buyer Information */}
                        <div className="space-y-4">
                            <h4 className="border-b pb-2 font-semibold text-gray-900">
                                Buyer Information
                            </h4>
                            {ticketData.buyer_name && (
                                <div>
                                    <label className="block text-sm font-medium text-gray-700">
                                        Buyer Name
                                    </label>
                                    <p className="text-gray-900">
                                        {ticketData.buyer_name}
                                    </p>
                                </div>
                            )}
                            {ticketData.buyer_email && (
                                <div>
                                    <label className="block text-sm font-medium text-gray-700">
                                        Email
                                    </label>
                                    <p className="text-gray-900">
                                        {ticketData.buyer_email}
                                    </p>
                                </div>
                            )}
                            {ticketData.buyer_phone && (
                                <div>
                                    <label className="block text-sm font-medium text-gray-700">
                                        Phone
                                    </label>
                                    <p className="text-gray-900">
                                        {ticketData.buyer_phone}
                                    </p>
                                </div>
                            )}
                            {ticketData.buyer_id_number && (
                                <div>
                                    <label className="block text-sm font-medium text-gray-700">
                                        NIK
                                    </label>
                                    <p className="text-gray-900">
                                        {ticketData.buyer_id_number}
                                    </p>
                                </div>
                            )}
                            {ticketData.buyer_sizes && (
                                <div>
                                    <label className="block text-sm font-medium text-gray-700">
                                        Sizes
                                    </label>
                                    <p className="text-gray-900">
                                        {ticketData.buyer_sizes}
                                    </p>
                                </div>
                            )}
                            {ticketData.order_code && (
                                <div>
                                    <label className="block text-sm font-medium text-gray-700">
                                        Order Code
                                    </label>
                                    <p className="font-mono text-gray-900">
                                        {ticketData.order_code}
                                    </p>
                                </div>
                            )}
                        </div>
                    </div>

                    {/* Order Timestamps */}
                    {(ticketData.order_created_at ||
                        ticketData.order_paid_at) && (
                        <div className="mt-6 space-y-4">
                            <h4 className="border-b pb-2 font-semibold text-gray-900">
                                Order Timeline
                            </h4>
                            <div className="grid grid-cols-1 gap-4 md:grid-cols-2">
                                {ticketData.order_created_at && (
                                    <div>
                                        <label className="block text-sm font-medium text-gray-700">
                                            Order Created
                                        </label>
                                        <p className="text-gray-900">
                                            {new Date(
                                                ticketData.order_created_at,
                                            ).toLocaleString()}
                                        </p>
                                    </div>
                                )}
                                {ticketData.order_paid_at && (
                                    <div>
                                        <label className="block text-sm font-medium text-gray-700">
                                            Order Paid
                                        </label>
                                        <p className="text-gray-900">
                                            {new Date(
                                                ticketData.order_paid_at,
                                            ).toLocaleString()}
                                        </p>
                                    </div>
                                )}
                            </div>
                        </div>
                    )}

                    {/* Event Information */}
                    {(ticketData.event_name ||
                        ticketData.event_date ||
                        ticketData.event_location) && (
                        <div className="mt-6 space-y-4">
                            <h4 className="border-b pb-2 font-semibold text-gray-900">
                                Event Information
                            </h4>
                            <div className="grid grid-cols-1 gap-4 md:grid-cols-3">
                                {ticketData.event_name && (
                                    <div>
                                        <label className="block text-sm font-medium text-gray-700">
                                            Event
                                        </label>
                                        <p className="text-gray-900">
                                            {ticketData.event_name}
                                        </p>
                                    </div>
                                )}
                                {ticketData.event_date && (
                                    <div>
                                        <label className="block text-sm font-medium text-gray-700">
                                            Date
                                        </label>
                                        <p className="text-gray-900">
                                            {ticketData.event_date}
                                            {ticketData.event_time &&
                                                ` at ${ticketData.event_time}`}
                                        </p>
                                    </div>
                                )}
                                {ticketData.event_location && (
                                    <div>
                                        <label className="block text-sm font-medium text-gray-700">
                                            Location
                                        </label>
                                        <p className="text-gray-900">
                                            {ticketData.event_location}
                                        </p>
                                    </div>
                                )}
                            </div>
                        </div>
                    )}
                </div>
                <div className="flex justify-end gap-3 bg-gray-50 px-6 py-4">
                    <button
                        onClick={onCancel}
                        className="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50"
                        disabled={isLoading}
                    >
                        {isAlreadyScanned ? 'Close' : 'Cancel'}
                    </button>
                    {!isAlreadyScanned && (
                        <button
                            onClick={onConfirm}
                            className="rounded-lg bg-green-600 px-4 py-2 text-sm font-medium text-white hover:bg-green-700 disabled:opacity-50"
                            disabled={isLoading}
                        >
                            {isLoading ? 'Scanning...' : 'Confirm Scan'}
                        </button>
                    )}
                </div>
            </div>
        </div>
    );
};

// Detail Modal Component
const DetailModal: React.FC<{
    isOpen: boolean;
    ticketData: ScannedTicketData | null;
    onClose: () => void;
}> = ({ isOpen, ticketData, onClose }) => {
    if (!isOpen || !ticketData) return null;

    const formatCurrency = (amount: number) =>
        new Intl.NumberFormat('id-ID', {
            style: 'currency',
            currency: 'IDR',
            minimumFractionDigits: 0,
        }).format(amount);

    return (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50 p-4">
            <div className="max-h-[90vh] w-full max-w-4xl overflow-hidden rounded-xl bg-white shadow-2xl">
                <div className="bg-gray-800 p-6 text-white">
                    <div className="flex items-center justify-between">
                        <div>
                            <h3 className="text-xl font-bold">
                                Ticket Details
                            </h3>
                            <p className="mt-1 font-mono text-gray-300">
                                {ticketData.ticket_code}
                            </p>
                        </div>
                        <div className="text-right">
                            <div
                                className={`inline-block rounded-full px-3 py-1 text-sm font-semibold ${
                                    ticketData.status === 'success'
                                        ? 'bg-green-500 text-white'
                                        : 'bg-red-500 text-white'
                                }`}
                            >
                                {ticketData.status === 'success'
                                    ? 'Scanned'
                                    : 'Error'}
                            </div>
                            <p className="mt-1 text-sm text-gray-300">
                                {new Date(
                                    ticketData.scanned_at,
                                ).toLocaleString()}
                            </p>
                        </div>
                    </div>
                </div>
                <div className="max-h-[70vh] overflow-y-auto p-6">
                    <div className="grid grid-cols-1 gap-8 lg:grid-cols-3">
                        {/* Ticket Information */}
                        <div className="space-y-4">
                            <h4 className="border-b border-gray-200 pb-2 font-semibold text-gray-900">
                                🎫 Ticket Information
                            </h4>
                            <div className="space-y-3">
                                <div>
                                    <label className="block text-sm font-medium text-gray-700">
                                        Type
                                    </label>
                                    <div className="flex items-center gap-2">
                                        <div
                                            className="h-4 w-4 rounded"
                                            style={{
                                                backgroundColor:
                                                    ticketData.ticket_color ||
                                                    '#667eea',
                                            }}
                                        />
                                        <p className="text-gray-900">
                                            {ticketData.ticket_type}
                                        </p>
                                    </div>
                                </div>
                                {ticketData.attendee_name && (
                                    <div>
                                        <label className="block text-sm font-medium text-gray-700">
                                            Attendee
                                        </label>
                                        <p className="font-medium text-gray-900">
                                            {ticketData.attendee_name}
                                        </p>
                                    </div>
                                )}
                                {ticketData.ticket_price && (
                                    <div>
                                        <label className="block text-sm font-medium text-gray-700">
                                            Price
                                        </label>
                                        <p className="text-lg font-semibold text-gray-900">
                                            {formatCurrency(
                                                ticketData.ticket_price,
                                            )}
                                        </p>
                                    </div>
                                )}
                                <div>
                                    <label className="block text-sm font-medium text-gray-700">
                                        Seat
                                    </label>
                                    <p className="text-gray-900">
                                        {ticketData.seat_number ||
                                            'General Admission'}
                                        {ticketData.seat_row &&
                                            ` (Row ${ticketData.seat_row})`}
                                    </p>
                                </div>
                            </div>
                        </div>

                        {/* Buyer Information */}
                        <div className="space-y-4">
                            <h4 className="border-b border-gray-200 pb-2 font-semibold text-gray-900">
                                👤 Buyer Information
                            </h4>
                            <div className="space-y-3">
                                {ticketData.buyer_name && (
                                    <div>
                                        <label className="block text-sm font-medium text-gray-700">
                                            Name
                                        </label>
                                        <p className="font-medium text-gray-900">
                                            {ticketData.buyer_name}
                                        </p>
                                    </div>
                                )}
                                {ticketData.buyer_email && (
                                    <div>
                                        <label className="block text-sm font-medium text-gray-700">
                                            Email
                                        </label>
                                        <p className="text-gray-900">
                                            {ticketData.buyer_email}
                                        </p>
                                    </div>
                                )}
                                {ticketData.buyer_phone && (
                                    <div>
                                        <label className="block text-sm font-medium text-gray-700">
                                            Phone
                                        </label>
                                        <p className="text-gray-900">
                                            {ticketData.buyer_phone}
                                        </p>
                                    </div>
                                )}
                                {ticketData.buyer_id_number && (
                                    <div>
                                        <label className="block text-sm font-medium text-gray-700">
                                            NIK
                                        </label>
                                        <p className="font-mono text-gray-900">
                                            {ticketData.buyer_id_number}
                                        </p>
                                    </div>
                                )}
                                {ticketData.buyer_sizes && (
                                    <div>
                                        <label className="block text-sm font-medium text-gray-700">
                                            Sizes
                                        </label>
                                        <p className="text-gray-900">
                                            {ticketData.buyer_sizes}
                                        </p>
                                    </div>
                                )}
                            </div>
                        </div>

                        {/* Order Information */}
                        <div className="space-y-4">
                            <h4 className="border-b border-gray-200 pb-2 font-semibold text-gray-900">
                                📋 Order Details
                            </h4>
                            <div className="space-y-3">
                                {ticketData.order_code && (
                                    <div>
                                        <label className="block text-sm font-medium text-gray-700">
                                            Order Code
                                        </label>
                                        <p className="font-mono text-sm text-gray-900">
                                            {ticketData.order_code}
                                        </p>
                                    </div>
                                )}
                                {ticketData.order_created_at && (
                                    <div>
                                        <label className="block text-sm font-medium text-gray-700">
                                            Order Created
                                        </label>
                                        <p className="text-gray-900">
                                            {new Date(
                                                ticketData.order_created_at,
                                            ).toLocaleString()}
                                        </p>
                                    </div>
                                )}
                                {ticketData.order_paid_at && (
                                    <div>
                                        <label className="block text-sm font-medium text-gray-700">
                                            Order Paid
                                        </label>
                                        <p className="text-gray-900">
                                            {new Date(
                                                ticketData.order_paid_at,
                                            ).toLocaleString()}
                                        </p>
                                    </div>
                                )}
                                {ticketData.total_price && (
                                    <div>
                                        <label className="block text-sm font-medium text-gray-700">
                                            Total Price
                                        </label>
                                        <p className="font-semibold text-gray-900">
                                            {formatCurrency(
                                                ticketData.total_price,
                                            )}
                                        </p>
                                    </div>
                                )}
                            </div>
                        </div>
                    </div>

                    {/* Scan Information */}
                    <div className="mt-8 rounded-lg bg-gray-50 p-4">
                        <h4 className="mb-3 font-semibold text-gray-900">
                            🕒 Scan Information
                        </h4>
                        <div className="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-3">
                            <div>
                                <label className="block text-sm font-medium text-gray-700">
                                    Scan Status
                                </label>
                                <div className="flex items-center gap-2">
                                    <div
                                        className={`h-3 w-3 rounded-full ${
                                            ticketData.status === 'success'
                                                ? 'bg-green-400'
                                                : 'bg-red-400'
                                        }`}
                                    />
                                    <p
                                        className={`font-medium ${
                                            ticketData.status === 'success'
                                                ? 'text-green-700'
                                                : 'text-red-700'
                                        }`}
                                    >
                                        {ticketData.status === 'success'
                                            ? 'Successfully Scanned'
                                            : 'Scan Error'}
                                    </p>
                                </div>
                            </div>
                            <div>
                                <label className="block text-sm font-medium text-gray-700">
                                    Scan Time
                                </label>
                                <p className="text-gray-900">
                                    {new Date(
                                        ticketData.scanned_at,
                                    ).toLocaleString('id-ID', {
                                        weekday: 'long',
                                        year: 'numeric',
                                        month: 'long',
                                        day: 'numeric',
                                        hour: '2-digit',
                                        minute: '2-digit',
                                        second: '2-digit',
                                    })}
                                </p>
                            </div>
                            {ticketData.scanned_by_name && (
                                <div>
                                    <label className="block text-sm font-medium text-gray-700">
                                        Scanned By
                                    </label>
                                    <div className="space-y-1">
                                        <p className="font-medium text-gray-900">
                                            {ticketData.scanned_by_full_name ||
                                                ticketData.scanned_by_name}
                                        </p>
                                        {ticketData.scanned_by_email && (
                                            <p className="text-sm text-gray-600">
                                                {ticketData.scanned_by_email}
                                            </p>
                                        )}
                                    </div>
                                </div>
                            )}
                        </div>
                        {ticketData.message && (
                            <div className="mt-3">
                                <label className="block text-sm font-medium text-gray-700">
                                    Message
                                </label>
                                <p className="italic text-gray-900">
                                    {ticketData.message}
                                </p>
                            </div>
                        )}
                    </div>
                </div>
                <div className="flex justify-end bg-gray-50 px-6 py-4">
                    <button
                        onClick={onClose}
                        className="rounded-lg bg-gray-600 px-4 py-2 text-sm font-medium text-white hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-gray-500"
                    >
                        Close
                    </button>
                </div>
            </div>
        </div>
    );
};

// Main Component
const ScanTicket: React.FC = () => {
    const page = usePage<PageProps>();
    const {
        props: pageConfigProps,
        client,
        event,
        appName,
        userEndSessionDatetime,
    } = page.props;

    // State
    const [ticketCode, setTicketCode] = useState('');
    const [isLoading, setIsLoading] = useState(false);
    const [scannedTickets, setScannedTickets] = useState<ScannedTicketData[]>(
        [],
    );
    const [isFetchingHistory, setIsFetchingHistory] = useState(true);
    const [confirmationModal, setConfirmationModal] =
        useState<ConfirmationModalState>({ isOpen: false, ticketData: null });
    const [detailModal, setDetailModal] = useState<DetailModalState>({
        isOpen: false,
        ticketData: null,
    });

    // Custom hooks
    const { notification, showNotification, clearNotification } =
        useNotification();
    const camera = useCamera(showNotification);

    const isTicketAlreadyScanned = useCallback(
        (ticketCode: string) => {
            return scannedTickets.some(
                (ticket) => ticket.ticket_code === ticketCode,
            );
        },
        [scannedTickets],
    );
    // QR Scanner
    const onCodeScanned = useCallback(
        async (code: string) => {
            // Check if ticket is already scanned in current session first
            if (isTicketAlreadyScanned(code)) {
                const existingTicket = scannedTickets.find(
                    (ticket) => ticket.ticket_code === code,
                );
                if (existingTicket) {
                    // Convert ScannedTicketData to TicketValidationData for modal
                    const ticketDataForModal: TicketValidationData = {
                        ticket_code: existingTicket.ticket_code,
                        attendee_name: existingTicket.attendee_name,
                        ticket_type: existingTicket.ticket_type,
                        ticket_price: existingTicket.ticket_price,
                        order_code: existingTicket.order_code,
                        order_date: existingTicket.order_date,
                        order_created_at: existingTicket.order_created_at,
                        order_paid_at: existingTicket.order_paid_at,
                        buyer_email: existingTicket.buyer_email,
                        buyer_name: existingTicket.buyer_name,
                        buyer_phone: existingTicket.buyer_phone,
                        buyer_id_number: existingTicket.buyer_id_number,
                        buyer_sizes: existingTicket.buyer_sizes,
                        seat_number: existingTicket.seat_number,
                        seat_row: existingTicket.seat_row,
                        event_name: existingTicket.event_name,
                        event_location: existingTicket.event_location,
                        event_date: existingTicket.event_date,
                        event_time: existingTicket.event_time,
                        status: existingTicket.status,
                        scanned_at: existingTicket.scanned_at,
                        scanned_by_id: existingTicket.scanned_by_id,
                        scanned_by_name: existingTicket.scanned_by_name,
                        scanned_by_email: existingTicket.scanned_by_email,
                        scanned_by_full_name:
                            existingTicket.scanned_by_full_name,
                    };

                    setConfirmationModal({
                        isOpen: true,
                        ticketData: ticketDataForModal,
                        isAlreadyScanned: true,
                    });

                    showNotification(
                        'error',
                        `Ticket ${code} has already been scanned in this session.`,
                    );
                    return;
                }
            }

            await validateTicketCode(code);
        },
        [isTicketAlreadyScanned, scannedTickets, showNotification],
    );

    const { canvasRef, startQrScanning, stopQrScanning } = useQRScanner(
        camera.videoRef,
        camera.streamRef,
        isLoading,
        onCodeScanned,
    );

    // API Functions
    const validateTicketCode = useCallback(
        async (codeToValidate: string) => {
            if (isLoading || !codeToValidate.trim() || !event?.slug) return;

            setIsLoading(true);
            try {
                const url = route('client.scan.validate', { client });
                const response = await axios.post<
                    ApiSuccessResponse<TicketValidationData>
                >(url, {
                    ticket_code: codeToValidate.trim(),
                    event_slug: event.slug,
                });

                if (response.data?.data) {
                    setConfirmationModal({
                        isOpen: true,
                        ticketData: response.data.data,
                        isAlreadyScanned: false, // tiket valid untuk scan
                    });
                }
            } catch (error: unknown) {
                let errorMessage =
                    'An unknown error occurred while validating the ticket.';
                let scannedTicketData: ScannedTicketData | undefined =
                    undefined;

                if (axios.isAxiosError(error)) {
                    const responseData = error.response?.data as
                        | ApiErrorResponse<ScannedTicketData>
                        | undefined;
                    if (responseData?.message)
                        errorMessage = responseData.message;

                    if (error.response?.status === 409 && responseData?.data) {
                        scannedTicketData = responseData.data;

                        const ticketDataForModal: TicketValidationData = {
                            ticket_code: scannedTicketData.ticket_code,
                            attendee_name: scannedTicketData.attendee_name,
                            ticket_type: scannedTicketData.ticket_type,
                            ticket_price: scannedTicketData.ticket_price,
                            order_code: scannedTicketData.order_code,
                            order_date: scannedTicketData.order_date,
                            order_created_at:
                                scannedTicketData.order_created_at,
                            order_paid_at: scannedTicketData.order_paid_at,
                            buyer_email: scannedTicketData.buyer_email,
                            buyer_name: scannedTicketData.buyer_name,
                            buyer_phone: scannedTicketData.buyer_phone,
                            buyer_id_number: scannedTicketData.buyer_id_number,
                            buyer_sizes: scannedTicketData.buyer_sizes,
                            seat_number: scannedTicketData.seat_number,
                            seat_row: scannedTicketData.seat_row,
                            event_name: scannedTicketData.event_name,
                            event_location: scannedTicketData.event_location,
                            event_date: scannedTicketData.event_date,
                            event_time: scannedTicketData.event_time,
                            status: scannedTicketData.status,
                            scanned_at: scannedTicketData.scanned_at,
                            scanned_by_id: scannedTicketData.scanned_by_id,
                            scanned_by_name: scannedTicketData.scanned_by_name,
                            scanned_by_email:
                                scannedTicketData.scanned_by_email,
                            scanned_by_full_name:
                                scannedTicketData.scanned_by_full_name,
                        };

                        setConfirmationModal({
                            isOpen: true,
                            ticketData: ticketDataForModal,
                            isAlreadyScanned: true,
                        });

                        setScannedTickets((prev) => {
                            // Check if ticket already exists in the list
                            const existingTicketIndex = prev.findIndex(
                                (t) =>
                                    t.ticket_code ===
                                    scannedTicketData!.ticket_code,
                            );

                            if (existingTicketIndex >= 0) {
                                // Update existing ticket
                                const updatedTickets = [...prev];
                                updatedTickets[existingTicketIndex] =
                                    scannedTicketData!;
                                return updatedTickets;
                            } else {
                                // Add new ticket to the beginning of the list
                                return [scannedTicketData!, ...prev];
                            }
                        });

                        return;
                    }
                }

                showNotification('error', errorMessage);
            } finally {
                setIsLoading(false);
            }
        },
        [isLoading, client, event?.slug, showNotification],
    );

    const confirmScanTicket = useCallback(
        async (ticketData: TicketValidationData) => {
            if (!ticketData || !event?.slug) return;

            setIsLoading(true);
            setConfirmationModal({ isOpen: false, ticketData: null });

            try {
                const url = route('client.scan.store', { client });
                const response = await axios.post<
                    ApiSuccessResponse<ScannedTicketData>
                >(url, {
                    ticket_code: ticketData.ticket_code,
                    event_slug: event.slug,
                });

                const successMsg =
                    response.data?.message ||
                    `Ticket ${ticketData.ticket_code} scanned successfully!`;
                showNotification('success', successMsg);

                if (response.data?.data) {
                    // Update scanned tickets list immediately
                    setScannedTickets((prev) => {
                        // Remove any existing ticket with same ticket_code and add the new one at the beginning
                        const filteredPrev = prev.filter(
                            (t) =>
                                t.ticket_code !==
                                response.data.data!.ticket_code,
                        );
                        return [response.data.data!, ...filteredPrev];
                    });
                }
                setTicketCode('');
            } catch (error: unknown) {
                let errorMessage =
                    'An unknown error occurred while scanning the ticket.';
                if (axios.isAxiosError(error)) {
                    const responseData = error.response?.data as
                        | ApiErrorResponse<ScannedTicketData>
                        | undefined;
                    if (responseData?.message)
                        errorMessage = responseData.message;
                }
                showNotification('error', errorMessage);
            } finally {
                setIsLoading(false);
            }
        },
        [client, event?.slug, showNotification],
    );

    const fetchScannedTicketsHistory = useCallback(async () => {
        if (!event?.slug) return;

        setIsFetchingHistory(true);
        try {
            const url = route('client.scanned.history', { client });
            const response =
                await axios.get<ApiSuccessResponse<ScannedTicketData[]>>(url);
            setScannedTickets(response.data?.data || []);
        } catch (error) {
            showNotification('error', 'Failed to load scan history.');
            setScannedTickets([]);
        } finally {
            setIsFetchingHistory(false);
        }
    }, [client, event?.slug, showNotification]);

    // Event Handlers
    const handleManualSubmit = async (e: React.FormEvent) => {
        e.preventDefault();
        if (!ticketCode.trim()) {
            showNotification('error', 'Ticket code cannot be empty.');
            return;
        }
        // Check if ticket is already scanned in current session first
        if (isTicketAlreadyScanned(ticketCode.trim())) {
            const existingTicket = scannedTickets.find(
                (ticket) => ticket.ticket_code === ticketCode.trim(),
            );
            if (existingTicket) {
                // Convert ScannedTicketData to TicketValidationData for modal
                const ticketDataForModal: TicketValidationData = {
                    ticket_code: existingTicket.ticket_code,
                    attendee_name: existingTicket.attendee_name,
                    ticket_type: existingTicket.ticket_type,
                    ticket_price: existingTicket.ticket_price,
                    order_code: existingTicket.order_code,
                    order_date: existingTicket.order_date,
                    order_created_at: existingTicket.order_created_at,
                    order_paid_at: existingTicket.order_paid_at,
                    buyer_email: existingTicket.buyer_email,
                    buyer_name: existingTicket.buyer_name,
                    buyer_phone: existingTicket.buyer_phone,
                    buyer_id_number: existingTicket.buyer_id_number,
                    buyer_sizes: existingTicket.buyer_sizes,
                    seat_number: existingTicket.seat_number,
                    seat_row: existingTicket.seat_row,
                    event_name: existingTicket.event_name,
                    event_location: existingTicket.event_location,
                    event_date: existingTicket.event_date,
                    event_time: existingTicket.event_time,
                    status: existingTicket.status,
                    scanned_at: existingTicket.scanned_at,
                    scanned_by_id: existingTicket.scanned_by_id,
                    scanned_by_name: existingTicket.scanned_by_name,
                    scanned_by_email: existingTicket.scanned_by_email,
                    scanned_by_full_name: existingTicket.scanned_by_full_name,
                };

                setConfirmationModal({
                    isOpen: true,
                    ticketData: ticketDataForModal,
                    isAlreadyScanned: true,
                });

                showNotification(
                    'error',
                    `Ticket ${ticketCode.trim()} has already been scanned in this session.`,
                );
                return;
            }
        }
        await validateTicketCode(ticketCode.trim());
    };

    const handleConfirmScan = () => {
        if (confirmationModal.ticketData) {
            confirmScanTicket(confirmationModal.ticketData);
        }
    };

    const handleCancelScan = () => {
        setConfirmationModal({ isOpen: false, ticketData: null });
    };

    // Effects
    useEffect(() => {
        fetchScannedTicketsHistory();
    }, [fetchScannedTicketsHistory]);

    useEffect(() => {
        let mounted = true;

        const handleCameraControl = async () => {
            try {
                if (camera.isScanning) {
                    await camera.startCamera();
                    if (mounted) {
                        setTimeout(() => {
                            if (mounted) startQrScanning();
                        }, 1000);
                    }
                } else {
                    stopQrScanning();
                    await camera.stopCamera();
                }
            } catch (error) {
                if (mounted)
                    showNotification('error', 'Failed to control camera');
            }
        };

        handleCameraControl();

        return () => {
            mounted = false;
            stopQrScanning();
            camera.stopCamera().catch(console.error);
        };
    }, [
        camera.isScanning,
        camera.useFrontCamera,
        camera.startCamera,
        camera.stopCamera,
        startQrScanning,
        stopQrScanning,
        showNotification,
    ]);

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

            <ConfirmationModal
                isOpen={confirmationModal.isOpen}
                ticketData={confirmationModal.ticketData}
                onConfirm={handleConfirmScan}
                onCancel={handleCancelScan}
                isLoading={isLoading}
                isAlreadyScanned={confirmationModal.isAlreadyScanned} // tambahan prop
            />

            <DetailModal
                isOpen={detailModal.isOpen}
                ticketData={detailModal.ticketData}
                onClose={() =>
                    setDetailModal({ isOpen: false, ticketData: null })
                }
            />

            <div
                className="py-8 md:py-12"
                style={{
                    backgroundColor: pageConfigProps.secondary_color,
                    backgroundImage: pageConfigProps.texture
                        ? `url(${pageConfigProps.texture})`
                        : undefined,
                    backgroundRepeat: 'repeat',
                    backgroundSize: 'auto',
                }}
            >
                <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <NotificationBanner
                        notification={notification}
                        onClose={clearNotification}
                    />

                    <div className="grid grid-cols-1 gap-8 lg:grid-cols-2">
                        {/* Camera Scanner Section */}
                        <div
                            className="relative flex flex-col items-center justify-center rounded-2xl border border-white/20 bg-white/10 p-8 shadow-xl backdrop-blur-md"
                            style={{
                                color: pageConfigProps.text_primary_color,
                            }}
                        >
                            <h3 className="mb-6 text-2xl font-bold">
                                Camera Scanner
                            </h3>

                            {camera.cameraError && (
                                <div className="mb-6 w-full rounded-lg bg-yellow-100/20 p-4 backdrop-blur-sm">
                                    <strong
                                        className="block text-lg"
                                        style={{
                                            color: pageConfigProps.text_primary_color,
                                        }}
                                    >
                                        Camera Issue:
                                    </strong>
                                    <p
                                        className="text-sm"
                                        style={{
                                            color: pageConfigProps.text_primary_color,
                                        }}
                                    >
                                        {camera.cameraError}
                                    </p>
                                </div>
                            )}

                            <CameraFeed
                                videoRef={camera.videoRef}
                                canvasRef={canvasRef}
                                isScanning={camera.isScanning}
                                useFrontCamera={camera.useFrontCamera}
                                hasStream={!!camera.streamRef.current}
                            />

                            {/* Camera Controls */}
                            <div className="mb-8 flex flex-wrap justify-center gap-3">
                                <button
                                    type="button"
                                    onClick={camera.toggleScanning}
                                    style={{
                                        backgroundColor:
                                            pageConfigProps.primary_color,
                                        color: pageConfigProps.text_secondary_color,
                                    }}
                                    className={`rounded-full px-6 py-3 text-sm font-bold uppercase tracking-wide shadow-lg transition-all duration-300 focus:outline-none focus:ring-4 focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50`}
                                    disabled={isLoading}
                                >
                                    {camera.isScanning
                                        ? 'Stop Camera'
                                        : 'Start Camera & Scan'}
                                </button>

                                {camera.isScanning && (
                                    <button
                                        type="button"
                                        onClick={camera.toggleCameraFacingMode}
                                        style={{
                                            backgroundColor:
                                                pageConfigProps.primary_color,
                                            color: pageConfigProps.text_secondary_color,
                                        }}
                                        className="rounded-full px-6 py-3 text-sm font-bold uppercase tracking-wide shadow-lg transition-all duration-300 focus:outline-none focus:ring-4 disabled:cursor-not-allowed disabled:opacity-50"
                                        disabled={isLoading}
                                    >
                                        Switch to{' '}
                                        {camera.useFrontCamera
                                            ? 'Back'
                                            : 'Front'}{' '}
                                        Camera
                                    </button>
                                )}
                            </div>

                            <ManualInputForm
                                ticketCode={ticketCode}
                                setTicketCode={setTicketCode}
                                onSubmit={handleManualSubmit}
                                isLoading={isLoading}
                                textPrimaryColor={
                                    pageConfigProps.text_primary_color
                                }
                                primaryColor={pageConfigProps.primary_color}
                                textSecondaryColor={
                                    pageConfigProps.text_secondary_color
                                }
                            />
                        </div>

                        {/* Scanned Tickets History */}
                        <div className="flex flex-col rounded-2xl border border-white/20 bg-white/10 p-8 shadow-xl backdrop-blur-md">
                            <div
                                className="mb-6 flex items-center justify-between"
                                style={{
                                    color: pageConfigProps.text_primary_color,
                                }}
                            >
                                <h3 className="text-2xl font-bold">
                                    Scanned Tickets History
                                </h3>
                                <div className="flex items-center gap-2">
                                    <div
                                        className="rounded-full px-3 py-1"
                                        style={{
                                            backgroundColor:
                                                pageConfigProps.primary_color,
                                            color: pageConfigProps.text_secondary_color,
                                        }}
                                    >
                                        <span className="text-sm font-semibold">
                                            {scannedTickets.length} tickets
                                        </span>
                                    </div>
                                    {scannedTickets.length > 0 && (
                                        <button
                                            onClick={fetchScannedTicketsHistory}
                                            style={{
                                                backgroundColor:
                                                    pageConfigProps.primary_color,
                                                color: pageConfigProps.text_secondary_color,
                                            }}
                                            className="rounded-full p-2 transition-colors hover:opacity-80"
                                            title="Refresh history"
                                            aria-label="Refresh scan history"
                                        >
                                            <svg
                                                className="h-4 w-4"
                                                fill="none"
                                                viewBox="0 0 24 24"
                                                strokeWidth={1.5}
                                                stroke="currentColor"
                                            >
                                                <path
                                                    strokeLinecap="round"
                                                    strokeLinejoin="round"
                                                    d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182m0-4.991v4.99"
                                                />
                                            </svg>
                                        </button>
                                    )}
                                </div>
                            </div>

                            {isFetchingHistory ? (
                                <div
                                    className="flex flex-grow flex-col items-center justify-center py-8"
                                    style={{
                                        color: pageConfigProps.text_primary_color,
                                    }}
                                >
                                    <svg
                                        className="mx-auto h-16 w-16 animate-spin"
                                        fill="none"
                                        viewBox="0 0 24 24"
                                        style={{
                                            color: pageConfigProps.text_primary_color,
                                        }}
                                    >
                                        <circle
                                            className="opacity-25"
                                            cx="12"
                                            cy="12"
                                            r="10"
                                            stroke="currentColor"
                                            strokeWidth="4"
                                        />
                                        <path
                                            className="opacity-75"
                                            fill="currentColor"
                                            d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
                                        />
                                    </svg>
                                    <p className="mt-4 text-lg font-medium">
                                        Loading scan history...
                                    </p>
                                </div>
                            ) : scannedTickets.length === 0 ? (
                                <div
                                    className="flex flex-grow flex-col items-center justify-center py-8"
                                    style={{
                                        color: pageConfigProps.text_primary_color,
                                    }}
                                >
                                    <svg
                                        className="mx-auto mb-4 h-20 w-20"
                                        fill="none"
                                        viewBox="0 0 24 24"
                                        stroke="currentColor"
                                        style={{
                                            color: pageConfigProps.text_primary_color,
                                        }}
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
                                    <p className="mt-2 text-sm">
                                        Start scanning or manually enter a
                                        ticket code.
                                    </p>
                                </div>
                            ) : (
                                <div className="max-h-[calc(100vh-250px)] space-y-4 overflow-y-auto pr-2">
                                    {scannedTickets.map((ticket) => (
                                        <div
                                            key={ticket.id}
                                            onClick={() =>
                                                setDetailModal({
                                                    isOpen: true,
                                                    ticketData: ticket,
                                                })
                                            }
                                            style={{
                                                backgroundColor:
                                                    pageConfigProps.primary_color,
                                                color: pageConfigProps.text_secondary_color,
                                                borderLeftColor:
                                                    ticket.status === 'success'
                                                        ? 'green'
                                                        : 'red', // Border color based on status
                                            }}
                                            className={`cursor-pointer rounded-lg border-l-4 p-4 shadow-md transition-all duration-200 hover:scale-[1.02] hover:shadow-lg`}
                                        >
                                            <div className="mb-3 flex items-center justify-between">
                                                <div className="flex items-center">
                                                    <div
                                                        className={`mr-3 h-3 w-3 rounded-full ${ticket.status === 'success' ? 'bg-green-400' : 'bg-red-400'}`}
                                                    />
                                                    <span className="font-mono text-base font-bold">
                                                        {ticket.ticket_code}
                                                    </span>
                                                </div>
                                                <div
                                                    className="text-right"
                                                    style={{
                                                        color: pageConfigProps.text_secondary_color,
                                                    }}
                                                >
                                                    <span className="text-xs">
                                                        {new Date(
                                                            ticket.scanned_at,
                                                        ).toLocaleString(
                                                            'id-ID',
                                                            {
                                                                hour: '2-digit',
                                                                minute: '2-digit',
                                                                day: '2-digit',
                                                                month: '2-digit',
                                                            },
                                                        )}
                                                    </span>
                                                </div>
                                            </div>

                                            <div
                                                className="space-y-2"
                                                style={{
                                                    color: pageConfigProps.text_secondary_color,
                                                }}
                                            >
                                                {ticket.attendee_name && (
                                                    <p className="text-sm">
                                                        <strong>
                                                            Attendee:
                                                        </strong>{' '}
                                                        <span>
                                                            {
                                                                ticket.attendee_name
                                                            }
                                                        </span>
                                                    </p>
                                                )}
                                                <div
                                                    className="flex items-center justify-between"
                                                    style={{
                                                        color: pageConfigProps.text_secondary_color,
                                                    }}
                                                >
                                                    {ticket.ticket_type && (
                                                        <div className="flex items-center gap-2">
                                                            <div
                                                                className="h-3 w-3 rounded"
                                                                style={{
                                                                    backgroundColor:
                                                                        ticket.ticket_color ||
                                                                        '#667eea',
                                                                }}
                                                            />
                                                            <span className="text-sm">
                                                                {
                                                                    ticket.ticket_type
                                                                }
                                                            </span>
                                                        </div>
                                                    )}
                                                    {ticket.seat_number && (
                                                        <span className="text-sm">
                                                            📍{' '}
                                                            {ticket.seat_number}
                                                        </span>
                                                    )}
                                                </div>
                                                {ticket.buyer_name && (
                                                    <p className="text-sm">
                                                        <strong>Buyer:</strong>{' '}
                                                        {ticket.buyer_name}
                                                    </p>
                                                )}
                                                {ticket.scanned_by_name && (
                                                    <p className="text-sm">
                                                        <strong>
                                                            Scanned by:
                                                        </strong>{' '}
                                                        {ticket.scanned_by_full_name ||
                                                            ticket.scanned_by_name}
                                                    </p>
                                                )}
                                            </div>

                                            <div
                                                className="mt-3 flex items-center justify-between"
                                                style={{
                                                    color: pageConfigProps.text_secondary_color,
                                                }}
                                            >
                                                <p className={`text-sm`}>
                                                    {ticket.message}
                                                </p>
                                                <span className="text-xs transition-colors hover:text-white">
                                                    Click for details →
                                                </span>
                                            </div>
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
