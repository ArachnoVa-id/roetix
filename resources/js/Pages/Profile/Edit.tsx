import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { PageProps } from '@/types';
import { EventProps } from '@/types/front-end';
import { EventInterface } from '@/types/ticket';
import { Head } from '@inertiajs/react';
import UpdateContactForm from './Partials/UpdateContactForm';

export default function Edit({
    event,
    client,
    props,
}: PageProps<{
    event: EventInterface;
    mustVerifyEmail: boolean;
    status?: string;
    client: string;
    props: EventProps;
}>) {
    return (
        <AuthenticatedLayout props={props} client={client}>
            <Head title={'Profile | ' + event.name} />
            <div className="py-12">
                <div className="mx-auto flex h-full w-full max-w-7xl flex-col gap-8 sm:px-6 md:flex-row lg:px-8">
                    {/* <div
                        className="h-full w-full p-4 shadow sm:rounded-lg sm:p-8"
                        style={{
                            backgroundColor: props.primary_color,
                        }}
                    >
                        <UpdateProfileInformationForm
                            mustVerifyEmail={mustVerifyEmail}
                            status={status}
                            client={client}
                            props={props}
                        />
                    </div> */}

                    <div
                        className="h-full w-full p-4 shadow sm:rounded-lg sm:p-8"
                        style={{
                            backgroundColor: props.primary_color,
                        }}
                    >
                        <UpdateContactForm client={client} props={props} />
                    </div>

                    {/* <div
                        className="p-4 shadow sm:rounded-lg sm:p-8"
                        style={{ backgroundColor: props.primary_color }}
                    >
                        <UpdatePasswordForm
                            className="max-w-xl"
                            client={client}
                            props={props}
                        />
                    </div>

                    <div
                        className="p-4 shadow sm:rounded-lg sm:p-8"
                        style={{ backgroundColor: props.primary_color }}
                    >
                        <DeleteUserForm
                            className="max-w-xl"
                            client={client}
                            props={props}
                        />
                    </div> */}
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
