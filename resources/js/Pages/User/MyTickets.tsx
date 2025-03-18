import EmptyState from '@/Components/novatix/EmptyState';
import Ticket from '@/Components/novatix/Ticket';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { MyTicketsPageProps, TicketProps } from '@/types/ticket';
import { Head } from '@inertiajs/react';
import React from 'react';

export default function MyTickets({
    client,
    props,
    tickets,
    event,
}: MyTicketsPageProps): React.ReactElement {
    // Handle download all tickets - updated to use query parameters
    const handleDownloadAll = () => {
        if (!tickets || tickets.length === 0 || !event?.event_id) return;

        const ticketIds = tickets.map((ticket) => ticket.id);

        // Update the URL to use query parameters instead of path parameters
        const downloadUrl = `/api/tickets/download-all?event_id=${event.event_id}&ticket_ids=${ticketIds.join(',')}`;

        window.open(downloadUrl, '_blank');
    };

    return (
        <AuthenticatedLayout client={client} props={props}>
            <Head title="My Tickets" />
            <div className="py-8">
                <div className="mx-auto max-w-7xl sm:px-6 lg:px-8">
                    <div
                        className="overflow-hidden shadow-sm sm:rounded-lg"
                        style={{
                            backgroundColor: props.primary_color,
                            color: props.text_primary_color,
                        }}
                    >
                        <div className="p-6">
                            <div className="mb-6 flex items-center justify-between">
                                <h2
                                    className="text-xl font-semibold"
                                    style={{ color: props.text_primary_color }}
                                >
                                    {event?.name || client} - My Tickets
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
                                        Unduh Semua Tiket
                                    </button>
                                )}
                            </div>

                            {tickets && tickets.length > 0 ? (
                                <div className="grid grid-cols-1 gap-6 md:grid-cols-2">
                                    {tickets.map((ticket: TicketProps) => (
                                        <Ticket
                                            key={ticket.id}
                                            ticketType={ticket.ticketType}
                                            ticketCode={ticket.ticketCode}
                                            ticketURL={ticket.ticketURL}
                                            ticketData={ticket.ticketData}
                                            eventId={event.event_id}
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
        </AuthenticatedLayout>
    );
}
