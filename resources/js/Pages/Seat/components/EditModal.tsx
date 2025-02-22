// EditModal.tsx
import React, { useState } from 'react';
import { Category, SeatStatus } from '../types';

interface Props {
    mode: 'single' | 'bulk';
    selectedSeats: string[];
    onUpdate: (updates: { status: SeatStatus; category?: Category }) => void;
    onClose: () => void;
    currentStatus: SeatStatus;
    currentCategory?: Category;
}

const statusOptions = [
    { value: 'available', label: 'Available', color: 'bg-green-500' },
    { value: 'booked', label: 'Booked', color: 'bg-red-500' },
    {
        value: 'in_transaction',
        label: 'In Transaction',
        color: 'bg-yellow-500',
    },
    { value: 'not_available', label: 'Not Available', color: 'bg-gray-400' },
];

const EditModal: React.FC<Props> = ({
    mode,
    selectedSeats,
    onUpdate,
    onClose,
    currentStatus,
}) => {
    const [status, setStatus] = useState<SeatStatus>(currentStatus);

    return (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50">
            <div className="w-96 space-y-4 rounded-lg bg-white p-6">
                <h3 className="border-b pb-2 text-lg font-semibold">
                    Edit{' '}
                    {mode === 'bulk' ? `${selectedSeats.length} Seats` : 'Seat'}
                </h3>

                <div className="space-y-2">
                    <label className="block text-sm font-medium">Status</label>
                    <div className="grid grid-cols-2 gap-2">
                        {statusOptions.map((option) => (
                            <button
                                key={option.value}
                                className={`rounded px-3 py-2 ${
                                    status === option.value
                                        ? `${option.color} text-white`
                                        : 'bg-gray-100'
                                }`}
                                onClick={() =>
                                    setStatus(option.value as SeatStatus)
                                }
                            >
                                {option.label}
                            </button>
                        ))}
                    </div>
                </div>

                <div className="flex gap-2 pt-4">
                    <button
                        className="flex-1 rounded bg-blue-500 px-4 py-2 text-white hover:bg-blue-600"
                        onClick={() => onUpdate({ status })}
                    >
                        Update
                    </button>
                    <button
                        className="flex-1 rounded bg-gray-200 px-4 py-2 hover:bg-gray-300"
                        onClick={onClose}
                    >
                        Cancel
                    </button>
                </div>
            </div>
        </div>
    );
};

export default EditModal;
