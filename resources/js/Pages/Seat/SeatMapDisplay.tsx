import React from 'react';
import { Layout, SeatItem } from './types';

interface Props {
  config: Layout;
  onSeatClick?: (seat: SeatItem) => void;
  selectedSeats?: SeatItem[];
}

const SeatMapDisplay: React.FC<Props> = ({ config, onSeatClick, selectedSeats = [] }) => {
  const grid = Array.from({ length: config.totalRows }, () =>
    Array(config.totalColumns).fill(null)
  );

  // Isi grid dengan kursi
  config.items.forEach(item => {
    // Pastikan item merupakan SeatItem dengan melakukan pengecekan properti
    if ('seat_id' in item) {
      const rowIndex =
        typeof item.row === 'string'
          ? item.row.charCodeAt(0) - 65
          : item.row;
      if (rowIndex >= 0 && rowIndex < config.totalRows) {
        grid[rowIndex][(item.column as number) - 1] = item;
      }
    }
  });

  const getSeatColor = (seat: SeatItem): string => {
    if (seat.status !== 'available') {
      switch (seat.status) {
        case 'booked': return 'bg-red-500';
        case 'in_transaction': return 'bg-yellow-500';
        case 'not_available': return 'bg-gray-400';
      }
    }
    switch (seat.category) {
      case 'diamond': return 'bg-cyan-400';
      case 'gold': return 'bg-yellow-400';
      case 'silver': return 'bg-gray-300';
      default: return 'bg-lightgray';
    }
  };

  const renderCell = (seat: SeatItem, colIndex: number) => {
    const isSelected = selectedSeats.some(s => s.seat_id === seat.seat_id);
    // Gunakan kelas Tailwind untuk background warna kursi yang terpilih
    const seatColor = isSelected ? 'bg-[#24E05D]' : getSeatColor(seat);
    return (
      <div
        key={colIndex}
        onClick={() => onSeatClick && onSeatClick(seat)}
        className={`w-10 h-10 flex items-center justify-center cursor-pointer border rounded ${seatColor}`}
      >
        {seat.seat_id}
      </div>
    );
  };

  return (
    <div className="flex flex-col items-center">
      <div className="grid gap-1">
        {grid.map((row, rowIndex) => (
          <div key={rowIndex} className="flex gap-1 items-center">
            
            <div className="flex gap-1">
              {row.map((item, colIndex) =>
                item && 'seat_id' in item ? (
                  renderCell(item as SeatItem, colIndex)
                ) : (
                  <div key={colIndex} className="w-10 h-10"></div>
                )
              )}
            </div>
          </div>
        ))}
      </div>
      {/* Stage */}
      <div className="mt-8 w-96 h-12 bg-white border border-gray-200 flex items-center justify-center rounded">
        Panggung
      </div>
    </div>
  );
};

export default SeatMapDisplay;