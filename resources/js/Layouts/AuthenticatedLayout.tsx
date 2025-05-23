// resources/js/Layouts/AuthenticatedLayout.tsx

import NavLink from '@/Components/NavLink';
import ResponsiveNavLink from '@/Components/ResponsiveNavLink';
import { deconstructEventColorProps } from '@/types/deconstruct-front-end';
import { EventColorProps, EventProps } from '@/types/front-end';
import { PageProps as InertiaPageProps } from '@inertiajs/core'; // <--- Ubah impor di sini
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

const changeFavicon = (faviconUrl: string) => {
    const link = document.querySelector("link[rel~='icon']") as HTMLLinkElement;
    if (link) {
        link.href = faviconUrl;
    } else {
        const newLink = document.createElement('link');
        newLink.rel = 'icon';
        newLink.href = faviconUrl;
        document.head.appendChild(newLink);
    }
};

// Updated Event type to include slug
interface EventContext {
    id: number;
    name: string;
    slug: string; // Added slug property
}

// Perluas InertiaPageProps untuk menyertakan properti kustom kamu
interface CustomInertiaPageProps extends InertiaPageProps {
    event: EventContext;
    props: EventProps; // This likely comes from your EventProps type
    client: string;
    userEndSessionDatetime?: string;
}

interface AuthenticatedLayoutProps {
    header?: ReactNode;
    footer?: ReactNode;
    client: string;
    props: EventProps;
    userEndSessionDatetime?: string;
    event?: EventContext; // Use the updated EventContext type
}

export default function Authenticated({
    header,
    children,
    footer,
    client,
    props,
    userEndSessionDatetime,
    event, // This prop is crucial
}: PropsWithChildren<AuthenticatedLayoutProps>) {
    // Gunakan CustomInertiaPageProps di sini
    const { auth } = usePage<CustomInertiaPageProps>().props;
    const user = auth.user;

    const [eventColorProps, setEventColorProps] = useState<EventColorProps>(
        {} as EventColorProps,
    );

    function useCountdown(userEndSessionDatetime: string | undefined) {
        const [countdown, setCountdown] = useState<number | null>(null);

        useEffect(() => {
            if (!userEndSessionDatetime) return;

            const interval = setInterval(() => {
                const endTime = new Date(userEndSessionDatetime).getTime();
                const now = Date.now();
                const timeLeft = Math.max(
                    0,
                    Math.floor((endTime - now) / 1000),
                );

                setCountdown(timeLeft);

                if (timeLeft <= 0) {
                    clearInterval(interval);
                    window.location.href = route('client.home', client);
                }
            }, 1000);

            // Removed 'client' from dependency array to fix ESLint warning
            return () => clearInterval(interval);
        }, [userEndSessionDatetime]);

        return countdown;
    }

    function formatCountdown(seconds: number | null): string {
        if (seconds === null) return '--';
        const h = Math.floor(seconds / 3600);
        const m = Math.floor((seconds % 3600) / 60);
        const s = seconds % 60;

        const parts = [];
        if (h > 0) parts.push(`${h}h`);
        if (m > 0 || h > 0) parts.push(`${m}m`);
        parts.push(`${s}s`);

        return parts.join(' ');
    }

    const countdown = useCountdown(userEndSessionDatetime);

    useEffect(() => {
        changeFavicon(props.favicon);
    }, [props.favicon]);

    useEffect(() => {
        if (props) setEventColorProps(deconstructEventColorProps(props));
    }, [props]);

    const [showingNavigationDropdown, setShowingNavigationDropdown] =
        useState<boolean>(false);

    // Condition: Only show for 'admin' role AND when an event context is available
    const showScanTicketLink = user?.role === 'admin' && event?.id; // <-- Re-added event?.id

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
                                    Buy Ticket
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
                                    My Tickets
                                </NavLink>
                                {showScanTicketLink && ( // This determines if the link appears AT ALL
                                    <NavLink
                                        eventProps={props}
                                        href={
                                            // This ensures the href is only valid if event.id exists
                                            client && event?.slug // Changed event?.id to event?.slug
                                                ? route(
                                                      'client.events.scan.show',
                                                      {
                                                          client,
                                                          event_slug:
                                                              event.slug, // Use event.slug
                                                      },
                                                  )
                                                : '#' // Falls back to '#' if event?.id is undefined
                                        }
                                        active={route().current(
                                            'client.events.scan.show',
                                        )}
                                    >
                                        Scan Ticket
                                    </NavLink>
                                )}
                            </div>
                        </div>

                        <div className="flex">
                            <div className="hidden space-x-8 sm:-my-px sm:ms-10 sm:flex">
                                <NavLink
                                    href="#"
                                    active={false} // <--- Tambahkan properti active={false}
                                    eventProps={props}
                                    className="flex gap-3"
                                >
                                    <img
                                        src={
                                            user?.contact_info?.avatar ??
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
                                    active={false} // <--- Tambahkan properti active={false}
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
                            Buy Ticket
                        </ResponsiveNavLink>
                        <ResponsiveNavLink
                            eventProps={props}
                            href={
                                client ? route('client.my_tickets', client) : ''
                            }
                            active={route().current('client.my_tickets')}
                        >
                            My Tickets
                        </ResponsiveNavLink>
                        {showScanTicketLink && (
                            <ResponsiveNavLink
                                eventProps={props}
                                href={
                                    client && event?.slug // Changed event?.id to event?.slug
                                        ? route('client.events.scan.show', {
                                              client,
                                              event_slug: event.slug, // Use event.slug
                                          })
                                        : '#'
                                }
                                active={route().current(
                                    'client.events.scan.show',
                                )}
                            >
                                Scan Ticket
                            </ResponsiveNavLink>
                        )}
                        {/* ... */}
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
                                        user?.contact_info?.avatar ??
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
                            <ResponsiveNavLink eventProps={props} href="#">
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
                                target="_blank"
                                active={false}
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

            <main className="relative flex h-full w-full grow flex-col items-center justify-center">
                <p className="pointer-events-none fixed left-0 top-0 z-[10] flex w-screen justify-center">
                    <div className="relative mt-4 rounded-lg px-4 py-2 shadow-lg">
                        {/* Blurred background layer */}
                        <div
                            className="absolute inset-0 rounded-lg backdrop-blur"
                            style={{
                                backgroundColor: props.secondary_color,
                                opacity: 0.7,
                            }}
                        />
                        {/* Text layer (above the blur) */}

                        <span
                            className="relative font-bold"
                            style={{
                                color: props.text_primary_color,
                            }}
                        >
                            {userEndSessionDatetime
                                ? `Remaining Time: ${formatCountdown(countdown)}`
                                : `Admin View`}
                        </span>
                    </div>
                </p>
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
                                        className="hidden text-sm hover:underline"
                                        style={{
                                            color: props?.text_primary_color,
                                        }}
                                    >
                                        Privacy Policy
                                    </Link>
                                    <span
                                        className="mx-2 hidden"
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
                                        className="hidden text-sm hover:underline"
                                        style={{
                                            color: props?.text_primary_color,
                                        }}
                                    >
                                        Terms & Conditions
                                    </Link>
                                    <span
                                        className="mx-2 hidden"
                                        style={{
                                            color: props?.text_primary_color,
                                        }}
                                    >
                                        •
                                    </span>
                                    <a
                                        target="_blank"
                                        rel="noreferrer"
                                        href={props.contact_person}
                                        className="text-sm hover:underline"
                                        style={{
                                            color: props?.text_primary_color,
                                        }}
                                    >
                                        Contact Support
                                    </a>
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
