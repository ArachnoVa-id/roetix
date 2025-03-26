import { termConditionContent } from './termconditioncontent';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head } from '@inertiajs/react';
import { EventProps } from '@/types/front-end';

interface Props {
    client: string;
    props: EventProps;
    event?: {
        name: string;
        [key: string]: any;
    };
}

export default function TermCondition({ client, props, event }: Props) {
    const eventName = event?.name || 'YOS'; // Fallback to 'YOS' if event name isn't available
    
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
    
    // Replace any mentions of "YOS" in the introduction with the actual event name
    const updatedIntroduction = termConditionContent.introduction
        .replace('Event **YOS**', `Event **${eventName}**`)
        .replace('Panitia **YOS**', `Panitia **${eventName}**`);
    
    // Update footer similarly
    const updatedFooter = termConditionContent.footer
        .replace('Panitia **YOS**', `Panitia **${eventName}**`);
    
    return (
        <AuthenticatedLayout client={client} props={props}>
            <Head title="Terms and Conditions" />
            <div className="py-8">
                <div className="mx-auto max-w-7xl sm:px-6 lg:px-8">
                    <div className="overflow-hidden shadow-sm sm:rounded-lg"
                        style={{
                            backgroundColor: props.primary_color,
                        }}>
                        <div className="p-6 flex flex-col items-center justify-center"
                            style={{
                                color: props.text_primary_color,
                            }}>
                            {/* Title */}
                            <h1 className="text-6xl md:text-8xl font-normal text-center mb-6 font-[AstroJack] tracking-wider"
                                style={{
                                    color: props.text_secondary_color,
                                }}>
                                Term And Condition
                            </h1>
                           
                            {/* Main content */}
                            <div className="rounded-lg p-6 lg:p-10 w-full lg:w-[80%] shadow-lg mt-4 font-[Inter]"
                                style={{
                                    backgroundColor: props.secondary_color,
                                    color: props.text_secondary_color,
                                }}>
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
                                                    {renderBoldText(section.content)}
                                                </li>
                                            ),
                                        )}
                                    </ol>
                                    <p className="mt-8 whitespace-pre-line text-base lg:text-lg">
                                        {renderBoldText(updatedFooter)}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}