import NavLink from '@/Components/NavLink';
import ResponsiveNavLink from '@/Components/ResponsiveNavLink';
import { EventProps } from '@/types/front-end';
import { Link, usePage } from '@inertiajs/react';
import { PropsWithChildren, ReactNode, useState } from 'react';
import styled from 'styled-components';

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

    const [showingNavigationDropdown, setShowingNavigationDropdown] =
        useState(false);

    const StyledButton = styled.button`
        color: ${props.text_primary_color};
        &:hover {
            color: ${props.text_secondary_color};
        }
        background-color: ${props.primary_color};
        border-color: ${props.text_primary_color};
        &:hover {
            background-color: ${props.secondary_color};
            border-color: ${props.text_secondary_color};
        }
        &:focus {
            background-color: ${props.secondary_color};
            border-color: ${props.text_secondary_color};
        }
    `;

    return (
        <div
            className="flex min-h-screen flex-col"
            style={{
                backgroundColor: props.secondary_color,
                color: props.text_secondary_color,
            }}
        >
            <nav
                className="border-b"
                style={{
                    backgroundColor: props.primary_color,
                    borderColor: props.text_primary_color,
                }}
            >
                <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <div className="flex h-16 justify-between">
                        <div className="flex">
                            <div className="flex shrink-0 items-center">
                                <Link href="/">
                                    {/* <ApplicationLogo className="block h-9 w-auto fill-current text-gray-800" /> */}
                                    <img
                                        src={props.logo}
                                        alt="ArachnoVa"
                                        className="h-8"
                                    />
                                </Link>
                            </div>

                            <div className="hidden space-x-8 sm:-my-px sm:ms-10 sm:flex">
                                <NavLink
                                    href={route('client.home', client)}
                                    active={route().current('client.home')}
                                >
                                    Beli Tiket
                                </NavLink>
                                <NavLink
                                    href={route('client.my_tickets', client)}
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
                                    href={route('profile.edit')}
                                    active={route().current('profile.edit')}
                                >
                                    Profile
                                </NavLink>
                                <NavLink
                                    method="post"
                                    href={route('logout')}
                                    as="button"
                                    active={false}
                                    headers={{
                                        'X-CSRF-TOKEN':
                                            (
                                                document.querySelector(
                                                    'meta[name="csrf-token"]',
                                                ) as HTMLMetaElement
                                            )?.content || '',
                                    }}
                                >
                                    Log Out
                                </NavLink>
                            </div>
                        </div>

                        <div className="-me-2 flex items-center sm:hidden">
                            <StyledButton
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
                            href={route('client.home', client)}
                            active={route().current('client.home')}
                        >
                            Beli Tiket
                        </ResponsiveNavLink>
                        <ResponsiveNavLink
                            href={route('client.my_tickets', client)}
                            active={route().current('client.my_tickets')}
                        >
                            Tiket Saya
                        </ResponsiveNavLink>
                    </div>

                    <div
                        className="border-t pb-1 pt-4"
                        style={{
                            borderColor: props.primary_color,
                        }}
                    >
                        <div className="px-4">
                            <div
                                className="text-base font-medium"
                                style={{
                                    color: props.text_primary_color,
                                }}
                            >
                                {user.name}
                            </div>
                            <div
                                className="text-sm font-medium"
                                style={{
                                    color: props.text_secondary_color,
                                }}
                            >
                                {user.email}
                            </div>
                        </div>

                        <div className="mt-3 space-y-1">
                            <ResponsiveNavLink href={route('profile.edit')}>
                                Profile
                            </ResponsiveNavLink>
                            <ResponsiveNavLink
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
                        backgroundColor: props.primary_color,
                    }}
                >
                    <div className="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
                        {header}
                    </div>
                </header>
            )}

            <main className="grow">{children}</main>

            {footer ? (
                <footer
                    className="border-t"
                    style={{
                        backgroundColor: props.primary_color,
                        borderColor: props.text_primary_color,
                        color: props.text_primary_color,
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
                        color: props.text_primary_color,
                        backgroundColor: props.primary_color,
                        borderColor: props.text_primary_color,
                    }}
                >
                    <div className="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
                        <div className="flex justify-between">
                            <img
                                src={props.logo}
                                alt="ArachnoVa"
                                className="h-8"
                            />
                            <p
                                className="text-sm"
                                style={{
                                    color: props.text_primary_color,
                                }}
                            >
                                &copy; 2025 ArachnoVa. All rights reserved.
                            </p>
                        </div>
                    </div>
                </footer>
            )}
        </div>
    );
}
