import React, { useState } from 'react';
import { Layout, SeatItem, LayoutItem, EditorState } from './types';

interface Props {
  layout: Layout;
  onSave: (seats: any) => void;
}

const SeatMapEditor: React.FC<Props> = ({ layout, onSave }) => {
  const [selectedSeats, setSelectedSeats] = useState<Set<string>>(new Set());
  const [state, setState] = useState<EditorState>({
    mode: 'SINGLE',
    selectedSeats: new Set(),
    isDragging: false
  });

  const grid = Array(layout.totalRows).fill(null)
    .map(() => Array(layout.totalColumns).fill(null));

  layout.items.forEach(item => {
    const rowIndex = typeof item.row === 'string' ?
      item.row.charCodeAt(0) - 65 : item.row;
    if (rowIndex >= 0 && rowIndex < layout.totalRows) {
      grid[rowIndex][item.column - 1] = item;
    }
  });

  const getSeatColor = (seat: SeatItem): string => {
    const isSelected = state.selectedSeats.has(seat.seat_id);
    let baseColor = '';

    if (seat.status !== 'available') {
      switch (seat.status) {
        case 'booked': baseColor = 'bg-red-500'; break;
        case 'in-transaction': baseColor = 'bg-yellow-500'; break;
        case 'not_available': baseColor = 'bg-gray-400'; break;
      }
    } else {
      switch (seat.category) {
        case 'diamond': baseColor = 'bg-cyan-400'; break;
        case 'gold': baseColor = 'bg-yellow-400'; break;
        case 'silver': baseColor = 'bg-gray-300'; break;
        default: baseColor = 'bg-gray-200';
      }
    }

    return `${baseColor} ${isSelected ? 'ring-2 ring-blue-500' : ''}`;
  };

  const handleSeatClick = (seat: SeatItem) => {
    if (state.mode === 'DRAG') return;

    setState(prev => {
      const newSelectedSeats = new Set(prev.selectedSeats);
      if (newSelectedSeats.has(seat.seat_id)) {
        newSelectedSeats.delete(seat.seat_id);
      } else {
        newSelectedSeats.add(seat.seat_id);
      }
      return { ...prev, selectedSeats: newSelectedSeats };
    });
  };

  const handleStatusUpdate = (status: string) => {
    const updatedSeats = layout.items
      .filter(item => 
        item.type === 'seat' && 
        state.selectedSeats.has((item as SeatItem).seat_id)
      )
      .map(item => ({
        seat_id: (item as SeatItem).seat_id,
        status: status
      }));

    onSave(updatedSeats);
    setState(prev => ({ ...prev, selectedSeats: new Set() }));
  };

  const renderCell = (item: LayoutItem | null, key: number) => {
    if (!item) return <div key={key} className="w-6 h-6" />;

    if (item.type === 'label') {
      return (
        <div key={key} className="w-6 h-6 flex items-center justify-center text-sm font-medium">
          {item.text}
        </div>
      );
    }

    return (
      <button
        key={key}
        className={`
          w-6 h-6 rounded-sm
          ${getSeatColor(item)}
          hover:opacity-75
          transition-opacity duration-200
        `}
        onClick={() => handleSeatClick(item)}
        title={`${item.seat_id} - ${item.category} - ${item.status}\nPrice: ${item.price}`}
      />
    );
  };

  return (
    <div className="space-y-6">
      {/* Status Buttons */}
      <div className="flex gap-4 p-4 bg-gray-50 rounded-lg">
        <button
          className="px-4 py-2 bg-green-400 text-white rounded hover:bg-green-500"
          onClick={() => handleStatusUpdate('available')}
          disabled={state.selectedSeats.size === 0}
        >
          Set Available
        </button>
        <button
          className="px-4 py-2 bg-red-400 text-white rounded hover:bg-red-500"
          onClick={() => handleStatusUpdate('booked')}
          disabled={state.selectedSeats.size === 0}
        >
          Set Booked
        </button>
        <button
          className="px-4 py-2 bg-yellow-400 text-white rounded hover:bg-yellow-500"
          onClick={() => handleStatusUpdate('in-transaction')}
          disabled={state.selectedSeats.size === 0}
        >
          Set In Transaction
        </button>
      </div>

      {/* Legend */}
      <div className="flex gap-4 mb-6">
        {[
          { status: 'booked', color: 'bg-red-500', label: 'Booked' },
          { status: 'in-transaction', color: 'bg-yellow-500', label: 'In Transaction' },
          { status: 'not_available', color: 'bg-gray-400', label: 'Not Available' },
          { category: 'diamond', color: 'bg-cyan-400', label: 'Diamond' },
          { category: 'gold', color: 'bg-yellow-400', label: 'Gold' },
          { category: 'silver', color: 'bg-gray-300', label: 'Silver' }
        ].map((item, i) => (
          <div key={i} className="flex items-center gap-2">
            <div className={`w-4 h-4 ${item.color}`} />
            <span>{item.label}</span>
          </div>
        ))}
      </div>

      {/* Grid */}
      <div className="flex flex-col items-center w-full">
        <div className="grid gap-1">
          {grid.map((row, rowIndex) => (
            <div key={rowIndex} className="flex gap-1 items-center">
              <span className="w-6 text-right mr-2">
                {String.fromCharCode(65 + rowIndex)}
              </span>
              <div className="flex gap-1">
                {row.map((item, colIndex) => renderCell(item, colIndex))}
              </div>
            </div>
          ))}
        </div>

        {/* Stage */}
        <div className="mt-8 w-96 h-12 bg-white border border-gray-200 flex items-center justify-center rounded">
          Panggung
        </div>
      </div>
    </div>
  );
};

export default SeatMapEditor;