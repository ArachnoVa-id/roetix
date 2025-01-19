import React from 'react';
import { SeatStatus, Category, SeatMapProps } from './types';

const getSeatColor = (status: SeatStatus, category: Category): string => {
  if (status === 'booked') return 'bg-gray-500';
  if (status === 'in_transaction') return 'bg-yellow-300';
  if (status === 'reserved') return 'bg-red-500';
  
  switch (category) {
    case 'diamond':
      return 'bg-cyan-400';
    case 'gold':
      return 'bg-yellow-400';
    case 'silver':
      return 'bg-gray-300';
    default:
      return 'bg-gray-200';
  }
};

const SeatMapDisplay: React.FC<SeatMapProps> = ({ sections, onSeatClick }) => {
  return (
    <div className="flex flex-col items-center w-full mx-auto p-4 bg-gray-800 text-white">
      {/* Legend */}
      <div className="flex gap-4 mb-6">
        <div className="flex items-center gap-2">
          <div className="w-4 h-4 bg-cyan-400"></div>
          <span>Diamond</span>
        </div>
        <div className="flex items-center gap-2">
          <div className="w-4 h-4 bg-yellow-400"></div>
          <span>Gold</span>
        </div>
        <div className="flex items-center gap-2">
          <div className="w-4 h-4 bg-gray-300"></div>
          <span>Silver</span>
        </div>
      </div>

      {/* Sections */}
      <div className="flex flex-wrap gap-8 justify-center">
        {sections.map((section) => (
          <div key={section.id} className="flex flex-col items-center">
            <h3 className="text-lg font-semibold mb-2">{section.name}</h3>
            <div className="grid gap-1">
              {section.rows.map((row, rowIndex) => (
                <div key={`${section.id}-${row}`} className="flex gap-1 items-center">
                  <span className="w-6 text-right mr-2">{row}</span>
                  <div className="flex gap-1">
                    {section.seats
                      .filter((seat) => seat.row === row)
                      .sort((a, b) => a.column - b.column)
                      .map((seat) => (
                        <button
                          key={seat.seat_id}
                          className={`
                            w-6 h-6 rounded-sm
                            ${getSeatColor(seat.status, seat.category)}
                            ${seat.status !== 'available' ? 'cursor-not-allowed' : 'hover:opacity-75'}
                            transition-opacity duration-200
                          `}
                          onClick={() => seat.status === 'available' && onSeatClick?.(seat)}
                          title={`${seat.row}${seat.column} - ${seat.category.toUpperCase()}`}
                          disabled={seat.status !== 'available'}
                        />
                      ))}
                  </div>
                </div>
              ))}
            </div>
          </div>
        ))}
      </div>

      {/* Stage */}
      <div className="mt-8 w-96 h-12 bg-white text-gray-800 flex items-center justify-center rounded">
        Panggung
      </div>
    </div>
  );
};

export default SeatMapDisplay;