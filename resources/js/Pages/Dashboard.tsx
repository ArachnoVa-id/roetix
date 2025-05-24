import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { EventProps } from '@/types/front-end';
import { Head } from '@inertiajs/react';

export default function Dashboard({
    appName,
    client,
    props,
}: {
    appName: string;
    client: string;
    props: EventProps;
}) {
    return (
        <AuthenticatedLayout
            appName={appName}
            client={client}
            props={props}
            header={
                <h2 className="text-xl font-semibold leading-tight text-gray-800">
                    Dashboard
                </h2>
            }
        >
            <Head title="Dashboard" />

            <div className="py-12">
                <div className="mx-auto max-w-7xl sm:px-6 lg:px-8">
                    <div className="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                        <div className="p-6 text-gray-900">
                            You're logged in!
                        </div>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
