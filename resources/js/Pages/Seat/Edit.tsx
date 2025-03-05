import { Head, router } from '@inertiajs/react';
import React, { useState } from 'react';
import SeatMapEditor, { UpdatedSeats } from './SeatMapEditor';
import { Layout } from './types';

interface Props {
    layout: Layout;
}

const Edit: React.FC<Props> = ({ layout }) => {
    const [error, setError] = useState<string | null>(null);

    const handleSave = (updatedSeats: UpdatedSeats[]) => {
        // Add visitOptions untuk memastikan credentials dikirim
        const visitOptions = {
            preserveScroll: true,
            preserveState: true,
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
            },
            onBefore: () => {
                setError(null);
            },
            onSuccess: () => {},
            onError: (errors: unknown) => {
                if (errors instanceof Error) {
                    console.error('Update failed:', errors.message);
                } else {
                    console.error('Update failed:', errors);
                }
                setError('Failed to update seats. Please try again.');
            },
            onFinish: () => {},
        };

        router.post(
            '/seats/update',
            {
                seats: updatedSeats.map((seat) => ({ ...seat })),
            },
            visitOptions,
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
                            {error && (
                                <div className="mb-4 rounded bg-red-100 p-4 text-red-700">
                                    {error}
                                </div>
                            )}
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
