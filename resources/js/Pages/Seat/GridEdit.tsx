import React, { useEffect, useState } from 'react';
import { Head, router, usePage } from '@inertiajs/react';
import GridSeatEditor from './GridSeatEditor';
import { Layout, LabelItem, SeatItem } from './types';
import { AlertCircle, CheckCircle2 } from "lucide-react";

interface Props {
  layout?: Layout;
  venue_id: string;
  errors?: { [key: string]: string };
  flash?: { success?: string };
}

const GridEdit: React.FC<Props> = ({ layout, venue_id, errors, flash }) => {
  const [error, setError] = useState<string | null>(null);
  const [success, setSuccess] = useState<string | null>(null);

  useEffect(() => {
    if (errors && Object.keys(errors).length > 0) {
      setError(Object.values(errors).join('\n'));
    }

    if (flash?.success) {
      setSuccess(flash.success);
    }
  }, [errors, flash]);

  const handleSave = async (updatedLayout: Layout) => {
    try {
      const convertedItems = updatedLayout.items.map(item => {
        if (item.type === 'seat') {
          const seatItem = item as SeatItem;
          const rowStr = typeof seatItem.row === 'string' ? seatItem.row : String.fromCharCode(65 + seatItem.row);
          return {
            type: 'seat',
            seat_id: seatItem.seat_id,
            seat_number: seatItem.seat_number,
            row: rowStr,
            column: seatItem.column,
            status: seatItem.status,
            category: seatItem.category,
            position: `${rowStr}${seatItem.column}`
          };
        }
        
        const labelItem = item as LabelItem;
        const rowStr = typeof labelItem.row === 'string' ? labelItem.row : String.fromCharCode(65 + labelItem.row);
        return {
          type: 'label',
          row: rowStr,
          column: labelItem.column,
          text: labelItem.text
        };
      });

      const payload = {
        venue_id,
        totalRows: updatedLayout.totalRows,
        totalColumns: updatedLayout.totalColumns,
        items: convertedItems
      };

      router.post('/seats/update-layout', payload, {
        preserveScroll: true
      });

    } catch (err) {
      console.error('Error in handleSave:', err);
      setError('Failed to process layout data');
      setSuccess(null);
    }
  };

  return (
    <>
      <Head title="Grid Seat Editor" />
      <div className="py-12">
        <div className="max-w-full mx-auto px-4 sm:px-6 lg:px-8">
          <div className="bg-white overflow-hidden shadow-xl sm:rounded-lg">
            <div className="p-6">
              <h2 className="text-2xl font-bold mb-4">Grid Seat Editor</h2>
             
              {error && (
                <div className="bg-red-50 border border-red-200 rounded-lg p-4 mb-4">
                  <div className="flex items-center">
                    <AlertCircle className="h-4 w-4 text-red-500 mr-2" />
                    <p className="text-red-500 whitespace-pre-wrap">{error}</p>
                  </div>
                </div>
              )}
             
              {success && (
                <div className="bg-green-50 border border-green-200 rounded-lg p-4 mb-4">
                  <div className="flex items-center">
                    <CheckCircle2 className="h-4 w-4 text-green-500 mr-2" />
                    <p className="text-green-500">{success}</p>
                  </div>
                </div>
              )}

              <div className="w-full overflow-x-auto">
                <div className="inline-block min-w-full">
                  <div className="flex justify-center">
                    <GridSeatEditor
                      initialLayout={layout}
                      onSave={handleSave}
                      venueId={venue_id}
                    />
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </>
  );
};

export default GridEdit;