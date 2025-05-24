'use client';

import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { PageProps } from '@inertiajs/core';
import { Link, usePage } from '@inertiajs/react';

// Define the type for the events passed to this page
interface SelectEventPageProps extends PageProps {
    events: Array<{
        id: number;
        name: string;
        slug: string | number; // slug or ID
    }>;
}

const SelectEventPage = () => {
    const { events, props, client, userEndSessionDatetime } =
        usePage<SelectEventPageProps>().props;

    return (
        <AuthenticatedLayout
            client={client}
            props={props}
            userEndSessionDatetime={userEndSessionDatetime}
            header={
                <h2 className="text-xl font-semibold leading-tight text-gray-800">
                    Select Event to Scan
                </h2>
            }
        >
            <div className="container mx-auto max-w-2xl p-4">
                <div className="rounded-lg bg-white p-6 shadow-xl">
                    <h3 className="mb-4 text-2xl font-bold text-gray-800">
                        Available Events
                    </h3>
                    {events.length === 0 ? (
                        <p className="text-gray-600">
                            No events found for scanning.
                        </p>
                    ) : (
                        <ul className="space-y-4">
                            {events.map((event) => (
                                <li
                                    key={event.id}
                                    className="border-b border-gray-200 pb-4 last:border-b-0 last:pb-0"
                                >
                                    <Link
                                        href={route('client.events.scan.show', {
                                            client,
                                            event: event.slug, // Use slug for cleaner URLs
                                        })}
                                        className="block text-lg font-medium text-blue-600 hover:text-blue-800 hover:underline"
                                    >
                                        {event.name}
                                        <span className="ml-2 text-sm text-gray-500">
                                            ({event.slug})
                                        </span>
                                    </Link>
                                </li>
                            ))}
                        </ul>
                    )}
                </div>
            </div>
        </AuthenticatedLayout>
    );
};

export default SelectEventPage;
