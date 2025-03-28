import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { PageProps } from '@/types';
import { EventProps } from '@/types/front-end';
import { Head } from '@inertiajs/react';
import UpdateContactForm from './Partials/UpdateContactForm';
import UpdateProfileInformationForm from './Partials/UpdateProfileInformationForm';

export default function Edit({
    mustVerifyEmail,
    status,
    client,
    props,
}: PageProps<{
    mustVerifyEmail: boolean;
    status?: string;
    client: string;
    props: EventProps;
}>) {
    return (
        <AuthenticatedLayout
            props={props}
            client={client}
            header={
                <h2
                    className="text-xl font-semibold leading-tight"
                    style={{ color: props.text_primary_color }}
                >
                    Profile
                </h2>
            }
        >
            <Head title="Profile" />

            <div className="py-12">
                <div className="mx-auto flex h-full w-full max-w-7xl flex-col gap-8 sm:px-6 md:flex-row lg:px-8">
                    <div
                        className="h-[80vh] w-full p-4 shadow sm:rounded-lg sm:p-8 md:w-[35%]"
                        style={{
                            backgroundColor: props.primary_color,
                        }}
                    >
                        <UpdateProfileInformationForm
                            mustVerifyEmail={mustVerifyEmail}
                            status={status}
                            className="max-w-xl"
                            client={client}
                            props={props}
                        />
                    </div>

                    <div
                        className="h-full grow p-4 shadow sm:rounded-lg sm:p-8"
                        style={{
                            backgroundColor: props.primary_color,
                        }}
                    >
                        <UpdateContactForm
                            className="max-w-xl"
                            client={client}
                            props={props}
                        />
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
