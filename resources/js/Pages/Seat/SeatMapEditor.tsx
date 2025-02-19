import React, { useState } from 'react';
import { Layout, SeatItem, LayoutItem, EditorState, Category } from './types';

interface Props {
  layout: Layout;
  onSave: (updatedSeats: any) => void;
}

type SelectionMode = 'SINGLE' | 'MULTIPLE' | 'CATEGORY';

const categoryLegends = [
  { label: 'Diamond', color: 'bg-cyan-400' },
  { label: 'Gold', color: 'bg-yellow-400' },
  { label: 'Silver', color: 'bg-gray-300' }
];

const statusLegends = [
  { label: 'Booked', color: 'bg-red-500' },
  { label: 'In Transaction', color: 'bg-yellow-500' },
  { label: 'Not Available', color: 'bg-gray-400' }
];

const SeatMapEditor: React.FC<Props> = ({ layout, onSave }) => {
  const [selectionMode, setSelectionMode] = useState<SelectionMode>('SINGLE');
  const [selectedSeats, setSelectedSeats] = useState<Set<string>>(new Set());
  const [selectedCategory, setSelectedCategory] = useState<Category | null>(null);

  const grid = Array.from({ length: layout.totalRows }, () =>
    Array(layout.totalColumns).fill(null)
  );

  layout.items.forEach(item => {
    if ('seat_id' in item) {
      const rowIndex =
        typeof item.row === 'string'
          ? item.row.charCodeAt(0) - 65
          : item.row;
      if (rowIndex >= 0 && rowIndex < layout.totalRows) {
        grid[rowIndex][(item.column as number) - 1] = item;
      }
    }
  });

  const getSeatColor = (seat: SeatItem): string => {
    const isSelected = selectedSeats.has(seat.seat_id);
    let baseColor = '';

    if (seat.status !== 'available') {
      switch (seat.status) {
        case 'booked': baseColor = 'bg-red-500'; break;
        case 'in_transaction': baseColor = 'bg-yellow-500'; break;
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
    setSelectedSeats(prev => {
      const next = new Set(prev);
      
      switch (selectionMode) {
        case 'SINGLE':
          next.clear();
          next.add(seat.seat_id);
          break;
          
        case 'MULTIPLE':
          if (next.has(seat.seat_id)) {
            next.delete(seat.seat_id);
          } else {
            next.add(seat.seat_id);
          }
          break;
          
        case 'CATEGORY':
          next.clear();
          layout.items.forEach(item => {
            if (item.type === 'seat' && item.category === seat.category) {
              next.add(item.seat_id);
            }
          });
          setSelectedCategory(seat.category);
          break;
      }
      
      return next;
    });
  };

  const handleSelectCategory = (category: Category) => {
    if (selectionMode !== 'CATEGORY') return;
    
    setSelectedSeats(new Set(
      layout.items
        .filter(item => item.type === 'seat' && item.category === category)
        .map(item => (item as SeatItem).seat_id)
    ));
    setSelectedCategory(category);
  };

  const handleModeChange = (mode: SelectionMode) => {
    setSelectionMode(mode);
    setSelectedSeats(new Set());
    setSelectedCategory(null);
  };

  const handleStatusUpdate = (status: string) => {
    const updatedSeats = layout.items
      .filter(item => 
        item.type === 'seat' && 
        selectedSeats.has((item as SeatItem).seat_id)
      )
      .map(item => ({
        seat_id: (item as SeatItem).seat_id,
        status: status
      }));

    onSave(updatedSeats);
    setSelectedSeats(new Set());
  };

  const renderCell = (item: LayoutItem | null, colIndex: number) => {
    if (item && item.type === 'seat') {
      const seat = item as SeatItem;
      return (
        <div
          key={colIndex}
          onClick={() => handleSeatClick(seat)}
          className={`w-10 h-10 flex items-center justify-center cursor-pointer border rounded ${getSeatColor(seat)}`}
        >
          {seat.seat_id}
        </div>
      );
    }
    return <div key={colIndex} className="w-10 h-10"></div>;
  };

  return (
    <div className="p-6">
      {/* Mode Selection */}
      <div className="flex gap-4 p-4 bg-gray-100 rounded-lg">
        <button
          className={`px-4 py-2 rounded ${selectionMode === 'SINGLE' ? 'bg-blue-500 text-white' : 'bg-white'}`}
          onClick={() => handleModeChange('SINGLE')}
        >
          Single Edit
        </button>
        <button
          className={`px-4 py-2 rounded ${selectionMode === 'MULTIPLE' ? 'bg-blue-500 text-white' : 'bg-white'}`}
          onClick={() => handleModeChange('MULTIPLE')}
        >
          Multiple Edit
        </button>
        <button
          className={`px-4 py-2 rounded ${selectionMode === 'CATEGORY' ? 'bg-blue-500 text-white' : 'bg-white'}`}
          onClick={() => handleModeChange('CATEGORY')}
        >
          Category Edit
        </button>
      </div>

      {/* Category Selection (only shown in Category mode) */}
      {selectionMode === 'CATEGORY' && (
        <div className="flex gap-4 p-4 bg-gray-50 rounded-lg">
          {['diamond', 'gold', 'silver'].map((category) => (
            <button
              key={category}
              className={`px-4 py-2 rounded ${
                selectedCategory === category ? 'ring-2 ring-blue-500' : ''
              } ${
                category === 'diamond' ? 'bg-cyan-400' :
                category === 'gold' ? 'bg-yellow-400' : 'bg-gray-300'
              } text-white`}
              onClick={() => handleSelectCategory(category as Category)}
            >
              {category.charAt(0).toUpperCase() + category.slice(1)}
            </button>
          ))}
        </div>
      )}

      {/* Status Buttons */}
      <div className="flex gap-4 p-4 bg-gray-50 rounded-lg">
        <button
          className="px-4 py-2 bg-green-400 text-white rounded hover:bg-green-500 disabled:opacity-50"
          onClick={() => handleStatusUpdate('available')}
          disabled={selectedSeats.size === 0}
        >
          Set Available
        </button>
        <button
          className="px-4 py-2 bg-red-400 text-white rounded hover:bg-red-500 disabled:opacity-50"
          onClick={() => handleStatusUpdate('booked')}
          disabled={selectedSeats.size === 0}
        >
          Set Booked
        </button>
        <button
          className="px-4 py-2 bg-yellow-400 text-white rounded hover:bg-yellow-500 disabled:opacity-50"
          onClick={() => handleStatusUpdate('in_transaction')}
          disabled={selectedSeats.size === 0}
        >
          Set In Transaction
        </button>
        <button
          className="px-4 py-2 bg-gray-400 text-white rounded hover:bg-gray-500 disabled:opacity-50"
          onClick={() => handleStatusUpdate('not_available')}
          disabled={selectedSeats.size === 0}
        >
          Set Not Available
        </button>
      </div>

      

      {/* Legends Section yang Menarik */}
      <div className="mb-8">
        <h3 className="text-2xl font-bold mb-4 text-center"></h3>
        <div className="grid grid-cols-2 gap-8">
          <div className="flex flex-col items-center">
            <h4 className="text-lg font-semibold mb-2">Category</h4>
            <div className="flex space-x-4">
              {categoryLegends.map((legend, i) => (
                <div key={i} className="flex flex-col items-center">
                  <div className={`w-8 h-8 ${legend.color} rounded-full shadow-md`}></div>
                  <span className="mt-1 text-sm">{legend.label}</span>
                </div>
              ))}
            </div>
          </div>
          <div className="flex flex-col items-center">
            <h4 className="text-lg font-semibold mb-2">Status</h4>
            <div className="flex space-x-4">
              {statusLegends.map((legend, i) => (
                <div key={i} className="flex flex-col items-center">
                  <div className={`w-8 h-8 ${legend.color} rounded-full shadow-md`}></div>
                  <span className="mt-1 text-sm">{legend.label}</span>
                </div>
              ))}
            </div>
          </div>
        </div>
      </div>

      {/* Grid */}
      <div className="flex flex-col items-center w-full">
        <div className="grid gap-1">
          {grid.map((row, rowIndex) => (
            <div key={rowIndex} className="flex gap-1 items-center">
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