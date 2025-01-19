import React, { useState, useCallback, useRef } from 'react';
import { SeatMapSection, Seat, SeatStatus, Category } from './types';
import EditModal from './components/EditModal';

interface SeatMapEditorProps {
  sections: SeatMapSection[];
  onSave: (sections: SeatMapSection[]) => void;
}

type EditorMode = 'SINGLE' | 'GROUP' | 'DRAG';

const SeatMapEditor: React.FC<SeatMapEditorProps> = ({ sections: initialSections, onSave }) => {
  const [sections, setSections] = useState<SeatMapSection[]>(initialSections);
  const [mode, setMode] = useState<EditorMode>('SINGLE');
  const [selectedSeats, setSelectedSeats] = useState<Set<string>>(new Set());
  const [showEditModal, setShowEditModal] = useState(false);
  const [dragSeat, setDragSeat] = useState<string | null>(null);
  
  const gridRef = useRef<HTMLDivElement>(null);

  // Get visual style for seat
  const getSeatStyle = (seat: Seat) => {
    const isSelected = selectedSeats.has(seat.seat_id);
    const isDragging = dragSeat === seat.seat_id;

    let baseColor = 'bg-gray-300';
    if (seat.status === 'booked') baseColor = 'bg-red-500';
    else if (seat.status === 'reserved') baseColor = 'bg-yellow-500';
    else if (seat.status === 'in_transaction') baseColor = 'bg-orange-500';
    else if (seat.category === 'diamond') baseColor = 'bg-cyan-400';
    else if (seat.category === 'gold') baseColor = 'bg-yellow-400';

    return `
      w-6 h-6 rounded-sm relative
      ${isSelected ? 'ring-2 ring-blue-500' : ''}
      ${isDragging ? 'opacity-50' : ''}
      ${baseColor}
      ${mode === 'DRAG' ? 'cursor-grab active:cursor-grabbing' : 'cursor-pointer'}
      hover:opacity-75 transition-all duration-200
    `;
  };

  // Handle seat selection
  const handleSeatClick = (seat: Seat, event: React.MouseEvent) => {
    if (mode === 'DRAG') return;

    const newSelected = new Set(selectedSeats);
    
    if (mode === 'SINGLE') {
      newSelected.clear();
      newSelected.add(seat.seat_id);
      setSelectedSeats(newSelected);
      setShowEditModal(true);
    } else if (mode === 'GROUP') {
      if (event.shiftKey && selectedSeats.size > 0) {
        newSelected.add(seat.seat_id);
      } else if (newSelected.has(seat.seat_id)) {
        newSelected.delete(seat.seat_id);
      } else {
        newSelected.add(seat.seat_id);
      }
      setSelectedSeats(newSelected);
      if (newSelected.size > 0) {
        setShowEditModal(true);
      }
    }
  };

  // Get current values for edit modal
  const getCurrentValues = () => {
    const firstSelectedSeat = sections
      .flatMap(s => s.seats)
      .find(seat => selectedSeats.has(seat.seat_id));

    return {
      status: firstSelectedSeat?.status || 'available',
      category: firstSelectedSeat?.category || 'silver',
      price: firstSelectedSeat?.price || 0
    };
  };

  // Handle updates from edit modal
  const handleSeatUpdate = (updates: {
    status?: SeatStatus;
    category?: Category;
    price?: number;
  }) => {
    const updatedSections = sections.map(section => ({
      ...section,
      seats: section.seats.map(seat => 
        selectedSeats.has(seat.seat_id)
          ? { ...seat, ...updates }
          : seat
      )
    }));

    setSections(updatedSections);
    setSelectedSeats(new Set());
    setShowEditModal(false);
  };

  // Drag and drop handlers
  const handleDragStart = (e: React.MouseEvent, seatId: string) => {
    if (mode !== 'DRAG') return;
    setDragSeat(seatId);
  };

  const handleDragEnd = (e: React.MouseEvent) => {
    if (!dragSeat || !gridRef.current) return;

    const rect = gridRef.current.getBoundingClientRect();
    const x = e.clientX - rect.left;
    const y = e.clientY - rect.top;
    
    // Calculate new position
    const cellSize = 24; // w-6 = 24px
    const newRow = String.fromCharCode(65 + Math.floor(y / cellSize));
    const newColumn = Math.floor(x / cellSize) + 1;

    // Update seat position
    const updatedSections = sections.map(section => ({
      ...section,
      seats: section.seats.map(seat =>
        seat.seat_id === dragSeat
          ? { ...seat, row: newRow, column: newColumn }
          : seat
      )
    }));

    setSections(updatedSections);
    setDragSeat(null);
  };

  return (
    <div className="space-y-6">
      {/* Mode Selection */}
      <div className="flex gap-4 p-4 bg-gray-100 rounded-lg">
        <button
          className={`px-4 py-2 rounded ${mode === 'SINGLE' ? 'bg-blue-500 text-white' : 'bg-white'}`}
          onClick={() => {
            setMode('SINGLE');
            setSelectedSeats(new Set());
          }}
        >
          Single Select
        </button>
        <button
          className={`px-4 py-2 rounded ${mode === 'GROUP' ? 'bg-blue-500 text-white' : 'bg-white'}`}
          onClick={() => {
            setMode('GROUP');
            setSelectedSeats(new Set());
          }}
        >
          Group Select
        </button>
        <button
          className={`px-4 py-2 rounded ${mode === 'DRAG' ? 'bg-blue-500 text-white' : 'bg-white'}`}
          onClick={() => {
            setMode('DRAG');
            setSelectedSeats(new Set());
          }}
        >
          Drag Mode
        </button>
      </div>

      {/* Instructions */}
      <div className="text-sm text-gray-600">
        {mode === 'SINGLE' && 'Click a seat to edit its properties'}
        {mode === 'GROUP' && 'Select multiple seats to edit. Hold Shift to select multiple seats'}
        {mode === 'DRAG' && 'Drag seats to new positions'}
      </div>

      {/* Seat Map */}
      <div 
        ref={gridRef}
        className="relative flex flex-wrap gap-8 justify-center"
        onMouseUp={handleDragEnd}
        onMouseLeave={handleDragEnd}
      >
        {sections.map((section) => (
          <div key={section.id} className="flex flex-col items-center">
            <h3 className="text-lg font-semibold mb-2">{section.name}</h3>
            <div className="grid gap-1">
              {section.rows.map((row) => (
                <div key={`${section.id}-${row}`} className="flex gap-1 items-center">
                  <span className="w-6 text-right mr-2">{row}</span>
                  <div className="flex gap-1">
                  {section.seats
                      .filter((seat) => seat.row === row)
                      .sort((a, b) => a.column - b.column)
                      .map((seat) => (
                        <button
                          key={seat.seat_id}
                          className={getSeatStyle(seat)}
                          onMouseDown={(e) => handleDragStart(e, seat.seat_id)}
                          onClick={(e) => handleSeatClick(seat, e)}
                          title={`${seat.row}${seat.column} - ${seat.category.toUpperCase()} - ${seat.status} - $${seat.price}`}
                        />
                      ))}
                  </div>
                </div>
              ))}
            </div>
          </div>
        ))}
      </div>

      {/* Edit Modal */}
      {showEditModal && (
        <EditModal
          selectedSeats={selectedSeats}
          onUpdate={handleSeatUpdate}
          onClose={() => {
            setShowEditModal(false);
            setSelectedSeats(new Set());
          }}
          currentValues={getCurrentValues()}
          mode={mode === 'GROUP' ? 'GROUP' : 'SINGLE'}
        />
      )}

      {/* Save Button */}
      <div className="flex justify-end mt-6">
        <button
          className="px-6 py-2 bg-green-500 text-white rounded hover:bg-green-600"
          onClick={() => onSave(sections)}
        >
          Save Changes
        </button>
      </div>
    </div>
  );
};

export default SeatMapEditor;