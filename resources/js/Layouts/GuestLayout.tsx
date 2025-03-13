import { EventProps } from '@/types/front-end';
import { Link } from '@inertiajs/react';
import { PropsWithChildren } from 'react';

export default function Guest({
    children,
    props,
}: PropsWithChildren & { props: EventProps }) {
    return (
        <div className="flex min-h-screen flex-col items-center bg-gray-100 pt-6 sm:justify-center sm:pt-0">
            <div>
                <Link href="/">
                    <img
                        src={props.logo}
                        alt={props.logo_alt}
                        className="asspect-[1/1] w-40"
                    />
                </Link>
            </div>

            <div className="mt-6 w-full overflow-hidden bg-white px-6 py-4 shadow-md sm:max-w-md sm:rounded-lg">
                {children}
            </div>
        </div>
    );
}
