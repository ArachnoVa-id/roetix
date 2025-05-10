import Checkbox from '@/Components/Checkbox';
import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import TextInput from '@/Components/TextInput';
import GuestLayout from '@/Layouts/GuestLayout';
import { Event, EventProps } from '@/types/front-end';
import { faGoogle } from '@fortawesome/free-brands-svg-icons';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import { Head, Link, useForm } from '@inertiajs/react';
import { FormEventHandler } from 'react';

export default function Login({
    status,
    canResetPassword,
    event,
    client,
    props,
    message,
}: {
    status?: string;
    canResetPassword: boolean;
    event: Event;
    client: string;
    props: EventProps;
    message: string;
}) {
    const { data, setData, post, processing, errors, reset } = useForm<{
        email: string;
        password: string;
        remember: boolean;
        client: string;
    }>({
        email: '',
        password: '',
        remember: false,
        client: client,
    });

    const submit: FormEventHandler = async (e) => {
        e.preventDefault();

        post(route('post.login'), {
            onSuccess: () => {
                window.location.reload();
            },
            onFinish: () => reset('password'),
        });
    };

    // alert message if exist
    if (message) {
        alert(message);
    }

    return (
        <GuestLayout props={props} client={client}>
            <Head title={'Log In | ' + event.name} />

            {status && (
                <div className="text-sm font-medium text-green-600 shadow-md">
                    {status}
                </div>
            )}

            <div className="text-center font-bold">{event.name}</div>
            {event.name === 'Admin NovaTix' ? (
                <form onSubmit={submit} className="md:w-94 w-full">
                    <div>
                        <InputLabel htmlFor="email" value="Email" />

                        <TextInput
                            id="email"
                            type="email"
                            name="email"
                            value={data.email}
                            className="mt-1 block w-full"
                            autoComplete="username"
                            isFocused={true}
                            onChange={(
                                e: React.ChangeEvent<HTMLInputElement>,
                            ) => setData('email', e.target.value)}
                        />

                        <InputError message={errors.email} className="mt-2" />
                    </div>

                    <div className="mt-4">
                        <InputLabel htmlFor="password" value="Password" />

                        <TextInput
                            id="password"
                            type="password"
                            name="password"
                            value={data.password}
                            className="mt-1 block w-full"
                            autoComplete="current-password"
                            onChange={(
                                e: React.ChangeEvent<HTMLInputElement>,
                            ) => setData('password', e.target.value)}
                        />

                        <InputError
                            message={errors.password}
                            className="mt-2"
                        />
                    </div>

                    <div className="mt-4 flex items-center justify-between">
                        <label className="flex items-center">
                            <Checkbox
                                name="remember"
                                checked={data.remember}
                                onChange={(
                                    e: React.ChangeEvent<HTMLInputElement>,
                                ) =>
                                    setData(
                                        'remember',
                                        e.target.checked as boolean,
                                    )
                                }
                            />
                            <span className="ms-2 text-sm text-gray-600">
                                Remember me
                            </span>
                        </label>
                        {canResetPassword && (
                            <Link
                                href={route('password.request')}
                                className="rounded-md text-sm text-gray-600 underline hover:text-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
                            >
                                Forgot your password?
                            </Link>
                        )}

                        <PrimaryButton className="ms-4" disabled={processing}>
                            Log in
                        </PrimaryButton>
                    </div>
                    <div className="mt-2 flex flex-col items-center justify-center gap-0">
                        <p className="text-sm text-gray-700">or</p>
                        <a
                            href={route(
                                client ? 'client-auth.google' : 'auth.google',
                                client,
                            )}
                            className="flex w-full items-center justify-center gap-2 rounded-md bg-red-700 px-4 py-2 text-center font-bold text-white"
                        >
                            <FontAwesomeIcon icon={faGoogle} size="sm" />
                            <span className="text-xs">Login With Google</span>
                        </a>
                    </div>
                </form>
            ) : (
                <div className="flex">
                    <a
                        href={route(
                            client ? 'client-auth.google' : 'auth.google',
                            client,
                        )}
                        className="mt-2 flex w-full items-center justify-center gap-2 rounded-md bg-red-700 px-4 py-2 text-center font-bold text-white"
                    >
                        <FontAwesomeIcon icon={faGoogle} size="sm" />
                        <span className="text-xs">Login With Google</span>
                    </a>
                </div>
            )}
        </GuestLayout>
    );
}
