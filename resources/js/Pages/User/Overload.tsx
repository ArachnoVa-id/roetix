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
    primary_color: string;
    secondary_color: string;
    text_primary_color: string;
    text_secondary_color: string;
    texture?: string;
    logo?: string;
    logo_alt?: string;
}

export default function Maintenance({
    maintenance,
    primary_color,
    secondary_color,
    text_primary_color,
    text_secondary_color,
    texture,
    logo,
    logo_alt,
}: MaintenanceProps): React.ReactElement {
    return (
        <div
            className="flex min-h-screen flex-col items-center justify-center px-4 py-12 sm:px-6 lg:px-8"
            style={{
                backgroundColor: primary_color,
                backgroundImage: texture ? `url(${texture})` : undefined,
                backgroundRepeat: 'repeat',
                backgroundSize: 'auto',
            }}
        >
            <Head title={'in queue:  ' + maintenance.title} />

            <div
                className="flex w-full max-w-md flex-col items-center justify-center gap-4 rounded-lg p-8 shadow-md"
                style={{
                    backgroundColor: secondary_color,
                    color: text_primary_color,
                }}
            >
                {logo && (
                    <img
                        src={logo}
                        alt={logo_alt || 'Logo'}
                        className="h-32 w-auto rounded-lg"
                    />
                )}
                <h2
                    className="text-2xl font-extrabold"
                    style={{ color: text_primary_color || '#1f2937' }}
                >
                    Event is Overloaded you are in queue
                </h2>
                <div className="flex flex-col text-center">
                    <h2
                        className="text-xl font-extrabold"
                        style={{ color: text_primary_color || '#1f2937' }}
                    >
                        {maintenance.title}
                    </h2>
                    <p
                        className="text-sm"
                        style={{
                            color: text_secondary_color || '#4b5563',
                        }}
                    >
                        {maintenance.message}
                    </p>

                    {maintenance.expected_finish && (
                        <div
                            className="mt-2 rounded-md p-4"
                            style={{
                                backgroundColor: `${primary_color || '#fef3c7'}20`,
                                borderColor: primary_color || '#fcd34d',
                            }}
                        >
                            <div className="flex flex-col items-center px-4">
                                <h3
                                    className="text-sm font-medium"
                                    style={{
                                        color: text_primary_color || '#92400e',
                                    }}
                                >
                                    Expected completion
                                </h3>
                                <div
                                    className="text-sm"
                                    style={{
                                        color:
                                            text_secondary_color || '#b45309',
                                    }}
                                >
                                    <p>{maintenance.expected_finish}</p>
                                </div>
                            </div>
                        </div>
                    )}
                </div>

                <div
                    className="text-center text-sm"
                    style={{ color: text_secondary_color || '#6b7280' }}
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
