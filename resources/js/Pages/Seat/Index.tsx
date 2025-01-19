import React from 'react';
import { Head, Link } from '@inertiajs/react';
import SeatMapDisplay from './SeatMapDisplay';
import { SeatMapSection } from './types';

interface Props {
  seatData: {
    sections: SeatMapSection[];
  };
}

const Index: React.FC<Props> = ({ seatData }) => {
  return (
    <>
      <Head title="Seat Map" />
      
      <div className="py-12">
        <div className="max-w-7xl mx-auto sm:px-6 lg:px-8">
          <div className="bg-white overflow-hidden shadow-xl sm:rounded-lg">
            <div className="p-6">
              <div className="flex justify-between items-center mb-6">
                <h2 className="text-2xl font-bold">Seat Map</h2>
                <Link
                  href={route('seats.edit')}
                  className="px-4 py-2 bg-blue-500 text-white rounded hover:bg-blue-600"
                >
                  Edit Seat Map
                </Link>
              </div>
              
              <SeatMapDisplay
                sections={seatData.sections}
              />
            </div>
          </div>
        </div>
      </div>
    </>
  );
};

export default Index;