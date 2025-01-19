import React from 'react';
import { Head, router } from '@inertiajs/react';
import SeatMapEditor from './SeatMapEditor';
import { SeatMapSection } from './types';

interface Props {
  seatData: {
    sections: SeatMapSection[];
  };
}

const Edit: React.FC<Props> = ({ seatData }) => {
  const handleSave = (updatedSections: SeatMapSection[]) => {
    // Convert sections to plain object to satisfy FormDataConvertible
    const formData = {
      sections: updatedSections.map(section => ({
        id: section.id,
        name: section.name,
        rows: section.rows,
        seats: section.seats.map(seat => ({
          seat_id: seat.seat_id,
          seat_number: seat.seat_number,
          position: seat.position,
          status: seat.status,
          category: seat.category,
          price: seat.price,
          row: seat.row,
          column: seat.column
        }))
      }))
    };

    router.post(route('seats.update'), formData, {
      onSuccess: () => {
        console.log('Seat map updated successfully');
      },
      onError: (errors) => {
        console.error('Failed to update seat map:', errors);
      }
    });
  };

  return (
    <>
      <Head title="Edit Seat Map" />
      
      <div className="py-12">
        <div className="max-w-7xl mx-auto sm:px-6 lg:px-8">
          <div className="bg-white overflow-hidden shadow-xl sm:rounded-lg">
            <div className="p-6">
              <h2 className="text-2xl font-bold mb-4">Edit Seat Map</h2>
              <SeatMapEditor
                sections={seatData.sections}
                onSave={handleSave}
              />
            </div>
          </div>
        </div>
      </div>
    </>
  );
};

export default Edit;