import { privacyPolicyContent } from './privacypolicycontent';
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

export default function PrivacyPolicy({ client, props, event }: Props) {
    const eventName = event?.name || 'YOS'; // Fallback to 'YOS' if event name isn't available
    
    return (
        <AuthenticatedLayout client={client} props={props}>
            <Head title="Privacy Policy" />
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
                            {/* Title and last updated */}
                            <h1 className="text-6xl md:text-8xl font-normal text-center mb-2 font-[AstroJack] tracking-wider"
                                style={{
                                    color: props.text_secondary_color,
                                }}>
                                {privacyPolicyContent.title}
                            </h1>
                            <p className="text-center text-lg lg:text-2xl font-bold mb-6 font-[GillSansMT] italic">
                                Terakhir kali diubah: {privacyPolicyContent.lastUpdated}
                            </p>
                           
                            {/* Main content */}
                            <div className="rounded-lg p-6 lg:p-10 w-full lg:w-[80%] shadow-lg mt-4 font-[Inter]"
                                style={{
                                    backgroundColor: props.secondary_color,
                                    color: props.text_secondary_color,
                                }}>
                                <div className="text-justify space-y-4">
                                    <p className="text-base lg:text-lg font-semibold">
                                        {privacyPolicyContent.introduction.replace('Yogyakarta Oratorio Society', eventName)}
                                    </p>
                                    <ol className="list-decimal pl-5 space-y-4">
                                        {privacyPolicyContent.sections.map((section, index) => {
                                            // Replace any instances of "Yogyakarta Oratorio Society" with the event name
                                            let content = section.content;
                                            if (content) {
                                                content = content.replace('Yogyakarta Oratorio Society', eventName);
                                            }
                                            
                                            // Also update any points that might mention the organization
                                            let points = section.points;
                                            if (points) {
                                                points = points.map(point => 
                                                    point.replace('Yogyakarta Oratorio Society', eventName)
                                                );
                                            }
                                            
                                            return (
                                                <li key={index}>
                                                    <strong className="text-lg lg:text-xl font-[Inter] font-semibold">
                                                        {section.title}
                                                    </strong>
                                                    <br />
                                                    {content}
                                                    {points && (
                                                        <ul className="list-disc pl-5 mt-1">
                                                            {points.map((point, pointIndex) => (
                                                                <li key={pointIndex}>{point}</li>
                                                            ))}
                                                        </ul>
                                                    )}
                                                </li>
                                            );
                                        })}
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