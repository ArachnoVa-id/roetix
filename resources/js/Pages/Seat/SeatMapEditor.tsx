import React, { useState, useRef } from 'react';
import { 
  SeatMapSection, 
  Seat, 
  SeatStatus, 
  Category, 
  EditorState 
} from './types';

interface SeatMapEditorProps {
  sections: SeatMapSection[];
  onSave: (sections: SeatMapSection[]) => void;
}

const SeatMapEditor: React.FC<SeatMapEditorProps> = ({ sections: initialSections, onSave }) => {
  const [state, setState] = useState<EditorState>({
    mode: 'SINGLE',
    selectedSeats: new Set(),
    isDragging: false,
    selectedCategory: undefined
  });

  const [sections, setSections] = useState<SeatMapSection[]>(initialSections);
  const gridRef = useRef<HTMLDivElement>(null);

  const getSeatStyle = (seat: Seat) => {
    const isSelected = state.selectedSeats.has(seat.seat_id);
    const isSelectedCategory = 
      state.mode === 'CAT_GROUP' && 
      state.selectedCategory === seat.category;

    let baseColor = 'bg-gray-300';
    switch (seat.status) {
      case 'booked': baseColor = 'bg-red-500'; break;
      case 'in-transaction': baseColor = 'bg-yellow-500'; break;
      case 'not_available': baseColor = 'bg-gray-400'; break;
      default:
        switch (seat.category) {
          case 'diamond': baseColor = 'bg-cyan-400'; break;
          case 'gold': baseColor = 'bg-yellow-400'; break;
        }
    }

    return `
      w-6 h-6 rounded-sm relative
      ${isSelected || isSelectedCategory ? 'ring-2 ring-blue-500' : ''}
      ${state.isDragging ? 'opacity-50' : ''}
      ${baseColor}
      ${state.mode === 'DRAG' ? 'cursor-grab active:cursor-grabbing' : 'cursor-pointer'}
      hover:opacity-75 transition-all duration-200
    `;
  };

  const handleSeatClick = (seat: Seat, event: React.MouseEvent) => {
    if (state.mode === 'DRAG') return;

    const newSelectedSeats = new Set(state.selectedSeats);

    if (state.mode === 'SINGLE') {
      newSelectedSeats.clear();
      newSelectedSeats.add(seat.seat_id);
    } else if (state.mode === 'CAT_GROUP') {
      if (event.shiftKey && state.selectedCategory) {
        // Select all seats in the selected category
        sections.forEach(section => 
          section.seats.forEach(s => {
            if (s.category === state.selectedCategory) {
              newSelectedSeats.add(s.seat_id);
            }
          })
        );
      } else {
        // Toggle seat selection
        if (newSelectedSeats.has(seat.seat_id)) {
          newSelectedSeats.delete(seat.seat_id);
        } else {
          newSelectedSeats.add(seat.seat_id);
        }
      }
    }

    setState(prev => ({
      ...prev,
      selectedSeats: newSelectedSeats
    }));
  };

  const updateSelectedSeats = (updates: Partial<Seat>) => {
    const updatedSections = sections.map(section => ({
      ...section,
      seats: section.seats.map(seat => 
        state.selectedSeats.has(seat.seat_id)
          ? { ...seat, ...updates }
          : seat
      )
    }));

    setSections(updatedSections);
    setState(prev => ({
      ...prev,
      selectedSeats: new Set()
    }));
  };

  const handleModeChange = (mode: EditorState['mode']) => {
    setState(prev => ({
      mode,
      selectedSeats: new Set(),
      isDragging: false,
      selectedCategory: undefined
    }));
  };

  return (
    <div className="space-y-6">
      {/* Mode Selection */}
      <div className="flex gap-4 p-4 bg-gray-100 rounded-lg">
        <button
          className={`px-4 py-2 rounded ${state.mode === 'SINGLE' ? 'bg-blue-500 text-white' : 'bg-white'}`}
          onClick={() => handleModeChange('SINGLE')}
        >
          Single Select
        </button>
        <button
          className={`px-4 py-2 rounded ${state.mode === 'CAT_GROUP' ? 'bg-blue-500 text-white' : 'bg-white'}`}
          onClick={() => handleModeChange('CAT_GROUP')}
        >
          Category Group
        </button>
        <button
          className={`px-4 py-2 rounded ${state.mode === 'DRAG' ? 'bg-blue-500 text-white' : 'bg-white'}`}
          onClick={() => handleModeChange('DRAG')}
        >
          Drag Mode
        </button>
      </div>

      {/* Seat Map Rendering */}
      <div ref={gridRef} className="relative flex flex-wrap gap-8 justify-center">
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
                          onClick={(e) => handleSeatClick(seat, e)}
                          title={`${seat.seat_number} - ${seat.category} - ${seat.status} - $${seat.price}`}
                        />
                      ))}
                  </div>
                </div>
              ))}
            </div>
          </div>
        ))}
      </div>

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