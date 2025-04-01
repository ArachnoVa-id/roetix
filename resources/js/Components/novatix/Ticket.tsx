import { Button } from '@/Components/ui/button';
import { TicketProps } from '@/types/ticket';
import React, { useEffect, useState } from 'react';

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

interface TicketComponentProps extends TicketProps {
    eventId: string;
    categoryColor?: string; // Add categoryColor prop
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
    id,
    type,
    code,
    data,
    qrStr,
    eventId,
    status,
    categoryColor, // Accept categoryColor prop
}: TicketComponentProps): React.ReactElement {
    // State to store the converted colors
    const [colors, setColors] = useState({
        accent: 'bg-blue-500',
        light: 'bg-blue-50',
        border: 'border-blue-200',
        text: 'text-blue-800',
    });

    // Function to handle ticket download
    const handleDownload = (): void => {
        try {
            // Dispatch a custom event for the parent component to show a toaster
            const ticketActionEvent = new CustomEvent('ticket-action', {
                detail: {
                    action: 'download',
                    ticketId: code,
                    ticketType: type,
                },
                bubbles: true,
            });
            window.dispatchEvent(ticketActionEvent);

            // Call the ticket download endpoint
            const downloadUrl = `/api/tickets/download?ticket_ids=${[id]}&event_id=${eventId}`;

            // Open in a new window/tab
            window.open(downloadUrl, '_blank');
        } catch (error) {
            console.error('Error downloading ticket:', error);

            // Dispatch error event
            const errorEvent = new CustomEvent('ticket-action', {
                detail: {
                    action: 'error',
                    ticketId: code,
                    error: 'Failed to download ticket',
                },
                bubbles: true,
            });
            window.dispatchEvent(errorEvent);
        }
    };

    // Function to convert hex color to tailwind-like color classes
    useEffect(() => {
        // Function to get color variants from the main color
        const getColorClasses = () => {
            // Default fallback - blue color scheme
            const defaultColors = {
                accent: 'bg-blue-500',
                light: 'bg-blue-50',
                border: 'border-blue-200',
                text: 'text-blue-800',
            };

            // If no categoryColor is provided, use a color based on ticket type
            if (!categoryColor) {
                const extractType = type.toLowerCase();
                if (extractType.includes('vip')) {
                    return {
                        accent: 'bg-amber-500',
                        light: 'bg-amber-50',
                        border: 'border-amber-200',
                        text: 'text-amber-800',
                    };
                } else if (extractType.includes('premium')) {
                    return {
                        accent: 'bg-purple-500',
                        light: 'bg-purple-50',
                        border: 'border-purple-200',
                        text: 'text-purple-800',
                    };
                } else {
                    return defaultColors;
                }
            }

            try {
                // If categoryColor is provided, use inline styles instead of Tailwind classes
                // This allows us to use any color from the database
                return {
                    accent: '', // We'll use inline style for accent
                    light: '', // We'll use inline style for light background
                    border: '', // We'll use inline style for border
                    text: '', // We'll use inline style for text
                };
            } catch (error) {
                console.error('Error processing category color:', error);
                return defaultColors;
            }
        };

        setColors(getColorClasses());
    }, [categoryColor, type]);

    // Create inline styles based on categoryColor
    const inlineStyles = categoryColor
        ? {
              accent: {
                  backgroundColor: categoryColor,
              },
              light: {
                  backgroundColor: categoryColor + '10', // Adding 10% opacity
              },
              border: {
                  borderColor: categoryColor + '33', // Adding 20% opacity
              },
              text: {
                  color: categoryColor,
              },
          }
        : null;

    return (
        <div
            className={`relative flex grow flex-col rounded-lg ${colors.border} transform overflow-hidden border-2 shadow-lg transition-transform ${status !== 'scanned' ? 'hover:scale-[1.02] hover:shadow-xl' : ''}`}
            style={{
                opacity: status === 'scanned' ? 0.6 : 1,
                position: 'relative',
                ...(inlineStyles
                    ? { borderColor: inlineStyles.border.borderColor }
                    : {}),
            }}
        >
            {/* Ticket header with type */}
            <div
                className={`${colors.accent} flex items-center justify-between px-4 py-2`}
                style={inlineStyles ? inlineStyles.accent : {}}
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
                    {type}
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
                        SCANNED
                    </div>
                </div>
            )}

            <div className="flex flex-row">
                {/* Left side with QR code */}
                <div
                    className={`flex w-[40%] items-center justify-center ${colors.light} border-r px-3 py-6 ${colors.border}`}
                    style={
                        inlineStyles
                            ? {
                                  ...inlineStyles.light,
                                  borderRight: `1px solid ${inlineStyles.border.borderColor}`,
                              }
                            : {}
                    }
                >
                    <div className="rounded-lg bg-white p-2 shadow-md">
                        <img
                            src={`data:image/svg+xml;base64,${qrStr}`}
                            alt="QR Code"
                            className="max-h-32 max-w-32"
                        />
                    </div>
                </div>

                {/* Right side with ticket info */}
                <div className="flex w-[60%] flex-col justify-between bg-white p-4 text-black">
                    <div className="space-y-1">
                        <RowComponent idtf="ID" content={code} />
                        <RowComponent idtf="Tanggal" content={data.date} />
                        <RowComponent idtf="Tipe" content={data.type} />
                        <RowComponent idtf="Kursi" content={data.seat} />
                        <div className="my-2 border-t border-dashed pt-2">
                            <RowComponent
                                idtf="Subtotal"
                                content={data.price}
                            />
                        </div>
                    </div>
                </div>
            </div>

            {/* Ticket footer with info */}
            <div
                className={`${colors.light} px-4 py-2 text-center text-xs ${colors.text} flex items-center justify-between font-medium`}
                style={
                    inlineStyles
                        ? {
                              ...inlineStyles.light,
                              color: inlineStyles.text.color,
                          }
                        : {}
                }
            >
                <span>Scan QR code for verification</span>
                <span>Novatix ID: {code.substring(0, 8)}</span>
                {status === 'scanned' && (
                    <span className="rounded bg-red-500 px-2 py-1 text-xs text-white">
                        SCANNED
                    </span>
                )}
            </div>
        </div>
    );
}
