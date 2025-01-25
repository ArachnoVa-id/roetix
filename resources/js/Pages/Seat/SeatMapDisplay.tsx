import React from 'react';
import { Layout, SeatItem, LabelItem } from './types';

interface Props {
  config: Layout;
  onSeatClick?: (seat: SeatItem) => void;
}

const SeatMapDisplay: React.FC<Props> = ({ config, onSeatClick }) => {
  const grid = Array(config.totalRows).fill(null)
    .map(() => Array(config.totalColumns).fill(null));

  config.items.forEach(item => {
    const rowIndex = typeof item.row === 'string' ? 
      item.row.charCodeAt(0) - 65 : item.row;
    if (rowIndex >= 0 && rowIndex < config.totalRows) {
      grid[rowIndex][item.column - 1] = item;
    }
  });

  const getSeatColor = (seat: SeatItem): string => {
    if (seat.status !== 'available') {
      switch (seat.status) {
        case 'booked': return 'bg-red-500';
        case 'in-transaction': return 'bg-yellow-500';
        case 'not_available': return 'bg-gray-400';
      }
    }

    switch (seat.category) {
      case 'diamond': return 'bg-cyan-400';
      case 'gold': return 'bg-yellow-400';
      case 'silver': return 'bg-gray-300';
      default: return 'bg-gray-200';
    }
  };

  const renderCell = (item: SeatItem | LabelItem | null, key: number) => {
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
          ${item.status !== 'available' ? 'cursor-not-allowed' : 'hover:opacity-75'}
          transition-opacity duration-200
        `}
        onClick={() => item.status === 'available' && onSeatClick?.(item)}
        disabled={item.status !== 'available'}
        title={`${item.seat_id} - ${item.category} - ${item.status}\nPrice: ${item.price}`}
      />
    );
  };

  return (
    <div className="flex flex-col items-center w-full p-4">
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
      <div className="mt-8 w-96 h-12 bg-white text-gray-800 flex items-center justify-center rounded border">
        Panggung
      </div>
    </div>
  );
};

export default SeatMapDisplay;