import Toaster from '@/Components/novatix/Toaster'; // Import the Toaster component
import useToaster from '@/hooks/useToaster';
import { Head, router } from '@inertiajs/react';
import React, { useEffect, useState } from 'react';
import GridSeatEditor from './GridSeatEditor';
import { LabelItem, Layout, SeatItem } from './types';

interface Props {
    layout?: Layout;
    venue_id: string;
    errors?: { [key: string]: string };
    flash?: { success?: string };
    isDisabled?: boolean;
}

const GridEdit: React.FC<Props> = ({ layout, venue_id, errors, flash }) => {
    const { toasterState, showSuccess, showError, hideToaster } = useToaster();
    const [isSubmitting, setIsSubmitting] = useState<boolean>(false);

    useEffect(() => {
        if (errors && Object.keys(errors).length > 0) {
            showError(Object.values(errors).join('\n'));
        }

        if (flash?.success) {
            showSuccess(flash.success);
        }
    }, [errors, flash]);

    const handleSave = async (updatedLayout: Layout) => {
        setIsSubmitting(true);

        try {
            const convertedItems = updatedLayout.items.map((item) => {
                if (item.type === 'seat') {
                    const seatItem = item as SeatItem;
                    const rowStr =
                        typeof seatItem.row === 'string'
                            ? seatItem.row
                            : String.fromCharCode(65 + seatItem.row);

                    return {
                        type: 'seat',
                        seat_id: seatItem.seat_id,
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

            // Use Inertia router for the form submission
            router.post('/seats/save-grid-layout', payload, {
                preserveScroll: true,
                preserveState: true,
                onSuccess: () => {
                    showSuccess('Layout saved sucessfully');
                    setIsSubmitting(false);
                },
                onError: (errors) => {
                    showError(Object.values(errors).join('\n'));
                    setIsSubmitting(false);
                },
                onFinish: () => {
                    setIsSubmitting(false);
                },
            });
        } catch (err) {
            console.error('Error in handleSave:', err);
            showError('Failed to process layout data');
            setIsSubmitting(false);
        }
    };

    return (
        <>
            <Head title="Grid Seat Editor" />
            <div className="py-12">
                <div className="mx-auto max-w-full px-4 sm:px-6 lg:px-8">
                    <div className="overflow-hidden bg-white shadow-xl sm:rounded-lg">
                        <div className="p-6">
                            <div className="w-full overflow-x-auto">
                                <div className="inline-block min-w-full">
                                    <div className="flex justify-center">
                                        <GridSeatEditor
                                            initialLayout={layout}
                                            onSave={handleSave}
                                            venueId={venue_id}
                                            isDisabled={isSubmitting}
                                        />
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {/* Render the Toaster component */}
            <Toaster
                message={toasterState.message}
                type={toasterState.type}
                isVisible={toasterState.isVisible}
                onClose={hideToaster}
            />
        </>
    );
};

export default GridEdit;
