import { Button } from '@/Components/ui/button';
import { TicketData } from '@/types/ticket';
import React from 'react';

interface RowComponentProps {
    idtf: string;
    content: string;
}

function RowComponent({
    idtf,
    content,
}: RowComponentProps): React.ReactElement {
    return (
        <div className="flex w-full items-center py-1">
            <p className="w-[30%] font-medium text-gray-600">{idtf}</p>
            <p className="w-[70%] font-semibold">{content}</p>
        </div>
    );
}

interface TicketComponentProps {
    ticketType: string;
    ticketCode: string;
    ticketURL: string;
    ticketData: TicketData;
    eventId: string;
    status?: string; // Add status prop
    userData?: {
        firstName: string;
        lastName: string;
        email: string;
    };
    eventInfo?: {
        location: string;
        eventDate: string;
    };
}

export default function Ticket({
    ticketType,
    ticketCode,
    ticketData,
    ticketURL,
    eventId,
    status = 'enabled', // Default to enabled if not provided
}: TicketComponentProps): React.ReactElement {
    // Function to handle ticket download
    const handleDownload = (): void => {
        try {
            // Dispatch a custom event for the parent component to show a toaster
            const ticketActionEvent = new CustomEvent('ticket-action', {
                detail: {
                    action: 'download',
                    ticketId: ticketCode,
                    ticketType: ticketType,
                },
                bubbles: true,
            });
            window.dispatchEvent(ticketActionEvent);

            // Call the ticket download endpoint
            const downloadUrl = `/api/tickets/download?ticket_id=${ticketCode}&event_id=${eventId}`;

            // Open in a new window/tab
            window.open(downloadUrl, '_blank');
        } catch (error) {
            console.error('Error downloading ticket:', error);

            // Dispatch error event
            const errorEvent = new CustomEvent('ticket-action', {
                detail: {
                    action: 'error',
                    ticketId: ticketCode,
                    error: 'Failed to download ticket',
                },
                bubbles: true,
            });
            window.dispatchEvent(errorEvent);
        }
    };

    // Determine color scheme based on ticket type
    const getTicketColors = () => {
        const type = ticketType.toLowerCase();
        if (type.includes('vip')) {
            return {
                accent: 'bg-amber-500',
                light: 'bg-amber-50',
                border: 'border-amber-200',
                text: 'text-amber-800',
            };
        } else if (type.includes('premium')) {
            return {
                accent: 'bg-purple-500',
                light: 'bg-purple-50',
                border: 'border-purple-200',
                text: 'text-purple-800',
            };
        } else {
            return {
                accent: 'bg-blue-500',
                light: 'bg-blue-50',
                border: 'border-blue-200',
                text: 'text-blue-800',
            };
        }
    };

    const getTicketStyle = () => {
        if (status === 'scanned') {
            return {
                opacity: 0.6,
                pointerEvents: 'none' as const, // Type assertion needed
                watermark: 'USED',
            };
        }

        return {
            opacity: 1,
            pointerEvents: 'auto' as const,
            watermark: null,
        };
    };

    const ticketStyle = getTicketStyle();

    const colors = getTicketColors();

    return (
        <div
            className={`relative flex grow flex-col rounded-lg ${colors.border} transform overflow-hidden border-2 shadow-lg transition-transform ${status !== 'scanned' ? 'hover:scale-[1.02] hover:shadow-xl' : ''}`}
            style={{
                opacity: status === 'scanned' ? 0.6 : 1,
                position: 'relative', // Add this to ensure absolute positioning works for the watermark
            }}
        >
            {/* Ticket header with type */}
            <div
                className={`${colors.accent} flex items-center justify-between px-4 py-2`}
            >
                <h3 className="flex items-center text-lg font-bold text-white">
                    <svg
                        xmlns="http://www.w3.org/2000/svg"
                        className="mr-2 h-5 w-5"
                        viewBox="0 0 20 20"
                        fill="currentColor"
                    >
                        <path
                            fillRule="evenodd"
                            d="M5 2a1 1 0 011-1h8a1 1 0 110 2H6a1 1 0 01-1-1zm7 2a1 1 0 00-1 1v10a1 1 0 102 0V5a1 1 0 00-1-1zM5 5a1 1 0 00-1 1v8a1 1 0 002 0V6a1 1 0 00-1-1zm0 10a1 1 0 100 2h8a1 1 0 100-2H5z"
                            clipRule="evenodd"
                        />
                    </svg>
                    {ticketType}
                </h3>
                <Button
                    onClick={handleDownload}
                    className={`rounded-full border border-white bg-white px-3 py-1 text-sm font-medium text-gray-800 ${status !== 'scanned' ? 'hover:bg-opacity-90 hover:text-black' : 'cursor-not-allowed opacity-50'}`}
                    disabled={status === 'scanned'}
                >
                    <svg
                        xmlns="http://www.w3.org/2000/svg"
                        className="mr-1 inline h-4 w-4"
                        viewBox="0 0 20 20"
                        fill="currentColor"
                    >
                        <path
                            fillRule="evenodd"
                            d="M3 17a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm3.293-7.707a1 1 0 011.414 0L9 10.586V3a1 1 0 112 0v7.586l1.293-1.293a1 1 0 111.414 1.414l-3 3a1 1 0 01-1.414 0l-3-3a1 1 0 010-1.414z"
                            clipRule="evenodd"
                        />
                    </svg>
                    Unduh
                </Button>
            </div>

            {/* Show a watermark for scanned tickets */}
            {status === 'scanned' && (
                <div className="pointer-events-none absolute inset-0 z-10 flex items-center justify-center">
                    <div className="rotate-45 transform text-6xl font-extrabold text-red-500 opacity-30">
                        USED
                    </div>
                </div>
            )}

            <div className="flex flex-row">
                {/* Left side with QR code */}
                <div
                    className={`flex w-[40%] items-center justify-center ${colors.light} border-r px-3 py-6 ${colors.border}`}
                >
                    <div className="rounded-lg bg-white p-2 shadow-md">
                        <img
                            src={`https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=${encodeURIComponent(ticketURL)}`}
                            alt="QR Code"
                            className="max-h-32 max-w-32"
                        />
                    </div>
                </div>

                {/* Right side with ticket info */}
                <div className="flex w-[60%] flex-col justify-between bg-white p-4 text-black">
                    <div className="space-y-1">
                        <RowComponent idtf="ID" content={ticketCode} />
                        <RowComponent
                            idtf="Tanggal"
                            content={ticketData.date}
                        />
                        <RowComponent idtf="Tipe" content={ticketData.type} />
                        <RowComponent idtf="Kursi" content={ticketData.seat} />
                        <div className="my-2 border-t border-dashed pt-2">
                            <RowComponent
                                idtf="Subtotal"
                                content={ticketData.price}
                            />
                        </div>
                    </div>
                </div>
            </div>

            {/* Ticket footer with info */}
            <div
                className={`${colors.light} px-4 py-2 text-center text-xs ${colors.text} flex items-center justify-between font-medium`}
            >
                <span>Scan QR code for verification</span>
                <span>Novatix ID: {ticketCode.substring(0, 8)}</span>
                {status === 'scanned' && (
                    <span className="rounded bg-red-500 px-2 py-1 text-xs text-white">
                        USED
                    </span>
                )}
            </div>
        </div>
    );
}
