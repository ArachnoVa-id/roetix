// GridEdit.tsx
import Toaster from '@/Components/novatix/Toaster';
import useToaster from '@/hooks/useToaster';
import { GridEditorProps } from '@/types/editor';
import { Layout, SeatItem } from '@/types/seatmap';
import { Head, router } from '@inertiajs/react';
import React, { useEffect, useState } from 'react';
import GridSeatEditor from './GridSeatEditor';

const GridEdit: React.FC<GridEditorProps> = ({
    layout,
    venue_id,
    errors,
    flash,
}) => {
    const { toasterState, showSuccess, showError, hideToaster } = useToaster();
    const [isSubmitting, setIsSubmitting] = useState<boolean>(false);

    useEffect(() => {
        if (errors && Object.keys(errors).length > 0) {
            showError(Object.values(errors).join('\n'));
        }

        if (flash?.success) {
            showSuccess(flash.success);
        }
    }, [errors, flash, showError, showSuccess]);

    const handleSave = async (updatedLayout: Layout) => {
        setIsSubmitting(true);

        try {
            const convertedItems = updatedLayout.items.map((item) => {
                // if (item.type === 'seat') {

                const seatItem = item as SeatItem;
                const rowStr =
                    typeof seatItem.row === 'string'
                        ? seatItem.row
                        : String.fromCharCode(65 + seatItem.row);

                return {
                    type: 'seat',
                    id: seatItem.id,
                    seat_number: seatItem.seat_number,
                    row: rowStr,
                    column: seatItem.column,
                    position: `${rowStr}${seatItem.column}`,
                };

                // const labelItem = item as LabelItem;
                // const rowStr =
                //     typeof labelItem.row === 'string'
                //         ? labelItem.row
                //         : String.fromCharCode(65 + labelItem.row);
                // return {
                //     type: 'label',
                //     row: rowStr,
                //     column: labelItem.column,
                //     text: labelItem.text,
                // };
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
            {/* Take up full viewport without scrolling */}
            <div className="fixed inset-0 overflow-hidden">
                <div className="h-full w-full">
                    <GridSeatEditor
                        initialLayout={layout}
                        onSave={handleSave}
                        venueId={venue_id}
                        isDisabled={isSubmitting}
                    />
                </div>
            </div>

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
