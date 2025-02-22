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

  // Map untuk menyimpan nomor terakhir untuk setiap baris
  const lastNumberByRow = new Map<string, number>();

  // Fungsi untuk mendapatkan nomor kursi berikutnya untuk suatu baris
  const getNextNumber = (row: string): number => {
    const lastNum = lastNumberByRow.get(row) || 0;
    const nextNum = lastNum + 1;
    lastNumberByRow.set(row, nextNum);
    return nextNum;
  };

  // Isi grid dengan kursi
  config.items.forEach(item => {
    if ('seat_id' in item) {
      const rowIndex = typeof item.row === 'string'
        ? item.row.charCodeAt(0) - 65
        : item.row;
      
      if (rowIndex >= 0 && rowIndex < config.totalRows) {
        const rowLetter = String.fromCharCode(65 + rowIndex);
        const colIndex = (item.column as number) - 1;
        const seatNumber = getNextNumber(rowLetter);
        const updatedItem = {
          ...item,
          seat_id: `${rowLetter}${seatNumber}`
        };
        
        grid[rowIndex][colIndex] = updatedItem;
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
      default: return 'bg-gray-200';
    }
  };

  const isSeatSelectable = (seat: SeatItem): boolean => {
    return seat.status === 'available';
  };

  const renderCell = (seat: SeatItem | null, colIndex: number) => {
    if (!seat) {
      return <div key={colIndex} className="w-8 h-8" />;
    }

    const isSelected = selectedSeats.some(s => s.seat_id === seat.seat_id);
    const seatColor = isSelected ? 'bg-green-400' : getSeatColor(seat);
    const isSelectable = isSeatSelectable(seat);

    return (
      <div
        key={colIndex}
        onClick={() => isSelectable && onSeatClick && onSeatClick(seat)}
        className={`
          w-8 h-8
          flex items-center justify-center
          border rounded
          ${seatColor}
          ${isSelectable ? 'cursor-pointer hover:opacity-80' : 'cursor-not-allowed opacity-75'}
          text-xs
        `}
        title={!isSelectable ? 'Kursi tidak tersedia' : ''}
      >
        {seat.seat_id}
      </div>
    );
  };

  // Balik urutan grid untuk menampilkan A dari bawah
  const reversedGrid = [...grid].reverse();

  return (
    <div className="flex flex-col items-center">
      <div className="grid gap-1">
        {reversedGrid.map((row, reversedIndex) => {
          // Hitung kembali indeks asli untuk label baris
          const originalIndex = grid.length - 1 - reversedIndex;
          return (
            <div key={reversedIndex} className="flex gap-1 items-center">
              <div className="flex gap-1">
                {row.map((seat, colIndex) => renderCell(seat, colIndex))}
              </div>
            </div>
          );
        })}
      </div>
      {/* Stage */}
      <div className="mt-20 w-[50vw] h-12 bg-white border border-gray-200 flex items-center justify-center rounded">
        Panggung
      </div>
    </div>
  );
};

export default SeatMapDisplay;