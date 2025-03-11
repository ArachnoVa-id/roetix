import { Head, router } from '@inertiajs/react';
import { AlertCircle, CheckCircle2 } from 'lucide-react';
import React, { useEffect, useState } from 'react';
import GridSeatEditor from './GridSeatEditor';
import { LabelItem, Layout, SeatItem } from './types';

interface Props {
    layout?: Layout;
    venue_id: string;
    errors?: { [key: string]: string };
    flash?: { success?: string };
}

interface User {
    user_id: string;
    team_ids: string[];
}

const GridEdit: React.FC<Props> = ({ layout, venue_id, errors, flash }) => {
    const queryParams = new URLSearchParams(window.location.search); 
    const venueId = queryParams.get('venue_id');

    const [user, setUser] = useState<User | null>(null);
    const [isAuthorized, setIsAuthorized] = useState<boolean>(false);
    const [loading, setLoading] = useState<boolean>(true);

    useEffect(() => {
        if (!venueId) return;

        fetch('/api/user')
            .then((res) => res.json())
            .then((data) => {
                setUser(data);
                setIsAuthorized(data.team_ids.includes(venueId));
            })
            .catch(() => setIsAuthorized(false))
            .finally(() => setLoading(false));
    }, [venueId]);
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
            const convertedItems = updatedLayout.items.map((item) => {
                if (item.type === 'seat') {
                    const seatItem = item as SeatItem;
                    const rowStr =
                        typeof seatItem.row === 'string'
                            ? seatItem.row
                            : String.fromCharCode(65 + seatItem.row);

                    // Perhatikan bahwa kita tidak perlu menetapkan seat_id di sini
                    // seat_id akan dibuat di server berdasarkan venue_id dan seat_number
                    return {
                        type: 'seat',
                        seat_id: seatItem.seat_id, // Kirim seat_id yang ada (jika ada)
                        seat_number: seatItem.seat_number,
                        row: rowStr,
                        column: seatItem.column,
                        position: `${rowStr}${seatItem.column}`,
                    };
                }

                const labelItem = item as LabelItem;
                const rowStr =
                    typeof labelItem.row === 'string'
                        ? labelItem.row
                        : String.fromCharCode(65 + labelItem.row);
                return {
                    type: 'label',
                    row: rowStr,
                    column: labelItem.column,
                    text: labelItem.text,
                };
            });

            const payload = {
                venue_id,
                totalRows: updatedLayout.totalRows,
                totalColumns: updatedLayout.totalColumns,
                items: convertedItems,
            };

            // Gunakan endpoint saveGridLayout yang baru
            // Ini akan membuat seat_id berdasarkan venue_id dan seat_number di sisi server
            router.post('/seats/save-grid-layout', payload, {
                preserveScroll: true,
                onSuccess: () => {
                    setSuccess('Layout berhasil disimpan');
                    setError(null);
                },
                onError: (errors) => {
                    setError(Object.values(errors).join('\n'));
                    setSuccess(null);
                },
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
                <div className="mx-auto max-w-full px-4 sm:px-6 lg:px-8">
                    <div className="overflow-hidden bg-white shadow-xl sm:rounded-lg">
                        <div className="p-6">
                            <h2 className="mb-4 text-2xl font-bold">
                                Grid Seat Editor
                            </h2>

                            {error && (
                                <div className="mb-4 rounded-lg border border-red-200 bg-red-50 p-4">
                                    <div className="flex items-center">
                                        <AlertCircle className="mr-2 h-4 w-4 text-red-500" />
                                        <p className="whitespace-pre-wrap text-red-500">
                                            {error}
                                        </p>
                                    </div>
                                </div>
                            )}

                            {success && (
                                <div className="mb-4 rounded-lg border border-green-200 bg-green-50 p-4">
                                    <div className="flex items-center">
                                        <CheckCircle2 className="mr-2 h-4 w-4 text-green-500" />
                                        <p className="text-green-500">
                                            {success}
                                        </p>
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
