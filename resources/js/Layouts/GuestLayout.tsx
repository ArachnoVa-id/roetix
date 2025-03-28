import { EventProps } from '@/types/front-end';
import { Link } from '@inertiajs/react';
import { PropsWithChildren } from 'react';

export default function Guest({
    children,
    client,
    props,
}: PropsWithChildren & { props: EventProps; client: string }) {
    return (
        <div
            className="flex min-h-screen w-full flex-col items-center gap-4 p-4 sm:justify-center"
            style={{
                backgroundColor: props?.primary_color,
            }}
        >
            <Link href="/">
                <img
                    src={props.logo}
                    alt={props.logo_alt}
                    className="aspect-[1/1] w-40"
                />
            </Link>
            <div
                className="w-fit max-w-7xl overflow-hidden px-6 shadow-md sm:rounded-lg"
                style={{
                    color: props.text_primary_color,
                }}
            >
                {children}
            </div>
            <div
                className="flex h-full w-fit items-center justify-center rounded-full px-3"
                style={{
                    backgroundColor: props.secondary_color,
                }}
            >
                <Link
                    href={route(
                        client ? 'client.privacy_policy' : 'privacy_policy',
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
                        client ? 'client.terms_conditions' : 'terms_conditions',
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
        </div>
    );
}
