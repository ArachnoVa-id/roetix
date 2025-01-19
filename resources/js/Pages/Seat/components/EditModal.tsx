import React, { useState } from 'react';
import { SeatStatus, Category } from '../types';

interface EditModalProps {
  selectedSeats: Set<string>;
  onUpdate: (updates: {
    status?: SeatStatus;
    category?: Category;
    price?: number;
  }) => void;
  onClose: () => void;
  currentValues: {
    status: SeatStatus;
    category: Category;
    price: number;
  };
  mode: 'SINGLE' | 'GROUP';
}

const EditModal: React.FC<EditModalProps> = ({ 
  selectedSeats, 
  onUpdate, 
  onClose, 
  currentValues,
  mode 
}) => {
  const [status, setStatus] = useState<SeatStatus | undefined>(undefined);
  const [category, setCategory] = useState<Category | undefined>(undefined);
  const [price, setPrice] = useState<string>('');

  const handleUpdate = () => {
    const updates: any = {};
    if (status) updates.status = status;
    if (category) updates.category = category;
    if (price) updates.price = Number(price);
    
    onUpdate(updates);
  };

  return (
    <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
      <div className="bg-white rounded-lg p-6 space-y-4 w-96">
        <h3 className="text-lg font-semibold border-b pb-2">
          Edit {mode === 'GROUP' ? `${selectedSeats.size} Seats` : 'Seat'}
        </h3>

        {/* Status Selection */}
        <div className="space-y-2">
          <label className="block text-sm font-medium text-gray-700">Status</label>
          <div className="grid grid-cols-2 gap-2">
            <button
              className={`px-3 py-2 rounded ${status === 'available' ? 'bg-green-500 text-white' : 'bg-gray-100'}`}
              onClick={() => setStatus('available')}
            >
              Available
            </button>
            <button
              className={`px-3 py-2 rounded ${status === 'booked' ? 'bg-red-500 text-white' : 'bg-gray-100'}`}
              onClick={() => setStatus('booked')}
            >
              Booked
            </button>
            <button
              className={`px-3 py-2 rounded ${status === 'reserved' ? 'bg-yellow-500 text-white' : 'bg-gray-100'}`}
              onClick={() => setStatus('reserved')}
            >
              Reserved
            </button>
            <button
              className={`px-3 py-2 rounded ${status === 'in_transaction' ? 'bg-orange-500 text-white' : 'bg-gray-100'}`}
              onClick={() => setStatus('in_transaction')}
            >
              In Transaction
            </button>
          </div>
        </div>

        {/* Category Selection */}
        <div className="space-y-2">
          <label className="block text-sm font-medium text-gray-700">Category</label>
          <div className="grid grid-cols-3 gap-2">
            <button
              className={`px-3 py-2 rounded ${category === 'diamond' ? 'bg-cyan-500 text-white' : 'bg-gray-100'}`}
              onClick={() => setCategory('diamond')}
            >
              Diamond
            </button>
            <button
              className={`px-3 py-2 rounded ${category === 'gold' ? 'bg-yellow-500 text-white' : 'bg-gray-100'}`}
              onClick={() => setCategory('gold')}
            >
              Gold
            </button>
            <button
              className={`px-3 py-2 rounded ${category === 'silver' ? 'bg-gray-500 text-white' : 'bg-gray-100'}`}
              onClick={() => setCategory('silver')}
            >
              Silver
            </button>
          </div>
        </div>

        {/* Price Input */}
        <div className="space-y-2">
          <label className="block text-sm font-medium text-gray-700">Price</label>
          <input
            type="number"
            className="w-full px-3 py-2 border rounded focus:ring-blue-500 focus:border-blue-500"
            placeholder="Enter price"
            value={price}
            onChange={(e) => setPrice(e.target.value)}
          />
        </div>

        {/* Current Values Display */}
        <div className="text-sm text-gray-500 space-y-1 bg-gray-50 p-2 rounded">
          <p>Current Values:</p>
          <p>Status: {currentValues.status}</p>
          <p>Category: {currentValues.category}</p>
          <p>Price: {currentValues.price}</p>
        </div>

        {/* Action Buttons */}
        <div className="flex gap-2 pt-4">
          <button
            className="flex-1 px-4 py-2 bg-blue-500 text-white rounded hover:bg-blue-600"
            onClick={handleUpdate}
          >
            Update {mode === 'GROUP' ? 'All' : 'Seat'}
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