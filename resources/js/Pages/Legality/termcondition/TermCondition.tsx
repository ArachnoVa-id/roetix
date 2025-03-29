import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import GuestLayout from '@/Layouts/GuestLayout';
import { EventProps } from '@/types/front-end';
import { Head } from '@inertiajs/react';
import { termConditionContent } from './termconditioncontent';
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

export default function TermCondition({ client, props, event, user }: Props) {
    const eventName = event?.name || 'NovaTix'; // Fallback to 'NovaTix' if event name isn't available

    const renderBoldText = (text: string) => {
        return text.split(/(\*\*[^*]+\*\*)/).map((part, index) => {
            if (part.startsWith('**') && part.endsWith('**')) {
                return (
                    <strong key={index} className="font-bold">
                        {part.slice(2, -2)}
                    </strong>
                );
            }
            return part;
        });
    };

    // Replace any mentions of "NovaTix" in the introduction with the actual event name
    const updatedIntroduction = termConditionContent.introduction
        .replace('Event **NovaTix**', `Event **${eventName}**`)
        .replace('Panitia **NovaTix**', `Panitia **${eventName}**`);

    // Update footer similarly
    const updatedFooter = termConditionContent.footer.replace(
        'Panitia **NovaTix**',
        `Panitia **${eventName}**`,
    );

    const content = (
        <>
            <Head title="Terms and Conditions" />
            <div className="mx-auto max-w-7xl sm:px-6 lg:px-8">
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
                                {termConditionContent.title}
                            </h1>
                            <p className="text-center text-lg font-bold italic md:text-xl">
                                Terakhir kali diubah:{' '}
                                {termConditionContent.lastUpdated}
                            </p>
                        </div>

                        {/* Main content */}
                        <div
                            className="mt-4 w-full rounded-lg p-6 text-justify shadow-lg lg:w-[80%] lg:p-10"
                            style={{
                                backgroundColor: props.primary_color,
                                color: props.text_secondary_color,
                            }}
                        >
                            <div className="space-y-6">
                                <p className="mb-8 whitespace-pre-line text-base lg:text-lg">
                                    {renderBoldText(updatedIntroduction)}
                                </p>
                                <p className="font-bold">
                                    {termConditionContent.title}
                                </p>
                                <ol className="list-decimal space-y-4 pl-5">
                                    {termConditionContent.sections.map(
                                        (section, index) => (
                                            <li
                                                key={index}
                                                className="text-base lg:text-lg"
                                            >
                                                {renderBoldText(
                                                    section.content,
                                                )}
                                            </li>
                                        ),
                                    )}
                                </ol>
                                <p className="mt-8 whitespace-pre-line text-justify text-base lg:text-lg">
                                    {renderBoldText(updatedFooter)}
                                </p>
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
