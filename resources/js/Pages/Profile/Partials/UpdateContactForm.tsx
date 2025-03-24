import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import TextInput from '@/Components/TextInput';
import { EventProps } from '@/types/front-end';
import { Transition } from '@headlessui/react';
import { useForm, usePage } from '@inertiajs/react';
import { FormEventHandler } from 'react';

export default function UpdateContactForm({
    className = '',
    client,
    props,
}: {
    className?: string;
    client: string;
    props: EventProps;
}) {
    const user = usePage().props.auth.user;

    const { data, setData, errors, put, processing, recentlySuccessful } =
        useForm({
            phone_number: user.contact_info.phone_number,
            whatsapp_number: user.contact_info.whatsapp_number,
            instagram: user.contact_info.instagram,
        });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();

        put(route('profile.contact_update', { client }), {
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
                    Contact Information
                </h2>

                <p
                    className="mt-1 text-sm"
                    style={{ color: props.text_secondary_color }}
                >
                    Update your account's contact information.
                </p>
            </header>

            <form onSubmit={submit} className="mt-6 space-y-6">
                <div>
                    <InputLabel
                        htmlFor="phone_number"
                        value="Phone Number"
                        style={{
                            color: props.text_primary_color,
                        }}
                    />

                    <TextInput
                        id="phone_number"
                        className="mt-1 block w-full"
                        value={data.phone_number}
                        onChange={(e: React.ChangeEvent<HTMLInputElement>) =>
                            setData('phone_number', e.target.value)
                        }
                        required
                        isFocused
                        autoComplete="phone_number"
                        style={{
                            color: props.text_secondary_color,
                        }}
                    />

                    <InputError
                        className="mt-2"
                        message={errors.phone_number}
                    />
                </div>

                <div>
                    <InputLabel
                        htmlFor="whatsapp_number"
                        value="WhatsApp Number"
                        style={{
                            color: props.text_primary_color,
                        }}
                    />

                    <TextInput
                        id="whatsapp_number"
                        className="mt-1 block w-full"
                        value={data.whatsapp_number}
                        onChange={(e: React.ChangeEvent<HTMLInputElement>) =>
                            setData('whatsapp_number', e.target.value)
                        }
                        required
                        isFocused
                        autoComplete="whatsapp_number"
                        style={{
                            color: props.text_secondary_color,
                        }}
                    />

                    <InputError
                        className="mt-2"
                        message={errors.whatsapp_number}
                    />
                </div>

                <div>
                    <InputLabel
                        htmlFor="instagram"
                        value="Instagram"
                        style={{
                            color: props.text_primary_color,
                        }}
                    />

                    <TextInput
                        id="instagram"
                        className="mt-1 block w-full"
                        value={data.instagram}
                        onChange={(e: React.ChangeEvent<HTMLInputElement>) =>
                            setData('instagram', e.target.value)
                        }
                        required
                        isFocused
                        autoComplete="instagram"
                        style={{
                            color: props.text_secondary_color,
                        }}
                    />

                    <InputError className="mt-2" message={errors.instagram} />
                </div>

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
