import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import GuestLayout from '@/Layouts/GuestLayout';
import { EventProps } from '@/types/front-end';
import { Head } from '@inertiajs/react';
import { privacyPolicyContent } from './privacypolicycontent';

interface Props {
    client: string;
    props: EventProps;
    event?: {
        name: string;
        [key: string]: string | number | boolean | object;
    };
    user?: {
        [key: string]: string | number | boolean | object;
    };
}

export default function PrivacyPolicy({ client, props, event, user }: Props) {
    const eventName = event?.name || 'NovaTix'; // Fallback to 'NovaTix' if event name isn't available

    const content = (
        <>
            <Head title="Privacy Policy" />
            <div className="mx-auto max-w-7xl sm:px-6 md:px-8">
                <div
                    className="overflow-hidden shadow-sm sm:rounded-lg"
                    style={{
                        backgroundColor: props.secondary_color,
                    }}
                >
                    <div
                        className="flex flex-col items-center justify-center p-6"
                        style={{
                            color: props.text_primary_color,
                        }}
                    >
                        <div
                            className="w-full rounded-lg p-4 shadow-lg md:w-[80%]"
                            style={{
                                backgroundColor: props.primary_color,
                                color: props.text_secondary_color,
                            }}
                        >
                            {/* Title and last updated */}
                            <h1
                                className="text-center text-4xl font-normal tracking-wider md:text-4xl"
                                style={{
                                    color: props.text_secondary_color,
                                }}
                            >
                                {privacyPolicyContent.title}
                            </h1>
                            <p className="text-center text-lg font-bold italic md:text-xl">
                                Terakhir kali diubah:{' '}
                                {privacyPolicyContent.lastUpdated}
                            </p>
                        </div>

                        {/* Main content */}
                        <div
                            className="mt-4 w-full rounded-lg p-6 shadow-lg md:w-[80%] md:p-10"
                            style={{
                                backgroundColor: props.primary_color,
                                color: props.text_secondary_color,
                            }}
                        >
                            <div className="space-y-4 text-justify">
                                <p className="text-base font-semibold md:text-lg">
                                    {privacyPolicyContent.introduction.replace(
                                        'NovaTix',
                                        eventName,
                                    )}
                                </p>
                                <ol className="list-decimal space-y-4 pl-5">
                                    {privacyPolicyContent.sections.map(
                                        (section, index) => {
                                            // Replace any instances of "NovaTix" with the event name
                                            let content = section.content;
                                            if (content) {
                                                content = content.replace(
                                                    'NovaTix',
                                                    eventName,
                                                );
                                            }

                                            // Also update any points that might mention the organization
                                            let points = section.points;
                                            if (points) {
                                                points = points.map((point) =>
                                                    point.replace(
                                                        'NovaTix',
                                                        eventName,
                                                    ),
                                                );
                                            }

                                            return (
                                                <li key={index}>
                                                    <strong className="text-lg font-semibold md:text-xl">
                                                        {section.title}
                                                    </strong>
                                                    <br />
                                                    {content}
                                                    {points && (
                                                        <ul className="mt-1 list-disc pl-5">
                                                            {points.map(
                                                                (
                                                                    point,
                                                                    pointIndex,
                                                                ) => (
                                                                    <li
                                                                        key={
                                                                            pointIndex
                                                                        }
                                                                    >
                                                                        {point}
                                                                    </li>
                                                                ),
                                                            )}
                                                        </ul>
                                                    )}
                                                </li>
                                            );
                                        },
                                    )}
                                </ol>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </>
    );

    if (user)
        return (
            <AuthenticatedLayout client={client} props={props}>
                {content}
            </AuthenticatedLayout>
        );
    else
        return (
            <GuestLayout client={client} props={props}>
                {content}
            </GuestLayout>
        );
}
