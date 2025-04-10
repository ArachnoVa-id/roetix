import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import GuestLayout from '@/Layouts/GuestLayout';
import { EventProps } from '@/types/front-end';
import { Head } from '@inertiajs/react';
import { useEffect, useRef, useState } from 'react';
import {
    privacyPolicyContent,
    PrivacyPolicySection,
} from './privacypolicycontent';

interface Props {
    client: string;
    props: EventProps;
    event?: {
        name: string;
        [key: string]: string | number | boolean | object;
    };
    user?: {
        [key: string]: string | number | boolean | object;
    };
    dbContent: string;
}

export default function PrivacyPolicy({
    client,
    props,
    event,
    user,
    dbContent,
}: Props) {
    const eventName = event?.name || 'NovaTix'; // Fallback to 'NovaTix' if event name isn't available
    const contentRef = useRef<HTMLDivElement>(null);
    const [activeSection, setActiveSection] = useState<string | null>(null);
    const [language, setLanguage] = useState('indonesia');

    // Handling scroll spy untuk navigasi sidebar
    useEffect(() => {
        const handleScroll = () => {
            if (!contentRef.current) return;

            const sections = contentRef.current.querySelectorAll('section');
            let currentActiveSection = null;

            sections.forEach((section) => {
                const sectionTop = section.offsetTop;
                const sectionHeight = section.clientHeight;
                if (
                    window.scrollY >= sectionTop - 100 &&
                    window.scrollY < sectionTop + sectionHeight - 100
                ) {
                    currentActiveSection = section.id;
                }
            });

            if (currentActiveSection !== activeSection) {
                setActiveSection(currentActiveSection);
            }
        };

        window.addEventListener('scroll', handleScroll);
        return () => window.removeEventListener('scroll', handleScroll);
    }, [activeSection]);

    useEffect(() => {
        if (contentRef.current) {
            contentRef.current.querySelectorAll('ol').forEach((ol) => {
                ol.setAttribute(
                    'style',
                    'list-style-type: decimal; padding-left: 1.25rem; margin-top: 1rem; margin-bottom: 1rem;',
                );
            });

            contentRef.current.querySelectorAll('h2').forEach((h2) => {
                h2.setAttribute(
                    'style',
                    'font-size: 2rem; margin-top: 2rem; margin-bottom: 1rem;',
                );
            });

            contentRef.current.querySelectorAll('h3').forEach((h3) => {
                h3.setAttribute(
                    'style',
                    'font-size: 1.25rem; margin-top: 1.5rem; margin-bottom: 1rem;',
                );
            });
        }
    }, []);

    const content = (
        <>
            <Head title="Kebijakan Privasi" />
            <div
                className="relative w-full p-10 text-center text-white"
                style={{
                    background: `linear-gradient(135deg, ${props.secondary_color} 0%, ${props.primary_color} 100%)`,
                }}
            >
                <div className="mx-auto max-w-7xl">
                    <h1
                        className="animate-fade-in-up text-4xl font-extrabold tracking-tight"
                        style={{ color: props.text_secondary_color }}
                    >
                        {privacyPolicyContent.title}
                    </h1>
                    <p
                        className="animate-fade-in-up mt-2 text-sm italic delay-100"
                        style={{ color: props.text_secondary_color }}
                    >
                        Diperbarui: {privacyPolicyContent.lastUpdated}
                    </p>
                </div>
            </div>

            {dbContent ? (
                <div
                    ref={contentRef}
                    dangerouslySetInnerHTML={{ __html: dbContent }}
                />
            ) : (
                <div
                    className="mx-auto flex max-w-7xl flex-col px-4 py-8 md:flex-row"
                    ref={contentRef}
                >
                    {/* Sidebar Navigation */}
                    <div className="mb-8 md:mb-0 md:w-1/4">
                        <div
                            className="rounded-lg p-4"
                            style={{
                                backgroundColor: props.primary_color,
                                color: props.text_secondary_color,
                            }}
                        >
                            <div className="mb-4 text-lg font-bold">
                                {privacyPolicyContent.mainTitle}
                            </div>
                            <nav className="space-y-2">
                                {privacyPolicyContent.sections.map(
                                    (section) => (
                                        <a
                                            key={section.id}
                                            href={`#${section.id}`}
                                            className={`group flex items-center gap-2 rounded-md px-3 py-2 font-medium transition-all ${
                                                activeSection === section.id
                                                    ? 'scale-105 shadow-md'
                                                    : 'hover:bg-white/10'
                                            }`}
                                            style={{
                                                backgroundColor:
                                                    activeSection === section.id
                                                        ? props.secondary_color
                                                        : 'transparent',
                                                color: props.text_secondary_color,
                                            }}
                                            onClick={(e) => {
                                                e.preventDefault();
                                                document
                                                    .getElementById(section.id)
                                                    ?.scrollIntoView({
                                                        behavior: 'smooth',
                                                    });
                                            }}
                                        >
                                            <span className="text-sm">•</span>
                                            {section.title}
                                        </a>
                                    ),
                                )}
                            </nav>
                        </div>
                    </div>

                    {/* Main Content */}
                    <div className="md:w-3/4 md:pl-8">
                        <div className="mb-8">
                            <h2
                                className="mb-4 text-2xl font-bold"
                                style={{ color: props.primary_color }}
                            >
                                {privacyPolicyContent.mainTitle}
                            </h2>
                            <p
                                className="mb-4"
                                style={{ color: props.text_primary_color }}
                            >
                                {privacyPolicyContent.introduction}
                            </p>
                            <p
                                className="font-semibold"
                                style={{ color: props.text_primary_color }}
                            >
                                {privacyPolicyContent.introductionNote}
                            </p>
                        </div>

                        {/* <h3
                            className="mb-4 text-xl font-bold"
                            style={{ color: props.primary_color }}
                        >
                            Kebijakan Privasi ini mencakup hal-hal sebagai
                            berikut:
                        </h3>

                        <ol
                            className="mb-8 list-decimal pl-5"
                            style={{ color: props.text_primary_color }}
                        >
                            {privacyPolicyContent.sections.map(
                                (
                                    section: PrivacyPolicySection,
                                    index: number,
                                ) => (
                                    <li key={index} className="mb-1">
                                        <a
                                            href={`#${section.id}`}
                                            style={{
                                                color: props.primary_color,
                                            }}
                                            className="hover:underline"
                                            onClick={(e) => {
                                                e.preventDefault();
                                                document
                                                    .getElementById(section.id)
                                                    ?.scrollIntoView({
                                                        behavior: 'smooth',
                                                    });
                                            }}
                                        >
                                            {section.title}
                                        </a>
                                    </li>
                                ),
                            )}
                        </ol> */}

                        {/* Content Sections */}
                        {privacyPolicyContent.sections.map(
                            (section: PrivacyPolicySection) => (
                                <section
                                    key={section.id}
                                    id={section.id}
                                    className="mb-10"
                                >
                                    <h3
                                        className="mb-4 text-xl font-bold"
                                        style={{ color: props.primary_color }}
                                    >
                                        {section.title}
                                    </h3>
                                    <div
                                        className="whitespace-pre-line mb-4 leading-relaxed transition-all duration-300"
                                        style={{
                                            color: props.text_primary_color,
                                            maxWidth: '75ch',
                                        }}
                                    >
                                        {section.content}
                                    </div>
                                </section>
                            ),
                        )}
                    </div>
                </div>
            )}

            {/* Language Selector */}
            <div className="fixed right-4 top-32 z-50">
                <div className="flex rounded-full border bg-white p-1 shadow-lg">
                    {['indonesia', 'english'].map((lang) => (
                        <button
                            key={lang}
                            className={`rounded-full px-4 py-1 text-sm font-medium transition-all ${
                                language === lang
                                    ? 'scale-105 shadow-md'
                                    : 'text-gray-600'
                            }`}
                            style={{
                                backgroundColor:
                                    language === lang
                                        ? props.primary_color
                                        : 'transparent',
                                color:
                                    language === lang
                                        ? props.text_secondary_color
                                        : 'inherit',
                            }}
                            onClick={() => setLanguage(lang)}
                        >
                            {lang === 'english' ? 'English' : 'Indonesia'}
                        </button>
                    ))}
                </div>
            </div>

            {/* Back button */}
            <div className="flex items-center justify-center pb-8">
                <a
                    href={route(client ? 'client.home' : 'home', {
                        client: client,
                    })}
                    className="inline-flex items-center gap-2 rounded-full px-6 py-3 font-semibold shadow-md transition hover:scale-105 hover:opacity-90"
                    style={{
                        backgroundColor: props.primary_color,
                        color: props.text_secondary_color,
                    }}
                >
                    ← Kembali ke Beranda
                </a>
            </div>
        </>
    );

    if (user)
        return (
            <AuthenticatedLayout client={client} props={props}>
                {content}
            </AuthenticatedLayout>
        );
    else
        return (
            <GuestLayout client={client} props={props}>
                {content}
            </GuestLayout>
        );
}
