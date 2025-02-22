import { Head, router } from '@inertiajs/react';
import React from 'react';
import SeatMapEditor from './SeatMapEditor';
import { Layout } from './types';

interface Props {
    layout: Layout;
}

const Edit: React.FC<Props> = ({ layout }) => {
    const handleSave = (updatedSeats: any) => {
        router.post(
            route('seats.update'),
            { seats: updatedSeats },
            {
                onSuccess: () => {
                    console.log('Seat map updated successfully');
                },
                onError: (errors) => {
                    console.error('Failed to update seat map:', errors);
                },
            },
        );
    };

    return (
        <>
            <Head title="Edit Seat Map" />
            <div className="py-12">
                <div className="w-full px-4">
                    <div className="overflow-hidden bg-white shadow-xl sm:rounded-lg">
                        <div className="p-6">
                            <h2 className="mb-4 text-2xl font-bold">
                                Edit Seat Map
                            </h2>
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
