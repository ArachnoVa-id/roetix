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
                            <h2
                                className="mb-4 text-xl font-semibold"
                                style={{ color: props.text_primary_color }}
                            >
                                {event?.name || client} - My Tickets
                            </h2>

                            {tickets && tickets.length > 0 ? (
                                <div className="grid grid-cols-1 gap-6 md:grid-cols-2">
                                    {tickets.map((ticket: TicketProps) => (
                                        <Ticket
                                            key={ticket.id}
                                            ticketType={ticket.ticketType}
                                            ticketCode={ticket.ticketCode}
                                            ticketURL={ticket.ticketURL}
                                            ticketData={ticket.ticketData}
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
