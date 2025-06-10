import NavLink from '@/Components/NavLink';
import ResponsiveNavLink from '@/Components/ResponsiveNavLink';
import { ContactInfo } from '@/types';
import { deconstructEventColorProps } from '@/types/deconstruct-front-end';
import { EventColorProps, EventProps } from '@/types/front-end';
import { PageProps as InertiaPageProps } from '@inertiajs/core';
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

interface EventContext {
    id: number;
    name: string;
    slug: string;
    location: string;
}

interface CustomInertiaPageProps extends InertiaPageProps {
    event: EventContext;
    props: EventProps;
    client: string;
    userEndSessionDatetime?: string;
    // Tambahkan properti `auth` untuk mengakses `user`
    auth: {
        user: {
            id: number;
            first_name: string;
            last_name: string;
            email: string;
            role: string; // Misal: 'user', 'admin', 'receptionist'
            contact_info: ContactInfo;
        };
    };
}

interface AuthenticatedLayoutProps {
    appName: string;
    header?: ReactNode;
    footer?: ReactNode;
    client: string;
    props: EventProps;
    userEndSessionDatetime?: string;
    // event: EventContext;
}

export default function Authenticated({
    appName,
    header,
    children,
    footer,
    client,
    props,
    userEndSessionDatetime,
    // event,
}: PropsWithChildren<AuthenticatedLayoutProps>) {
    const { auth, event } = usePage<CustomInertiaPageProps>().props;
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
                    window.location.href = route('client.home', {
                        client: client,
                    }); // Pastikan parameter client dikirim
                }
            }, 1000);

            return () => clearInterval(interval);
        }, [userEndSessionDatetime]); // Tambahkan client ke dependency array

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

    // --- LOGIKA BARU UNTUK NAVIGASI ---
    // Scan Ticket hanya terlihat jika user adalah 'receptionist' DAN event tersedia
    const showScanTicketLink = user?.role === 'receptionist';

    const handleScanNavigation = () => {
        if (!client) {
            console.error('Client not available');
            alert('Client information not available');
            return;
        }

        // Periksa apakah event ada dan memiliki slug
        if (!event) {
            console.error('Event not available');
            alert('Event information not available');
            return;
        }

        if (!event.slug) {
            console.error('Event slug not available');
            alert('Event slug not available');
            return;
        }

        try {
            const url = route('client.scan', {
                client: client,
            });
            window.location.href = url;
        } catch (error) {
            console.error('Route generation failed:', error);
            alert('Failed to generate scan page URL');
        }
    };

    // Buy Ticket dan My Tickets terlihat untuk SEMUA role
    // Tidak perlu variabel khusus, cukup render kondisional jika diperlukan
    // --- AKHIR LOGIKA BARU ---

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
                                        alt={props?.logo_alt || appName}
                                        className="h-8 rounded-lg"
                                    />
                                </Link>
                            </div>

                            <div className="hidden space-x-8 sm:-my-px sm:ms-10 sm:flex">
                                {/* Buy Ticket - Selalu tampil */}
                                <NavLink
                                    eventProps={props}
                                    href={
                                        client
                                            ? route('client.home', client)
                                            : ''
                                    }
                                    active={route().current('client.home')}
                                    className={
                                        showScanTicketLink ? 'hidden' : ''
                                    }
                                >
                                    Buy Ticket
                                </NavLink>
                                {/* My Tickets - Selalu tampil */}
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
                                    className={
                                        showScanTicketLink ? 'hidden' : ''
                                    }
                                >
                                    My Tickets
                                </NavLink>
                                {/* Scan Ticket - Tampil hanya jika user adalah 'receptionist' atau 'admin' dan event tersedia */}
                                {showScanTicketLink && event?.slug && (
                                    <NavLink
                                        eventProps={props}
                                        href="#"
                                        active={route().current('client.scan')}
                                        onClick={(e) => {
                                            e.preventDefault();
                                            handleScanNavigation();
                                        }}
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
                                    active={false}
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
                                    {user.first_name +
                                        ' ' +
                                        (user.last_name || '')}
                                </NavLink>
                                <NavLink
                                    className={
                                        user.role === 'admin' ? '' : 'hidden'
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
                                    id="logout"
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
                        {/* Buy Ticket - Selalu tampil di responsive */}
                        <ResponsiveNavLink
                            eventProps={props}
                            href={client ? route('client.home', client) : ''}
                            active={route().current('client.home')}
                            className={showScanTicketLink ? 'hidden' : ''}
                        >
                            Buy Ticket
                        </ResponsiveNavLink>
                        {/* My Tickets - Selalu tampil di responsive */}
                        <ResponsiveNavLink
                            eventProps={props}
                            href={
                                client ? route('client.my_tickets', client) : ''
                            }
                            active={route().current('client.my_tickets')}
                            className={showScanTicketLink ? 'hidden' : ''}
                        >
                            My Tickets
                        </ResponsiveNavLink>
                        {/* Scan Ticket - Tampil hanya jika user adalah 'receptionist' di responsive */}
                        {showScanTicketLink && (
                            <ResponsiveNavLink
                                eventProps={props}
                                href="#"
                                active={route().current('client.scan')}
                                onClick={(e) => {
                                    e.preventDefault();
                                    handleScanNavigation();
                                }}
                            >
                                Scan Ticket
                            </ResponsiveNavLink>
                        )}
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
                            <ResponsiveNavLink
                                eventProps={props}
                                href="#"
                                className="hidden"
                            >
                                Profile
                            </ResponsiveNavLink>
                            <ResponsiveNavLink
                                eventProps={props}
                                className={user.role === 'admin' ? '' : 'hidden'}
                                href="#"
                                onClick={() => {
                                    window.location.href = route('home');
                                }}
                            >
                                Admin Dashboard
                            </ResponsiveNavLink>
                            <ResponsiveNavLink
                                id="logout"
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
                <div className="pointer-events-none fixed left-0 top-0 z-[10] flex w-screen justify-center">
                    <div className="relative mt-4 rounded-lg px-4 py-2 shadow-lg">
                        <div
                            className="absolute inset-0 rounded-lg backdrop-blur"
                            style={{
                                backgroundColor: props.secondary_color,
                                opacity: 0.7,
                            }}
                        />
                        <span
                            id="session-timer"
                            className="relative font-bold"
                            style={{
                                color: props.text_primary_color,
                            }}
                        >
                            {userEndSessionDatetime
                                ? `Remaining Time: ${formatCountdown(countdown)}`
                                : user.role.charAt(0).toUpperCase() + user.role.slice(1) + ` View`
                            }
                        </span>
                    </div>
                </div>
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
                                    &copy; {new Date().getFullYear()} {appName}.
                                    All rights reserved.
                                </p>
                            </div>
                        </div>
                    </div>
                </footer>
            )}
        </div>
    );
}
