// EditModal.tsx
import React, { useState } from 'react';
import { SeatStatus, Category } from '../types';

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
  { value: 'in_transaction', label: 'In Transaction', color: 'bg-yellow-500' },
  { value: 'not_available', label: 'Not Available', color: 'bg-gray-400' }
];

const EditModal: React.FC<Props> = ({ 
  mode,
  selectedSeats,
  onUpdate,
  onClose,
  currentStatus,
  currentCategory 
}) => {
  const [status, setStatus] = useState<SeatStatus>(currentStatus);

  return (
    <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
      <div className="bg-white rounded-lg p-6 space-y-4 w-96">
        <h3 className="text-lg font-semibold border-b pb-2">
          Edit {mode === 'bulk' ? `${selectedSeats.length} Seats` : 'Seat'}
        </h3>

        <div className="space-y-2">
          <label className="block text-sm font-medium">Status</label>
          <div className="grid grid-cols-2 gap-2">
            {statusOptions.map(option => (
              <button
                key={option.value}
                className={`px-3 py-2 rounded ${
                  status === option.value ? `${option.color} text-white` : 'bg-gray-100'
                }`}
                onClick={() => setStatus(option.value as SeatStatus)}
              >
                {option.label}
              </button>
            ))}
          </div>
        </div>

        <div className="pt-4 flex gap-2">
          <button
            className="flex-1 px-4 py-2 bg-blue-500 text-white rounded hover:bg-blue-600"
            onClick={() => onUpdate({ status })}
          >
            Update
          </button>
          <button
            className="flex-1 px-4 py-2 bg-gray-200 rounded hover:bg-gray-300"
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