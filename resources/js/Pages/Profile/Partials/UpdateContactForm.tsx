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
            nickname: user.contact_info.nickname,
            fullname: user.contact_info.fullname,
            avatar: user.contact_info.avatar,
            phone_number: user.contact_info.phone_number,
            whatsapp_number: user.contact_info.whatsapp_number,
            instagram: user.contact_info.instagram,
            birth_date: user.contact_info.birth_date,
            gender: user.contact_info.gender,
            address: user.contact_info.address,
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
                    Update your account information for better content serving.
                </p>
            </header>

            <form onSubmit={submit} className="mt-6 flex flex-col gap-6">
                {/* Personal Information Section */}
                <div className="flex flex-wrap gap-6">
                    <div className="min-w-[250px] flex-1">
                        <InputLabel
                            htmlFor="nickname"
                            value="Nickname"
                            style={{ color: props.text_primary_color }}
                        />
                        <TextInput
                            id="nickname"
                            className="mt-1 block w-full"
                            value={data.nickname}
                            onChange={(e) =>
                                setData('nickname', e.target.value)
                            }
                            autoComplete="nickname"
                            style={{ color: props.text_secondary_color }}
                        />
                        <InputError
                            className="mt-2"
                            message={errors.nickname}
                        />
                    </div>

                    <div className="min-w-[250px] flex-1">
                        <InputLabel
                            htmlFor="fullname"
                            value="Full Name"
                            style={{ color: props.text_primary_color }}
                        />
                        <TextInput
                            id="fullname"
                            className="mt-1 block w-full"
                            value={data.fullname}
                            onChange={(e) =>
                                setData('fullname', e.target.value)
                            }
                            autoComplete="fullname"
                            style={{ color: props.text_secondary_color }}
                        />
                        <InputError
                            className="mt-2"
                            message={errors.fullname}
                        />
                    </div>
                </div>

                {/* Contact Information Section */}
                <div className="flex flex-wrap gap-6">
                    <div className="min-w-[250px] flex-1">
                        <InputLabel
                            htmlFor="avatar"
                            value="Avatar URL"
                            style={{ color: props.text_primary_color }}
                        />
                        <TextInput
                            id="avatar"
                            className="mt-1 block w-full"
                            value={data?.avatar || ''}
                            onChange={(e) => setData('avatar', e.target.value)}
                            autoComplete="avatar"
                            style={{ color: props.text_secondary_color }}
                        />
                        <InputError className="mt-2" message={errors.avatar} />
                    </div>

                    <div className="min-w-[250px] flex-1">
                        <InputLabel
                            htmlFor="phone_number"
                            value="Phone Number"
                            style={{ color: props.text_primary_color }}
                        />
                        <TextInput
                            id="phone_number"
                            className="mt-1 block w-full"
                            value={data.phone_number}
                            onChange={(e) =>
                                setData('phone_number', e.target.value)
                            }
                            autoComplete="phone_number"
                            style={{ color: props.text_secondary_color }}
                        />
                        <InputError
                            className="mt-2"
                            message={errors.phone_number}
                        />
                    </div>

                    <div className="min-w-[250px] flex-1">
                        <InputLabel
                            htmlFor="whatsapp_number"
                            value="WhatsApp Number"
                            style={{ color: props.text_primary_color }}
                        />
                        <TextInput
                            id="whatsapp_number"
                            className="mt-1 block w-full"
                            value={data.whatsapp_number}
                            onChange={(e) =>
                                setData('whatsapp_number', e.target.value)
                            }
                            autoComplete="whatsapp_number"
                            style={{ color: props.text_secondary_color }}
                        />
                        <InputError
                            className="mt-2"
                            message={errors.whatsapp_number}
                        />
                    </div>
                </div>

                {/* Social Media Information Section */}
                <div className="flex flex-wrap gap-6">
                    <div className="min-w-[250px] flex-1">
                        <InputLabel
                            htmlFor="instagram"
                            value="Instagram (without @)"
                            style={{ color: props.text_primary_color }}
                        />
                        <TextInput
                            id="instagram"
                            className="mt-1 block w-full"
                            value={data.instagram}
                            onChange={(e) =>
                                setData('instagram', e.target.value)
                            }
                            autoComplete="instagram"
                            style={{ color: props.text_secondary_color }}
                        />
                        <InputError
                            className="mt-2"
                            message={errors.instagram}
                        />
                    </div>

                    <div className="min-w-[250px] flex-1">
                        <InputLabel
                            htmlFor="birth_date"
                            value="Birth Date"
                            style={{ color: props.text_primary_color }}
                        />
                        <TextInput
                            id="birth_date"
                            type="date"
                            className="mt-1 block w-full"
                            value={data.birth_date}
                            onChange={(e) =>
                                setData('birth_date', e.target.value)
                            }
                            autoComplete="birth_date"
                            style={{ color: props.text_secondary_color }}
                        />
                        <InputError
                            className="mt-2"
                            message={errors.birth_date}
                        />
                    </div>
                </div>

                {/* Additional Information Section */}
                <div className="flex flex-wrap gap-6">
                    <div className="min-w-[250px] flex-1">
                        <InputLabel
                            htmlFor="gender"
                            value="Gender"
                            style={{ color: props.text_primary_color }}
                        />
                        <select
                            id="gender"
                            className="mt-1 block w-full rounded-md border shadow-sm"
                            value={data.gender}
                            onChange={(e) => setData('gender', e.target.value)}
                            style={{
                                color: props.text_secondary_color,
                                backgroundColor: 'rgba(255, 255, 255, 0.1)',
                            }}
                        >
                            <option value="">Select Gender</option>
                            <option value="male">Male</option>
                            <option value="female">Female</option>
                            <option value="other">Other</option>
                            <option value="prefer_not_to_say">
                                Prefer not to say
                            </option>
                        </select>
                        <InputError className="mt-2" message={errors.gender} />
                    </div>

                    <div className="min-w-[250px] flex-1">
                        <InputLabel
                            htmlFor="address"
                            value="Address"
                            style={{ color: props.text_primary_color }}
                        />
                        <textarea
                            id="address"
                            className="mt-1 block w-full rounded-md border border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                            value={data.address}
                            onChange={(e) => setData('address', e.target.value)}
                            rows={4}
                            style={{
                                color: props.text_secondary_color,
                                backgroundColor: 'rgba(255, 255, 255, 0.1)',
                            }}
                        />
                        <InputError className="mt-2" message={errors.address} />
                    </div>
                </div>

                {/* Save Button */}
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
                            style={{ color: props.text_secondary_color }}
                        >
                            Saved.
                        </p>
                    </Transition>
                </div>
            </form>
        </section>
    );
}
