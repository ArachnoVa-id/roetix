import React from 'react';

interface SelectedSeatsInfoProps {
    count: number;
    ticketType: string;
    status: string;
    price?: number;
}

export const SelectedSeatsInfo: React.FC<SelectedSeatsInfoProps> = ({
    count,
    ticketType,
    status,
    price,
}) => {
    if (count === 0) return null;

    return (
        <div className="mb-6 overflow-hidden rounded-xl border border-green-100 bg-green-50 shadow-sm">
            <div className="flex items-start gap-3 p-4">
                <div className="rounded-full bg-green-100 p-2 text-green-600">
                    <svg
                        xmlns="http://www.w3.org/2000/svg"
                        width="18"
                        height="18"
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        strokeWidth="2"
                        strokeLinecap="round"
                        strokeLinejoin="round"
                    >
                        <path d="M3 7V5a2 2 0 0 1 2-2h2"></path>
                        <path d="M17 3h2a2 2 0 0 1 2 2v2"></path>
                        <path d="M21 17v2a2 2 0 0 1-2 2h-2"></path>
                        <path d="M7 21H5a2 2 0 0 1-2-2v-2"></path>
                        <path d="M8 7v10"></path>
                        <path d="M16 7v10"></path>
                        <path d="M7 12h10"></path>
                    </svg>
                </div>
                <div>
                    <p className="font-medium text-green-800">
                        Selected: {count} seats
                    </p>
                    <p className="mt-1 text-sm text-green-700">
                        Will be configured as{' '}
                        <span className="font-semibold">{ticketType}</span>{' '}
                        tickets with{' '}
                        <span className="font-semibold">{status}</span> status
                        at{' '}
                        <span className="font-semibold">
                            Rp {price?.toLocaleString()}
                        </span>{' '}
                        each
                    </p>
                </div>
            </div>
        </div>
    );
};
