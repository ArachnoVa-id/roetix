import React from 'react';
import { Card } from '../Card';

interface SeatConfigPanelProps {
    selectedCount: number;
    selectedTicketType: string;
    onTicketTypeChange: (type: string) => void;
    selectedStatus: string;
    onStatusChange: (status: string) => void;
    currentPrice?: number;
    ticketTypes: string[];
    isDisabled?: boolean;
}

export const SeatConfigPanel: React.FC<SeatConfigPanelProps> = ({
    selectedCount,
    selectedTicketType,
    onTicketTypeChange,
    selectedStatus,
    onStatusChange,
    currentPrice,
    ticketTypes,
    isDisabled = false,
}) => {
    return (
        <Card
            title="Configure Selected Seats"
            icon={
                <svg
                    xmlns="http://www.w3.org/2000/svg"
                    width="16"
                    height="16"
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    strokeWidth="2"
                    strokeLinecap="round"
                    strokeLinejoin="round"
                >
                    <path d="M12.22 2h-.44a2 2 0 0 0-2 2v.18a2 2 0 0 1-1 1.73l-.43.25a2 2 0 0 1-2 0l-.15-.08a2 2 0 0 0-2.73.73l-.22.38a2 2 0 0 0 .73 2.73l.15.1a2 2 0 0 1 1 1.72v.51a2 2 0 0 1-1 1.74l-.15.09a2 2 0 0 0-.73 2.73l.22.38a2 2 0 0 0 2.73.73l.15-.08a2 2 0 0 1 2 0l.43.25a2 2 0 0 1 1 1.73V20a2 2 0 0 0 2 2h.44a2 2 0 0 0 2-2v-.18a2 2 0 0 1 1-1.73l.43-.25a2 2 0 0 1 2 0l.15.08a2 2 0 0 0 2.73-.73l.22-.39a2 2 0 0 0-.73-2.73l-.15-.08a2 2 0 0 1-1-1.74v-.5a2 2 0 0 1 1-1.74l.15-.09a2 2 0 0 0 .73-2.73l-.22-.38a2 2 0 0 0-2.73-.73l-.15.08a2 2 0 0 1-2 0l-.43-.25a2 2 0 0 1-1-1.73V4a2 2 0 0 0-2-2z"></path>
                    <circle cx="12" cy="12" r="3"></circle>
                </svg>
            }
        >
            <div className="p-1">
                <div className="grid grid-cols-1 gap-6">
                    <div>
                        <label
                            htmlFor="ticketType"
                            className="mb-1 block text-sm font-medium text-gray-700"
                        >
                            Category
                        </label>
                        <div className="relative">
                            <select
                                id="ticketType"
                                name="ticketType"
                                className={`mt-1 block w-full rounded-md border-gray-300 py-2 pl-3 pr-10 text-base focus:border-indigo-500 focus:outline-none focus:ring-indigo-500 sm:text-sm ${selectedCount === 0 ? 'cursor-not-allowed bg-gray-100' : ''}`}
                                value={selectedTicketType}
                                onChange={(e) =>
                                    onTicketTypeChange(e.target.value)
                                }
                                disabled={selectedCount === 0 || isDisabled}
                                aria-label="Select ticket type"
                            >
                                {ticketTypes.map((type) => (
                                    <option key={type} value={type}>
                                        {type.charAt(0).toUpperCase() +
                                            type.slice(1)}
                                    </option>
                                ))}
                            </select>
                            <div className="pointer-events-none absolute inset-y-0 right-0 flex items-center px-2 text-gray-700">
                                <svg
                                    className="h-4 w-4 fill-current"
                                    xmlns="http://www.w3.org/2000/svg"
                                    viewBox="0 0 20 20"
                                ></svg>
                            </div>
                        </div>
                    </div>

                    <div>
                        <label
                            htmlFor="seatStatus"
                            className="mb-1 block text-sm font-medium text-gray-700"
                        >
                            Status
                        </label>
                        <div className="relative">
                            <select
                                id="seatStatus"
                                name="seatStatus"
                                className={`mt-1 block w-full rounded-md border-gray-300 py-2 pl-3 pr-10 text-base focus:border-indigo-500 focus:outline-none focus:ring-indigo-500 sm:text-sm ${selectedCount === 0 ? 'cursor-not-allowed bg-gray-100' : ''}`}
                                value={selectedStatus}
                                onChange={(e) => onStatusChange(e.target.value)}
                                disabled={selectedCount === 0 || isDisabled}
                                aria-label="Select seat status"
                            >
                                <option value="available">Available</option>
                                <option value="reserved">Reserved</option>
                            </select>
                            <div className="pointer-events-none absolute inset-y-0 right-0 flex items-center px-2 text-gray-700">
                                <svg
                                    className="h-4 w-4 fill-current"
                                    xmlns="http://www.w3.org/2000/svg"
                                    viewBox="0 0 20 20"
                                ></svg>
                            </div>
                        </div>
                    </div>

                    <div>
                        <label
                            htmlFor="ticketPrice"
                            className="mb-1 block text-sm font-medium text-gray-700"
                        >
                            Price
                        </label>
                        <div className="mt-1 flex rounded-md shadow-sm">
                            <span className="inline-flex items-center rounded-l-md border border-r-0 border-gray-300 bg-gray-50 px-3 text-gray-500 sm:text-sm">
                                Rp
                            </span>
                            <input
                                type="text"
                                name="ticketPrice"
                                id="ticketPrice"
                                className="block w-full min-w-0 flex-1 rounded-none rounded-r-md border-gray-300 bg-gray-100 py-2 focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                                value={currentPrice?.toLocaleString()}
                                disabled
                                readOnly
                                aria-label="Ticket price"
                            />
                        </div>
                        <p className="mt-1 text-xs text-gray-500">
                            Prices are automatically set based on ticket
                            category
                        </p>
                    </div>
                </div>
            </div>
        </Card>
    );
};
