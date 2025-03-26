import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
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
}

export default function PrivacyPolicy({ client, props, event }: Props) {
    const eventName = event?.name || 'YOS'; // Fallback to 'YOS' if event name isn't available

    return (
        <AuthenticatedLayout client={client} props={props}>
            <Head title="Privacy Policy" />
            <div className="py-8">
                <div className="mx-auto max-w-7xl sm:px-6 lg:px-8">
                    <div
                        className="overflow-hidden shadow-sm sm:rounded-lg"
                        style={{
                            backgroundColor: props.primary_color,
                        }}
                    >
                        <div
                            className="flex flex-col items-center justify-center p-6"
                            style={{
                                color: props.text_primary_color,
                            }}
                        >
                            {/* Title and last updated */}
                            <h1
                                className="mb-2 text-center font-[AstroJack] text-6xl font-normal tracking-wider md:text-8xl"
                                style={{
                                    color: props.text_secondary_color,
                                }}
                            >
                                {privacyPolicyContent.title}
                            </h1>
                            <p className="mb-6 text-center font-[GillSansMT] text-lg font-bold italic lg:text-2xl">
                                Terakhir kali diubah:{' '}
                                {privacyPolicyContent.lastUpdated}
                            </p>

                            {/* Main content */}
                            <div
                                className="mt-4 w-full rounded-lg p-6 font-[Inter] shadow-lg lg:w-[80%] lg:p-10"
                                style={{
                                    backgroundColor: props.secondary_color,
                                    color: props.text_secondary_color,
                                }}
                            >
                                <div className="space-y-4 text-justify">
                                    <p className="text-base font-semibold lg:text-lg">
                                        {privacyPolicyContent.introduction.replace(
                                            'Yogyakarta Oratorio Society',
                                            eventName,
                                        )}
                                    </p>
                                    <ol className="list-decimal space-y-4 pl-5">
                                        {privacyPolicyContent.sections.map(
                                            (section, index) => {
                                                // Replace any instances of "Yogyakarta Oratorio Society" with the event name
                                                let content = section.content;
                                                if (content) {
                                                    content = content.replace(
                                                        'Yogyakarta Oratorio Society',
                                                        eventName,
                                                    );
                                                }

                                                // Also update any points that might mention the organization
                                                let points = section.points;
                                                if (points) {
                                                    points = points.map(
                                                        (point) =>
                                                            point.replace(
                                                                'Yogyakarta Oratorio Society',
                                                                eventName,
                                                            ),
                                                    );
                                                }

                                                return (
                                                    <li key={index}>
                                                        <strong className="font-[Inter] text-lg font-semibold lg:text-xl">
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
                                                                            {
                                                                                point
                                                                            }
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
            </div>
        </AuthenticatedLayout>
    );
}
