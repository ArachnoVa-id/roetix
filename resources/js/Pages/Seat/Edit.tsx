import React from 'react';
import { Head, router } from '@inertiajs/react';
import SeatMapEditor from './SeatMapEditor';
import { Layout } from './types';

interface Props {
  layout: Layout;
}

const Edit: React.FC<Props> = ({ layout }) => {
  const handleSave = (updatedSeats: any) => {
    router.post(route('seats.update'), { seats: updatedSeats }, {
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
        <div className="w-full px-4">
          <div className="bg-white overflow-hidden shadow-xl sm:rounded-lg">
            <div className="p-6">
              <h2 className="text-2xl font-bold mb-4">Edit Seat Map</h2>
              <div className="overflow-x-auto">
                <div className="min-w-max">
                  <SeatMapEditor 
                    layout={layout}
                    onSave={handleSave}
                  />
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </>
  );
};

export default Edit;