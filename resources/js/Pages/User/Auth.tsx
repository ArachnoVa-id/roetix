import { PageProps } from '@/types';
import { Head, Link } from '@inertiajs/react';

export default function Welcome({ auth }: PageProps) {
    return (
        <>
            <Head title="Welcome" />
            <div className="min-w-screen relative flex min-h-screen flex-col items-end selection:bg-[#FF2D20] selection:text-white">
                <header className="flex h-full w-full items-center py-5">
                    <nav className="flex w-full flex-1 justify-between px-10 py-2">
                        <div className="flex gap-5">
                            <img
                                src="/images/novatix-logo.jpeg"
                                alt="ArachnoVa"
                                className="h-8"
                            />
                            <div>NovaTix</div>
                        </div>
                        {auth.user ? (
                            <Link
                                href={route('home')}
                                className="rounded-md text-black ring-1 ring-transparent transition hover:text-black/70 focus:outline-none focus-visible:ring-[#FF2D20] dark:text-white dark:hover:text-white/80 dark:focus-visible:ring-white"
                            >
                                Dashboard
                            </Link>
                        ) : (
                            <div className="flex gap-4">
                                <Link
                                    href={route('login')}
                                    className="rounded-md text-black ring-1 ring-transparent transition hover:text-black/70 focus:outline-none focus-visible:ring-[#FF2D20] dark:text-white dark:hover:text-white/80 dark:focus-visible:ring-white"
                                >
                                    Log in
                                </Link>
                                <Link
                                    href={route('register')}
                                    className="rounded-md text-black ring-1 ring-transparent transition hover:text-black/70 focus:outline-none focus-visible:ring-[#FF2D20] dark:text-white dark:hover:text-white/80 dark:focus-visible:ring-white"
                                >
                                    Register
                                </Link>
                            </div>
                        )}
                    </nav>
                </header>

                <main className="flex w-full grow items-center justify-center">
                    <div>Staging Server</div>
                </main>
            </div>
        </>
    );
}
