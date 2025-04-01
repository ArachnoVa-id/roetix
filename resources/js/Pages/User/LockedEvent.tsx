import NavLink from '@/Components/NavLink';
import { EventProps } from '@/types/front-end';
import { Head, useForm } from '@inertiajs/react';
import React, { FormEvent } from 'react';

interface LockedEventProps {
    client: string;
    event: {
        name: string;
        slug: string;
    };
    hasAttemptedLogin: boolean;
    loginError: string | null;
    props: EventProps;
}

// Define a properly typed interface for the form
interface EventPasswordForm {
    event_password: string;
    [key: string]: string | File | File[]; // Add index signature for generic string keys
}

export default function LockedEvent({
    client,
    event,
    // hasAttemptedLogin,
    loginError,
    props,
}: LockedEventProps): React.ReactElement {
    // Explicitly type the useForm hook with our interface
    const { data, setData, post, processing, errors } =
        useForm<EventPasswordForm>({
            event_password: '',
        });

    const handleSubmit = (e: FormEvent<HTMLFormElement>): void => {
        e.preventDefault();
        post(route('client.verify-event-password', { client }));
    };

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
            <Head title={'Protected:  ' + event.name} />

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

                <div className="text-center">
                    <h2
                        className="text-3xl font-extrabold"
                        style={{ color: props.text_primary_color || '#1f2937' }}
                    >
                        {event.name}
                    </h2>
                    <p
                        className="text-sm"
                        style={{
                            color: props.text_secondary_color || '#4b5563',
                        }}
                    >
                        This event is password protected
                    </p>
                </div>

                <form className="flex flex-col gap-2" onSubmit={handleSubmit}>
                    <div className="rounded-md shadow-sm">
                        <div>
                            <label htmlFor="event_password" className="sr-only">
                                Password
                            </label>
                            <input
                                id="event_password"
                                name="event_password"
                                type="password"
                                required
                                className="relative block w-full appearance-none rounded-md border border-gray-300 px-3 py-2 text-gray-900 placeholder-gray-500 focus:z-10 focus:outline-none focus:ring-2 sm:text-sm"
                                style={{
                                    borderColor:
                                        props.text_secondary_color || '#d1d5db',
                                    borderRadius: '0.375rem',
                                }}
                                placeholder="Event password"
                                value={data.event_password}
                                onChange={(e) =>
                                    setData('event_password', e.target.value)
                                }
                            />
                        </div>
                    </div>

                    {loginError && (
                        <div className="text-sm text-red-600">{loginError}</div>
                    )}

                    {errors.event_password && (
                        <div className="text-sm text-red-600">
                            {errors.event_password}
                        </div>
                    )}

                    <button
                        type="submit"
                        disabled={processing}
                        className="inline-flex w-full items-center justify-center rounded-lg border-b-2 border-b-transparent px-6 pb-2 pt-2 text-center text-sm font-medium leading-5 transition duration-150 ease-in-out hover:border-b-white focus:outline-none"
                        style={{
                            backgroundColor: props.primary_color || '#4F46E5',
                            color: '#FFFFFF',
                            borderRadius: '0.375rem',
                        }}
                    >
                        {processing ? 'Verifying...' : 'Enter Event'}
                    </button>

                    <NavLink
                        eventProps={props}
                        method="post"
                        href={route('logout')}
                        target="_blank"
                        active={false}
                        className="flex w-full items-center justify-center rounded-lg px-6 pb-2 pt-2 text-center"
                        style={{
                            backgroundColor: props.primary_color,
                            color: props.text_primary_color,
                        }}
                    >
                        Log Out
                    </NavLink>
                </form>
            </div>
        </div>
    );
}
