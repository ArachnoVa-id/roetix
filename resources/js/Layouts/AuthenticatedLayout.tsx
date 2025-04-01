import NavLink from '@/Components/NavLink';
import ResponsiveNavLink from '@/Components/ResponsiveNavLink';
import { deconstructEventColorProps } from '@/types/deconstruct-front-end';
import { EventColorProps, EventProps } from '@/types/front-end';
import { Link, usePage } from '@inertiajs/react';
import { PropsWithChildren, ReactNode, useEffect, useState } from 'react';
import styled from 'styled-components';

const StyledButton = styled.button<{ $props: EventColorProps }>`
    ${({ $props }) => `
    color: ${$props?.text_primary_color};
    &:hover {
        color: ${$props?.text_secondary_color};
    }
    background-color: ${$props?.primary_color};
    border-color: ${$props?.text_primary_color};
    &:hover {
        background-color: ${$props?.secondary_color};
        border-color: ${$props?.text_secondary_color};
    }
    &:focus {
        background-color: ${$props?.secondary_color};
        border-color: ${$props?.text_secondary_color};
    }`}
`;

export default function Authenticated({
    header,
    children,
    footer,
    client,
    props,
}: PropsWithChildren<{
    header?: ReactNode;
    footer?: ReactNode;
    client: string;
    props: EventProps;
}>) {
    const user = usePage().props.auth.user;

    const [eventColorProps, setEventColorProps] = useState<EventColorProps>(
        {} as EventColorProps,
    );

    useEffect(() => {
        if (props) setEventColorProps(deconstructEventColorProps(props));
    }, [props]);

    const [showingNavigationDropdown, setShowingNavigationDropdown] =
        useState<boolean>(false);

    return (
        <div
            className="flex min-h-screen flex-col"
            style={{
                color: props?.text_secondary_color,
                backgroundColor: props?.primary_color,
                backgroundImage: `url(${props.texture})`,
                backgroundRepeat: 'repeat',
                backgroundSize: 'auto',
            }}
        >
            <nav
                className="border-b"
                style={{
                    backgroundColor: props?.primary_color,
                    borderColor: props?.text_primary_color,
                }}
            >
                <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <div className="flex h-16 justify-between">
                        <div className="flex">
                            <div className="flex shrink-0 items-center">
                                <Link href="/">
                                    <img
                                        src={props?.logo}
                                        alt={props?.logo_alt}
                                        className="h-8 rounded-lg"
                                    />
                                </Link>
                            </div>

                            <div className="hidden space-x-8 sm:-my-px sm:ms-10 sm:flex">
                                <NavLink
                                    eventProps={props}
                                    href={
                                        client
                                            ? route('client.home', client)
                                            : ''
                                    }
                                    active={route().current('client.home')}
                                >
                                    Beli Tiket
                                </NavLink>
                                <NavLink
                                    eventProps={props}
                                    href={
                                        client
                                            ? route('client.my_tickets', client)
                                            : ''
                                    }
                                    active={route().current(
                                        'client.my_tickets',
                                    )}
                                >
                                    Tiket Saya
                                </NavLink>
                            </div>
                        </div>

                        <div className="flex">
                            <div className="hidden space-x-8 sm:-my-px sm:ms-10 sm:flex">
                                <NavLink
                                    eventProps={props}
                                    href={
                                        client
                                            ? route('profile.edit', client)
                                            : ''
                                    }
                                    active={route().current('profile.edit')}
                                    className="flex gap-3"
                                >
                                    <img
                                        src={
                                            user.contact_info.avatar ??
                                            'images/default-avatar/default-avatar.png'
                                        }
                                        alt={'Avatar'}
                                        className="h-8 rounded-lg"
                                        loading="eager"
                                    />
                                    {user.first_name + ' ' + user.last_name}
                                </NavLink>
                                <NavLink
                                    className={
                                        user.role === 'user' ? 'hidden' : ''
                                    }
                                    eventProps={props}
                                    href="#"
                                    active={false}
                                    onClick={() => {
                                        window.location.href = route('home');
                                    }}
                                >
                                    Admin Dashboard
                                </NavLink>
                                <NavLink
                                    eventProps={props}
                                    method="post"
                                    href={route('logout')}
                                    target="_blank"
                                    active={false}
                                >
                                    Log Out
                                </NavLink>
                            </div>
                        </div>

                        <div className="-me-2 flex items-center sm:hidden">
                            <StyledButton
                                $props={eventColorProps}
                                onClick={() =>
                                    setShowingNavigationDropdown(
                                        (previousState) => !previousState,
                                    )
                                }
                                className="inline-flex items-center justify-center rounded-md p-2 transition duration-150 ease-in-out focus:outline-none"
                                aria-label="Toggle navigation menu"
                            >
                                <svg
                                    className="h-6 w-6"
                                    stroke="currentColor"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        className={
                                            !showingNavigationDropdown
                                                ? 'inline-flex'
                                                : 'hidden'
                                        }
                                        strokeLinecap="round"
                                        strokeLinejoin="round"
                                        strokeWidth="2"
                                        d="M4 6h16M4 12h16M4 18h16"
                                    />
                                    <path
                                        className={
                                            showingNavigationDropdown
                                                ? 'inline-flex'
                                                : 'hidden'
                                        }
                                        strokeLinecap="round"
                                        strokeLinejoin="round"
                                        strokeWidth="2"
                                        d="M6 18L18 6M6 6l12 12"
                                    />
                                </svg>
                            </StyledButton>
                        </div>
                    </div>
                </div>

                <div
                    className={
                        (showingNavigationDropdown ? 'block' : 'hidden') +
                        ' sm:hidden'
                    }
                >
                    <div className="space-y-1 pb-3 pt-2">
                        <ResponsiveNavLink
                            eventProps={props}
                            href={client ? route('client.home', client) : ''}
                            active={route().current('client.home')}
                        >
                            Beli Tiket
                        </ResponsiveNavLink>
                        <ResponsiveNavLink
                            eventProps={props}
                            href={
                                client ? route('client.my_tickets', client) : ''
                            }
                            active={route().current('client.my_tickets')}
                        >
                            Tiket Saya
                        </ResponsiveNavLink>
                    </div>

                    <div
                        className="border-t pb-1 pt-4"
                        style={{
                            borderColor: props?.primary_color,
                        }}
                    >
                        <div className="px-4">
                            <div
                                className="flex items-center gap-4 text-base font-medium"
                                style={{
                                    color: props?.text_primary_color,
                                }}
                            >
                                <img
                                    src={
                                        user.contact_info.avatar ??
                                        'images/default-avatar/default-avatar.png'
                                    }
                                    alt={'Avatar'}
                                    className="h-8 rounded-lg"
                                    loading="eager"
                                />
                                {user.first_name + ' ' + user.last_name}
                            </div>
                        </div>

                        <div className="mt-3 space-y-1">
                            <ResponsiveNavLink
                                eventProps={props}
                                href={
                                    client ? route('profile.edit', client) : ''
                                }
                            >
                                Profile
                            </ResponsiveNavLink>
                            <ResponsiveNavLink
                                eventProps={props}
                                className={user.role === 'user' ? 'hidden' : ''}
                                href="#"
                                onClick={() => {
                                    window.location.href = route('home');
                                }}
                            >
                                Admin Dashboard
                            </ResponsiveNavLink>
                            <ResponsiveNavLink
                                eventProps={props}
                                method="post"
                                href={route('logout')}
                                as="button"
                            >
                                Log Out
                            </ResponsiveNavLink>
                        </div>
                    </div>
                </div>
            </nav>

            {header && (
                <header
                    className="shadow"
                    style={{
                        backgroundColor: props?.primary_color,
                    }}
                >
                    <div className="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
                        {header}
                    </div>
                </header>
            )}

            <main className="flex h-full w-full grow flex-col items-center justify-center">
                {children}
            </main>

            {footer ? (
                <footer
                    className="border-t"
                    style={{
                        backgroundColor: props?.primary_color,
                        borderColor: props?.text_primary_color,
                        color: props?.text_primary_color,
                    }}
                >
                    <div className="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
                        {footer}
                    </div>
                </footer>
            ) : (
                <footer
                    className="border-t"
                    style={{
                        color: props?.text_primary_color,
                        backgroundColor: props?.primary_color,
                        borderColor: props?.text_primary_color,
                    }}
                >
                    <div className="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
                        <div className="flex flex-col">
                            <div className="relative flex flex-col items-center justify-between md:flex-row">
                                <Link href="/">
                                    <img
                                        src={props?.logo}
                                        alt={props?.logo_alt}
                                        className="h-8 rounded-lg"
                                    />
                                </Link>
                                <div className="z-0 flex h-full w-full items-center justify-center md:absolute md:left-0 md:top-0">
                                    <Link
                                        href={route(
                                            'client.privacy_policy',
                                            client,
                                        )}
                                        className="text-sm hover:underline"
                                        style={{
                                            color: props?.text_primary_color,
                                        }}
                                    >
                                        Privacy Policy
                                    </Link>
                                    <span
                                        className="mx-2"
                                        style={{
                                            color: props?.text_primary_color,
                                        }}
                                    >
                                        •
                                    </span>
                                    <Link
                                        href={route(
                                            'client.terms_conditions',
                                            client,
                                        )}
                                        className="text-sm hover:underline"
                                        style={{
                                            color: props?.text_primary_color,
                                        }}
                                    >
                                        Terms & Conditions
                                    </Link>
                                </div>
                                <p
                                    className="z-10 text-sm"
                                    style={{
                                        color: props?.text_primary_color,
                                    }}
                                >
                                    &copy; 2025 ArachnoVa. All rights reserved.
                                </p>
                            </div>
                        </div>
                    </div>
                </footer>
            )}
        </div>
    );
}
