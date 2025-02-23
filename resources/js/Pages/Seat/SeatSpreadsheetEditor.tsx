import React, { useState, useEffect } from 'react';
import { router, useForm } from '@inertiajs/react';
import { AlertDialog, AlertDialogAction, AlertDialogCancel, AlertDialogContent, AlertDialogDescription, AlertDialogFooter, AlertDialogHeader, AlertDialogTitle, AlertDialogTrigger } from "../../Components/ui/alert-dialog";
import { PlusCircle, Trash2, Save } from 'lucide-react';
import { Layout, SeatItem, SeatStatus, Category } from './types';

interface Props {
  layout: Layout;
}

interface EditableSeatItem extends SeatItem {
  isNew: boolean;
  isDirty: boolean;
}

const SeatSpreadsheetEditor: React.FC<Props> = ({ layout }) => {
  const [seats, setSeats] = useState<EditableSeatItem[]>([]);
  const [selectedCell, setSelectedCell] = useState<number | null>(null);
  
  const { data, setData, post, processing } = useForm({
    seats: [] as any[]
  });

  useEffect(() => {
    // Transform layout items into grid format
    const seatItems = layout.items
      .filter((item): item is SeatItem => item.type === 'seat')
      .map(item => ({
        ...item,
        isNew: false,
        isDirty: false
      }))
      .sort((a, b) => {
        // Sort by row then column
        const rowA = typeof a.row === 'string' ? a.row.charCodeAt(0) : a.row;
        const rowB = typeof b.row === 'string' ? b.row.charCodeAt(0) : b.row;
        if (rowA !== rowB) return rowA - rowB;
        return a.column - b.column;
      });
      
    setSeats(seatItems);
  }, [layout]);

  const handleCellClick = (index: number) => {
    setSelectedCell(index);
  };

  const handleStatusChange = (index: number, newStatus: SeatStatus) => {
    const updatedSeats = [...seats];
    updatedSeats[index] = {
      ...updatedSeats[index],
      status: newStatus,
      isDirty: true
    };
    setSeats(updatedSeats);
  };

  const handleCategoryChange = (index: number, newCategory: Category) => {
    const updatedSeats = [...seats];
    updatedSeats[index] = {
      ...updatedSeats[index],
      category: newCategory,
      isDirty: true
    };
    setSeats(updatedSeats);
  };

  const handleAddRow = () => {
    const lastSeat = seats[seats.length - 1];
    const newRow = lastSeat.row;
    const newColumn = lastSeat.column + 1;
    
    const newSeat: EditableSeatItem = {
      type: 'seat',
      seat_id: `${newRow}${newColumn}`,
      seat_number: `${newRow}${newColumn}`,
      row: newRow,
      column: newColumn,
      status: 'available',
      category: 'silver',
      isNew: true,
      isDirty: true
    };

    setSeats([...seats, newSeat]);
  };

  const handleDeleteSeat = (index: number) => {
    const updatedSeats = seats.filter((_, idx) => idx !== index);
    setSeats(updatedSeats);
  };

  const handleSave = () => {
    const dirtySeats = seats.filter(seat => seat.isDirty || seat.isNew);
    if (dirtySeats.length === 0) return;

    const payload = dirtySeats.map(seat => ({
      seat_id: seat.seat_id,
      status: seat.status,
      category: seat.category,
      isNew: seat.isNew
    }));

    setData('seats', payload);
    
    post(route('seats.update'), {
      preserveScroll: true,
      onSuccess: () => {
        const updatedSeats = seats.map(seat => ({
          ...seat,
          isDirty: false,
          isNew: false
        }));
        setSeats(updatedSeats);
      },
      onError: (errors) => {
        console.error('Update failed:', errors);
      }
    });
  };

  const getCellStyles = (seat: EditableSeatItem): string => {
    let baseStyle = 'px-4 py-2 text-sm border ';
    
    // Add background color based on category
    switch (seat.category) {
      case 'diamond':
        baseStyle += 'bg-cyan-100 ';
        break;
      case 'gold':
        baseStyle += 'bg-yellow-100 ';
        break;
      case 'silver':
        baseStyle += 'bg-gray-100 ';
        break;
    }

    // Add status indicators
    switch (seat.status) {
      case 'booked':
        baseStyle += 'text-red-600 font-semibold ';
        break;
      case 'in_transaction':
        baseStyle += 'text-yellow-600 ';
        break;
      case 'not_available':
        baseStyle += 'text-gray-400 ';
        break;
    }

    // Add modified indicator
    if (seat.isDirty || seat.isNew) {
      baseStyle += 'ring-2 ring-blue-400 ';
    }

    return baseStyle;
  };

  return (
    <div className="p-6">
      <div className="mb-4 flex justify-between items-center">
        <h2 className="text-2xl font-bold">Seat Spreadsheet Editor</h2>
        <div className="flex gap-2">
          <button
            onClick={handleAddRow}
            className="flex items-center gap-2 px-4 py-2 bg-green-500 text-white rounded hover:bg-green-600"
            title="Add new seat"
          >
            <PlusCircle size={20} />
            Add Seat
          </button>
          <button
            onClick={handleSave}
            className="flex items-center gap-2 px-4 py-2 bg-blue-500 text-white rounded hover:bg-blue-600"
            disabled={processing || !seats.some(seat => seat.isDirty || seat.isNew)}
            title="Save changes"
          >
            <Save size={20} />
            {processing ? 'Saving...' : 'Save Changes'}
          </button>
        </div>
      </div>

      <div className="overflow-x-auto">
        <table className="min-w-full bg-white border">
          <thead>
            <tr className="bg-gray-100">
              <th className="px-4 py-2 border">Actions</th>
              <th className="px-4 py-2 border">Seat ID</th>
              <th className="px-4 py-2 border">Seat Number</th>
              <th className="px-4 py-2 border">Row</th>
              <th className="px-4 py-2 border">Column</th>
              <th className="px-4 py-2 border">Status</th>
              <th className="px-4 py-2 border">Category</th>
            </tr>
          </thead>
          <tbody>
            {seats.map((seat, index) => (
              <tr 
                key={seat.seat_id}
                className={`hover:bg-gray-50 ${selectedCell === index ? 'bg-blue-50' : ''}`}
                onClick={() => handleCellClick(index)}
              >
                <td className="px-4 py-2 border">
                  <AlertDialog>
                    <AlertDialogTrigger asChild>
                      <button
                        className="p-1 text-red-500 hover:text-red-700"
                        disabled={seat.status === 'booked'}
                        title={`Delete seat ${seat.seat_id}`}
                      >
                        <Trash2 size={20} />
                      </button>
                    </AlertDialogTrigger>
                    <AlertDialogContent>
                      <AlertDialogHeader>
                        <AlertDialogTitle>Delete Seat</AlertDialogTitle>
                        <AlertDialogDescription>
                          Are you sure you want to delete seat {seat.seat_id}? This action cannot be undone.
                        </AlertDialogDescription>
                      </AlertDialogHeader>
                      <AlertDialogFooter>
                        <AlertDialogCancel>Cancel</AlertDialogCancel>
                        <AlertDialogAction onClick={() => handleDeleteSeat(index)}>
                          Delete
                        </AlertDialogAction>
                      </AlertDialogFooter>
                    </AlertDialogContent>
                  </AlertDialog>
                </td>
                <td className={getCellStyles(seat)}>{seat.seat_id}</td>
                <td className={getCellStyles(seat)}>{seat.seat_number}</td>
                <td className={getCellStyles(seat)}>{seat.row}</td>
                <td className={getCellStyles(seat)}>{seat.column}</td>
                <td className={getCellStyles(seat)}>
                  <select
                    value={seat.status}
                    onChange={(e) => handleStatusChange(index, e.target.value as SeatStatus)}
                    className="w-full bg-transparent"
                    disabled={seat.status === 'booked'}
                    title={`Change status for seat ${seat.seat_id}`}
                  >
                    <option value="available">Available</option>
                    <option value="booked">Booked</option>
                    <option value="in_transaction">In Transaction</option>
                    <option value="not_available">Not Available</option>
                  </select>
                </td>
                <td className={getCellStyles(seat)}>
                  <select
                    value={seat.category}
                    onChange={(e) => handleCategoryChange(index, e.target.value as Category)}
                    className="w-full bg-transparent"
                    disabled={seat.status === 'booked'}
                    title={`Change category for seat ${seat.seat_id}`}
                  >
                    <option value="diamond">Diamond</option>
                    <option value="gold">Gold</option>
                    <option value="silver">Silver</option>
                  </select>
                </td>
              </tr>
            ))}
          </tbody>
        </table>
      </div>
    </div>
  );
};

export default SeatSpreadsheetEditor;