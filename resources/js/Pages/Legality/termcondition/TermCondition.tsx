import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import GuestLayout from '@/Layouts/GuestLayout';
import { EventProps } from '@/types/front-end';
import { Head } from '@inertiajs/react';
import { useEffect, useRef, useState } from 'react';
import {
    termConditionContent,
    TermConditionSection,
} from './termconditioncontent';

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

export default function TermCondition({
    client,
    props,
    // event,
    user,
    dbContent,
}: Props) {
    // const eventName = event?.name || 'NovaTix';
    const contentRef = useRef<HTMLDivElement>(null);
    const [activeSection, setActiveSection] = useState<string | null>(null);
    const [language, setLanguage] = useState('indonesia');

    // const updatedIntroduction = termConditionContent.introduction.replace(
    //     'Event **NovaTix**',
    //     `Event **${eventName}**`,
    // );

    const updatedFooter = termConditionContent.footer;

    useEffect(() => {
        if (termConditionContent.sections.length > 0) {
            setActiveSection(termConditionContent.sections[0].id);
        }
    }, []);

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
    }, [dbContent, activeSection]);

    const renderBoldText = (text: string) => {
        return text.split(/(\*\*[^*]+\*\*)/).map((part, index) => {
            if (part.startsWith('**') && part.endsWith('**')) {
                return (
                    <strong key={index} className="font-bold">
                        {part.slice(2, -2)}
                    </strong>
                );
            }
            return part;
        });
    };

    const content = (
        <>
            <Head title="Syarat dan Ketentuan" />
            <div
                className="relative mb-10 w-full p-10 text-center text-white"
                style={{
                    background: `linear-gradient(135deg, ${props.secondary_color} 0%, ${props.primary_color} 100%)`,
                }}
            >
                <div className="mx-auto max-w-7xl">
                    <h1
                        className="animate-fade-in-up text-4xl font-extrabold tracking-tight"
                        style={{ color: props.text_secondary_color }}
                    >
                        {termConditionContent.title}
                    </h1>
                    <p
                        className="animate-fade-in-up mt-2 text-sm italic delay-100"
                        style={{ color: props.text_secondary_color }}
                    >
                        Terakhir kali diubah: {termConditionContent.lastUpdated}
                    </p>
                </div>
            </div>
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
                            {termConditionContent.mainTitle}
                        </div>
                        <nav className="space-y-2">
                            {termConditionContent.sections.map((section) => (
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
                                        setActiveSection(section.id);
                                        setTimeout(() => {
                                            document
                                                .getElementById(section.id)
                                                ?.scrollIntoView({
                                                    behavior: 'smooth',
                                                });
                                        }, 50);
                                    }}
                                >
                                    <span className="text-sm">•</span>
                                    {section.title}
                                </a>
                            ))}

                            {/* <button
                                className="mt-4 text-sm underline"
                                style={{
                                    color: props.text_secondary_color,
                                }}
                                onClick={() => setActiveSection(null)}
                            >
                                Tampilkan Semua Section
                            </button> */}
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
                            {termConditionContent.mainTitle}
                        </h2>
                        {/* <p
                            className="mb-4"
                            style={{ color: props.text_primary_color }}
                        >
                            {renderBoldText(updatedIntroduction)}
                        </p> */}
                        {/* <p
                            className="font-semibold"
                            style={{ color: props.text_primary_color }}
                        >
                            {termConditionContent.introductionNote}
                        </p> */}
                    </div>

                    {/* Content Sections */}
                    {termConditionContent.sections
                        .filter(
                            (section) =>
                                activeSection === null ||
                                section.id === activeSection,
                        )
                        .map((section: TermConditionSection) => (
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
                                    className="mb-4 whitespace-pre-line leading-relaxed transition-all duration-300"
                                    style={{
                                        color: props.text_primary_color,
                                        maxWidth: '75ch',
                                    }}
                                >
                                    {renderBoldText(section.content)}
                                </div>
                            </section>
                        ))}

                    {/* Footer */}
                    <div className="mt-12 border-t pt-6">
                        <p
                            className="whitespace-pre-line text-base"
                            style={{ color: props.text_primary_color }}
                        >
                            {renderBoldText(updatedFooter)}
                        </p>
                    </div>
                </div>
            </div>
            Language Selector
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
