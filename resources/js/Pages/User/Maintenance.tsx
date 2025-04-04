import NavLink from '@/Components/NavLink';
import { EventProps } from '@/types/front-end';
import { Head } from '@inertiajs/react';
import React from 'react';

interface MaintenanceProps {
    client: string;
    event: {
        name: string;
        slug: string;
    };
    maintenance: {
        title: string;
        message: string;
        expected_finish: string | null;
    };
    props: EventProps;
}

export default function Maintenance({
    // client,
    // event,
    maintenance,
    props,
}: MaintenanceProps): React.ReactElement {
    return (
        <div
            className="flex min-h-screen flex-col items-center justify-center px-4 py-12 sm:px-6 lg:px-8"
            style={{
                backgroundColor: props.primary_color,
                backgroundImage: `url(${props.texture})`,
                backgroundRepeat: 'repeat',
                backgroundSize: 'auto',
            }}
        >
            <Head title={'Maintenance:  ' + maintenance.title} />

            <div
                className="flex w-full max-w-md flex-col items-center justify-center gap-4 rounded-lg p-8 shadow-md"
                style={{
                    backgroundColor: props.secondary_color,
                    color: props.text_primary_color,
                }}
            >
                {props.logo && (
                    <img
                        src={props.logo}
                        alt={props.logo_alt || 'Logo'}
                        className="h-32 w-auto rounded-lg"
                    />
                )}
                <h2
                    className="text-2xl font-extrabold"
                    style={{ color: props.text_primary_color || '#1f2937' }}
                >
                    Event is Under Maintenance
                </h2>
                <div className="flex flex-col text-center">
                    <h2
                        className="text-xl font-extrabold"
                        style={{ color: props.text_primary_color || '#1f2937' }}
                    >
                        {maintenance.title}
                    </h2>
                    <p
                        className="text-sm"
                        style={{
                            color: props.text_secondary_color || '#4b5563',
                        }}
                    >
                        {maintenance.message}
                    </p>

                    {maintenance.expected_finish && (
                        <div
                            className="mt-2 rounded-md p-4"
                            style={{
                                backgroundColor: `${props.primary_color || '#fef3c7'}20`,
                                borderColor: props.primary_color || '#fcd34d',
                            }}
                        >
                            <div className="flex flex-col items-center px-4">
                                <h3
                                    className="text-sm font-medium"
                                    style={{
                                        color:
                                            props.text_primary_color ||
                                            '#92400e',
                                    }}
                                >
                                    Expected completion
                                </h3>
                                <div
                                    className="text-sm"
                                    style={{
                                        color:
                                            props.text_secondary_color ||
                                            '#b45309',
                                    }}
                                >
                                    <p>{maintenance.expected_finish}</p>
                                </div>
                            </div>
                        </div>
                    )}
                </div>

                <NavLink
                    eventProps={props}
                    method="post"
                    href={route('logout')}
                    target="_blank"
                    active={false}
                    className="flex items-center justify-center rounded-lg px-6 pb-2 pt-2 text-center"
                    style={{
                        backgroundColor: props.primary_color,
                        color: props.text_primary_color,
                    }}
                >
                    Log Out
                </NavLink>

                <div
                    className="text-center text-sm"
                    style={{ color: props.text_secondary_color || '#6b7280' }}
                >
                    <p>
                        Please check back later. We apologize for the
                        inconvenience.
                    </p>
                </div>
            </div>
        </div>
    );
}
