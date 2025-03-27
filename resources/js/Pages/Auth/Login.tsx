import Checkbox from '@/Components/Checkbox';
import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import TextInput from '@/Components/TextInput';
import GuestLayout from '@/Layouts/GuestLayout';
import { Event, EventProps } from '@/types/front-end';
import { Head, Link, useForm } from '@inertiajs/react';
import axios from 'axios';
import { FormEventHandler, useEffect } from 'react';

export default function Login({
    status,
    canResetPassword,
    event,
    client,
    props,
}: {
    status?: string;
    canResetPassword: boolean;
    event: Event;
    client: string;
    props: EventProps;
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

    useEffect(() => {
        axios.get('/sanctum/csrf-cookie', { withCredentials: true });
    }, []);

    const submit: FormEventHandler = async (e) => {
        e.preventDefault();

        await axios.get('/sanctum/csrf-cookie', { withCredentials: true });

        post(route('post.login'), {
            onSuccess: () => {
                window.location.reload();
            },
            onFinish: () => reset('password'),
        });
    };

    return (
        <GuestLayout props={props}>
            <Head title="Log in" />

            {status && (
                <div className="mb-4 text-sm font-medium text-green-600">
                    {status}
                </div>
            )}

            <div className='mb-6 text-center font-bold'>{event.name}</div>
            <form onSubmit={submit}>
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
                        onChange={(e: React.ChangeEvent<HTMLInputElement>) =>
                            setData('email', e.target.value)
                        }
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
                        onChange={(e: React.ChangeEvent<HTMLInputElement>) =>
                            setData('password', e.target.value)
                        }
                    />

                    <InputError message={errors.password} className="mt-2" />
                </div>

                <div className="mt-4 block">
                    <label className="flex items-center">
                        <Checkbox
                            name="remember"
                            checked={data.remember}
                            onChange={(
                                e: React.ChangeEvent<HTMLInputElement>,
                            ) =>
                                setData('remember', e.target.checked as boolean)
                            }
                        />
                        <span className="ms-2 text-sm text-gray-600">
                            Remember me
                        </span>
                    </label>
                </div>

                <div className="mt-4 flex items-center justify-end">
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
                <a
                    href={route('auth.google', {}, false)}
                    className="mt-2 w-full text-center"
                >
                    Login With Google
                </a>
            </form>
        </GuestLayout>
    );
}
