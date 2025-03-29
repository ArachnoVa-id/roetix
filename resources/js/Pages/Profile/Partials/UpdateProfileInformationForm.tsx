import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import TextInput from '@/Components/TextInput';
import { EventColorProps, EventProps } from '@/types/front-end';
import { Transition } from '@headlessui/react';
import { Link, useForm, usePage } from '@inertiajs/react';
import { FormEventHandler } from 'react';
import styled from 'styled-components';

const StyledLink = styled(Link)<{ $props: EventColorProps }>`
    color: ${({ $props }) => $props.text_primary_color};
    &:hover {
        color: ${({ $props }) => $props.primary_color};
    }
`;

export default function UpdateProfileInformation({
    mustVerifyEmail,
    status,
    className = '',
    client,
    props,
}: {
    mustVerifyEmail: boolean;
    status?: string;
    className?: string;
    client: string;
    props: EventProps;
}) {
    const user = usePage().props.auth.user;

    const { data, setData, patch, errors, processing, recentlySuccessful } =
        useForm({
            first_name: user.first_name,
            last_name: user.last_name,
        });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();

        patch(route('profile.update', { client }), {
            preserveScroll: true,
        });
    };

    return (
        <section className={className}>
            <header>
                <h2
                    className="text-lg font-medium"
                    style={{
                        color: props.text_primary_color,
                    }}
                >
                    Profile Information
                </h2>

                <p
                    className="mt-1 text-sm"
                    style={{ color: props.text_secondary_color }}
                >
                    Account's general profile.
                </p>
            </header>

            <form onSubmit={submit} className="mt-6 flex flex-col gap-6">
                <div>
                    <InputLabel
                        htmlFor="first_name"
                        value="First Name"
                        style={{
                            color: props.text_primary_color,
                        }}
                    />

                    <TextInput
                        id="first_name"
                        className="mt-1 block w-full"
                        value={data.first_name}
                        onChange={(e: React.ChangeEvent<HTMLInputElement>) =>
                            setData('first_name', e.target.value)
                        }
                        required
                        autoComplete="first_name"
                        style={{
                            color: props.text_primary_color,
                        }}
                    />

                    <InputError className="mt-2" message={errors.first_name} />
                </div>

                <div>
                    <InputLabel
                        htmlFor="last_name"
                        value="Last Name"
                        style={{
                            color: props.text_primary_color,
                        }}
                    />

                    <TextInput
                        id="last_name"
                        className="mt-1 block w-full"
                        value={data.last_name}
                        onChange={(e: React.ChangeEvent<HTMLInputElement>) =>
                            setData('last_name', e.target.value)
                        }
                        required
                        autoComplete="last_name"
                        style={{
                            color: props.text_primary_color,
                        }}
                    />

                    <InputError className="mt-2" message={errors.last_name} />
                </div>

                {/* <div>
                    <InputLabel
                        htmlFor="email"
                        value="Email"
                        style={{
                            color: props.text_primary_color,
                        }}
                    />

                    <TextInput
                        id="email"
                        type="email"
                        className="mt-1 block w-full"
                        value={data.email}
                        onChange={(e: React.ChangeEvent<HTMLInputElement>) =>
                            setData('email', e.target.value)
                        }
                        required
                        autoComplete="username"
                        style={{
                            color: props.text_primary_color,
                        }}
                    />

                    <InputError className="mt-2" message={errors.email} />
                </div> */}

                {mustVerifyEmail && user.email_verified_at === null && (
                    <div>
                        <p
                            className="mt-2 text-sm"
                            style={{ color: props.text_primary_color }}
                        >
                            Your email address is unverified.
                            <StyledLink
                                $props={props}
                                href={route('verification.send')}
                                method="post"
                                as="button"
                                className="rounded-md text-sm underline focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
                            >
                                Click here to re-send the verification email.
                            </StyledLink>
                        </p>

                        {status === 'verification-link-sent' && (
                            <div className="mt-2 text-sm font-medium text-green-600">
                                A new verification link has been sent to your
                                email address.
                            </div>
                        )}
                    </div>
                )}

                <div className="flex items-center gap-4">
                    <PrimaryButton disabled={processing}>Save</PrimaryButton>

                    <Transition
                        show={recentlySuccessful}
                        enter="transition ease-in-out"
                        enterFrom="opacity-0"
                        leave="transition ease-in-out"
                        leaveTo="opacity-0"
                    >
                        <p
                            className="text-sm"
                            style={{
                                color: props.text_secondary_color,
                            }}
                        >
                            Saved.
                        </p>
                    </Transition>
                </div>
            </form>
        </section>
    );
}
