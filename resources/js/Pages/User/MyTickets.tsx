import EmptyState from '@/Components/novatix/EmptyState';
import Ticket from '@/Components/novatix/Ticket';
import Toaster from '@/Components/novatix/Toaster';
import useToaster from '@/hooks/useToaster';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import {
    MyTicketsPageProps,
    TicketActionEvent,
    TicketProps,
} from '@/types/ticket';
import { Head } from '@inertiajs/react';
import React, { useCallback, useEffect } from 'react';

export default function MyTickets({
    client,
    props,
    tickets,
    event,
}: MyTicketsPageProps): React.ReactElement {
    const { toasterState, showSuccess, showError, hideToaster } = useToaster();

    // Handle download all tickets - updated to use query parameters
    const handleDownloadAll = () => {
        if (!tickets || tickets.length === 0 || !event?.id) {
            showError('No tickets available to download');
            return;
        }

        const ticketIds = tickets.map((ticket) => ticket.id);

        // Update the URL to use query parameters instead of path parameters
        const downloadUrl = `/api/tickets/download?event_id=${event.id}&ticket_ids=${ticketIds.join(',')}`;

        try {
            window.open(downloadUrl, '_blank');
            showSuccess('Downloading all tickets');
        } catch (error) {
            showError('Failed to download tickets');
        }
    };

    // Handle individual ticket download with useCallback to prevent dependency warning
    const handleSingleTicketDownload = useCallback(
        (ticketId: string) => {
            showSuccess(`Downloading ticket ${ticketId}`);
        },
        [showSuccess],
    );

    // Pass this handler to each Ticket component
    useEffect(() => {
        // Listen for custom event from Ticket component
        const handleTicketAction = (e: Event) => {
            const customEvent = e as TicketActionEvent;
            if (customEvent.detail?.action === 'download') {
                handleSingleTicketDownload(customEvent.detail.ticketId);
            } else if (customEvent.detail?.action === 'error') {
                showError(
                    customEvent.detail.error ||
                        'An error occurred with the ticket',
                );
            }
        };

        window.addEventListener('ticket-action', handleTicketAction);

        return () => {
            window.removeEventListener('ticket-action', handleTicketAction);
        };
    }, [handleSingleTicketDownload, showError]);

    // Create stylesheet classes instead of inline styles
    const containerStyle = {
        backgroundColor: props.primary_color,
        color: props.text_primary_color,
    };

    const titleStyle = {
        color: props.text_primary_color,
    };

    return (
        <AuthenticatedLayout client={client} props={props}>
            <Head title={'My Tickets | ' + event.name} />
            <div className="w-full py-8">
                <div className="mx-auto w-full max-w-7xl sm:px-6 lg:px-8">
                    <div
                        className="w-full overflow-hidden shadow-sm sm:rounded-lg"
                        style={containerStyle}
                    >
                        <div className="w-full p-6">
                            <div className="mb-6 flex items-center justify-between">
                                <h2
                                    className="text-xl font-semibold"
                                    style={titleStyle}
                                >
                                    {event?.name || client}{' '}
                                    <br className="md:hidden" />
                                    <span className="max-md:hidden">-</span> My
                                    Tickets
                                </h2>

                                {tickets && tickets.length > 0 && (
                                    <button
                                        onClick={handleDownloadAll}
                                        className="flex items-center rounded-md bg-green-600 px-4 py-2 text-white transition-colors duration-200 hover:bg-green-700"
                                    >
                                        <svg
                                            xmlns="http://www.w3.org/2000/svg"
                                            className="mr-2 h-5 w-5"
                                            viewBox="0 0 20 20"
                                            fill="currentColor"
                                        >
                                            <path
                                                fillRule="evenodd"
                                                d="M3 17a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm3.293-7.707a1 1 0 011.414 0L9 10.586V3a1 1 0 112 0v7.586l1.293-1.293a1 1 0 111.414 1.414l-3 3a1 1 0 01-1.414 0l-3-3a1 1 0 010-1.414z"
                                                clipRule="evenodd"
                                            />
                                        </svg>
                                        Download All Tickets
                                    </button>
                                )}
                            </div>

                            {tickets && tickets.length > 0 ? (
                                <div className="flex w-full flex-wrap gap-6">
                                    {tickets
                                        .sort((a: TicketProps) =>
                                            a.status === 'scanned' ? 1 : -1,
                                        )
                                        .map((ticket: TicketProps) => (
                                            <Ticket
                                                key={ticket.id}
                                                id={ticket.id}
                                                type={ticket.type}
                                                code={ticket.code}
                                                qrStr={ticket.qrStr}
                                                data={ticket.data}
                                                eventId={event.id}
                                                status={ticket.status}
                                                categoryColor={
                                                    ticket.categoryColor
                                                }
                                            />
                                        ))}
                                </div>
                            ) : (
                                <EmptyState
                                    title="No tickets found"
                                    description="You haven't purchased any tickets for this event yet."
                                    actionLink={route('client.home', client)}
                                    actionText="Buy Tickets"
                                />
                            )}
                        </div>
                    </div>
                </div>
            </div>

            {/* Add the Toaster component */}
            <Toaster
                message={toasterState.message}
                type={toasterState.type}
                isVisible={toasterState.isVisible}
                onClose={hideToaster}
            />
        </AuthenticatedLayout>
    );
}
