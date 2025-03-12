import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { EventProps } from '@/types/front-end';
import { Head } from '@inertiajs/react';

export default function Dashboard({
    client,
    props,
}: {
    client: string;
    props: EventProps;
}) {
    return (
        <AuthenticatedLayout client={client} props={props}>
            <Head title="Dashboard" />

            <div className="py-8">
                <div className="mx-auto max-w-7xl sm:px-6 lg:px-8">
                    <div
                        className="overflow-hidden shadow-sm sm:rounded-lg"
                        style={{
                            backgroundColor: props.primary_color,
                            color: props.text_primary_color,
                        }}
                    >
                        <div className="p-6 text-gray-900">
                            {client && client + ' : '} My Tickets
                        </div>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
