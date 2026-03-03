import { Head, Link, usePage } from '@inertiajs/react';
import { dashboard, login } from '@/routes';

const features = [
    {
        icon: (
            <svg className="h-6 w-6 text-teal-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
            </svg>
        ),
        title: 'Automated Review Requests',
        description: 'Send personalised review requests via email or SMS. Set it up once and let ReviewMate do the work.',
    },
    {
        icon: (
            <svg className="h-6 w-6 text-teal-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
            </svg>
        ),
        title: 'Track Your Performance',
        description: 'Monitor open rates, conversions, and your overall Google rating in one clean dashboard.',
    },
    {
        icon: (
            <svg className="h-6 w-6 text-teal-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09z" />
            </svg>
        ),
        title: 'AI-Powered Reply Suggestions',
        description: 'Get smart reply suggestions for every review. Respond professionally in seconds with AI assistance.',
    },
];

export default function Welcome() {
    const { auth } = usePage<{ auth: { user: { name: string } | null } }>().props;

    return (
        <>
            <Head title="ReviewMate — Get More Google Reviews">
                <link rel="preconnect" href="https://fonts.bunny.net" />
                <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700" rel="stylesheet" />
            </Head>

            <div className="min-h-screen bg-white font-[instrument-sans]">
                {/* Navigation */}
                <header className="sticky top-0 z-50 border-b border-gray-100 bg-white/80 backdrop-blur">
                    <nav className="mx-auto flex max-w-6xl items-center justify-between px-4 py-4">
                        <div className="flex items-center gap-2">
                            <div className="flex h-8 w-8 items-center justify-center rounded-lg bg-teal-600">
                                <svg className="h-5 w-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M11.48 3.499a.562.562 0 011.04 0l2.125 5.111a.563.563 0 00.475.345l5.518.442c.499.04.701.663.321.988l-4.204 3.602a.563.563 0 00-.182.557l1.285 5.385a.562.562 0 01-.84.61l-4.725-2.885a.563.563 0 00-.586 0L6.982 20.54a.562.562 0 01-.84-.61l1.285-5.386a.562.562 0 00-.182-.557l-4.204-3.602a.562.562 0 01.321-.988l5.518-.442a.563.563 0 00.475-.345L11.48 3.5z" />
                                </svg>
                            </div>
                            <span className="text-lg font-bold text-gray-900">ReviewMate</span>
                        </div>

                        <div className="flex items-center gap-3">
                            {auth.user ? (
                                <Link
                                    href={dashboard()}
                                    className="rounded-lg bg-teal-600 px-4 py-2 text-sm font-medium text-white transition hover:bg-teal-700"
                                >
                                    Go to Dashboard
                                </Link>
                            ) : (
                                <>
                                    <Link
                                        href={login()}
                                        className="text-sm font-medium text-gray-600 transition hover:text-gray-900"
                                    >
                                        Log in
                                    </Link>
                                    <Link
                                        href="/register"
                                        className="rounded-lg bg-teal-600 px-4 py-2 text-sm font-medium text-white transition hover:bg-teal-700"
                                    >
                                        Get Started Free
                                    </Link>
                                </>
                            )}
                        </div>
                    </nav>
                </header>

                {/* Hero */}
                <section className="mx-auto max-w-6xl px-4 py-20 text-center md:py-32">
                    <div className="mb-6 inline-flex items-center gap-2 rounded-full border border-teal-200 bg-teal-50 px-4 py-1.5 text-sm font-medium text-teal-700">
                        <span className="flex h-2 w-2 rounded-full bg-teal-500" />
                        Trusted by local businesses across Australia
                    </div>

                    <h1 className="mb-6 text-4xl font-bold leading-tight text-gray-900 md:text-6xl">
                        Get More Google Reviews{' '}
                        <span className="text-teal-600">on Autopilot</span>
                    </h1>

                    <p className="mx-auto mb-10 max-w-2xl text-lg text-gray-500 md:text-xl">
                        ReviewMate automatically sends personalised review requests to your customers after every job.
                        More reviews. Better ranking. More customers.
                    </p>

                    <div className="flex flex-col items-center gap-4 sm:flex-row sm:justify-center">
                        <Link
                            href="/register"
                            className="w-full rounded-xl bg-teal-600 px-8 py-4 text-base font-semibold text-white shadow-lg shadow-teal-500/20 transition hover:bg-teal-700 sm:w-auto"
                        >
                            Start Getting Reviews Today
                        </Link>
                        <Link
                            href={login()}
                            className="w-full rounded-xl border border-gray-200 bg-white px-8 py-4 text-base font-semibold text-gray-700 transition hover:border-gray-300 sm:w-auto"
                        >
                            Sign in to your account
                        </Link>
                    </div>

                    <p className="mt-4 text-sm text-gray-400">No credit card required. Free to get started.</p>
                </section>

                {/* Social Proof Numbers */}
                <section className="border-y border-gray-100 bg-gray-50 py-12">
                    <div className="mx-auto max-w-4xl px-4">
                        <div className="grid grid-cols-3 gap-8 text-center">
                            <div>
                                <div className="text-3xl font-bold text-teal-600 md:text-4xl">4.9★</div>
                                <div className="mt-1 text-sm text-gray-500">Average rating achieved</div>
                            </div>
                            <div>
                                <div className="text-3xl font-bold text-gray-900 md:text-4xl">30%</div>
                                <div className="mt-1 text-sm text-gray-500">Average conversion rate</div>
                            </div>
                            <div>
                                <div className="text-3xl font-bold text-gray-900 md:text-4xl">5 min</div>
                                <div className="mt-1 text-sm text-gray-500">Setup time</div>
                            </div>
                        </div>
                    </div>
                </section>

                {/* Features */}
                <section className="mx-auto max-w-6xl px-4 py-20">
                    <div className="mb-12 text-center">
                        <h2 className="text-3xl font-bold text-gray-900 md:text-4xl">Everything you need to grow your reputation</h2>
                        <p className="mt-4 text-gray-500">Simple tools for local businesses to get more reviews without any hassle.</p>
                    </div>

                    <div className="grid gap-8 md:grid-cols-3">
                        {features.map((feature, index) => (
                            <div key={index} className="rounded-2xl border border-gray-100 bg-white p-6 shadow-sm">
                                <div className="mb-4 flex h-12 w-12 items-center justify-center rounded-xl bg-teal-50">
                                    {feature.icon}
                                </div>
                                <h3 className="mb-2 text-lg font-semibold text-gray-900">{feature.title}</h3>
                                <p className="text-sm leading-relaxed text-gray-500">{feature.description}</p>
                            </div>
                        ))}
                    </div>
                </section>

                {/* How it Works */}
                <section className="bg-gray-50 py-20">
                    <div className="mx-auto max-w-4xl px-4">
                        <div className="mb-12 text-center">
                            <h2 className="text-3xl font-bold text-gray-900 md:text-4xl">Up and running in minutes</h2>
                        </div>

                        <div className="relative space-y-8">
                            {[
                                {
                                    step: '1',
                                    title: 'Add your business details',
                                    description: 'Tell us your business type and connect your Google Business Profile.',
                                },
                                {
                                    step: '2',
                                    title: 'Customise your template',
                                    description: 'We generate a template tailored to your industry. Tweak it to match your voice.',
                                },
                                {
                                    step: '3',
                                    title: 'Start sending requests',
                                    description: 'Add customers and send review requests in seconds. Watch the reviews roll in.',
                                },
                            ].map((item, index) => (
                                <div key={index} className="flex items-start gap-6">
                                    <div className="flex h-12 w-12 shrink-0 items-center justify-center rounded-full bg-teal-600 text-lg font-bold text-white shadow-lg shadow-teal-500/30">
                                        {item.step}
                                    </div>
                                    <div className="pt-1">
                                        <h3 className="mb-1 text-lg font-semibold text-gray-900">{item.title}</h3>
                                        <p className="text-gray-500">{item.description}</p>
                                    </div>
                                </div>
                            ))}
                        </div>
                    </div>
                </section>

                {/* CTA */}
                <section className="py-20">
                    <div className="mx-auto max-w-3xl px-4 text-center">
                        <h2 className="mb-4 text-3xl font-bold text-gray-900 md:text-4xl">
                            Ready to boost your Google rating?
                        </h2>
                        <p className="mb-8 text-lg text-gray-500">
                            Join local businesses using ReviewMate to build their online reputation.
                        </p>
                        <Link
                            href="/register"
                            className="inline-block rounded-xl bg-teal-600 px-10 py-4 text-base font-semibold text-white shadow-lg shadow-teal-500/20 transition hover:bg-teal-700"
                        >
                            Get Started Free Today
                        </Link>
                    </div>
                </section>

                {/* Footer */}
                <footer className="border-t border-gray-100 py-8">
                    <div className="mx-auto max-w-6xl px-4">
                        <div className="flex items-center justify-between">
                            <div className="flex items-center gap-2">
                                <div className="flex h-6 w-6 items-center justify-center rounded-md bg-teal-600">
                                    <svg className="h-4 w-4 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M11.48 3.499a.562.562 0 011.04 0l2.125 5.111a.563.563 0 00.475.345l5.518.442c.499.04.701.663.321.988l-4.204 3.602a.563.563 0 00-.182.557l1.285 5.385a.562.562 0 01-.84.61l-4.725-2.885a.563.563 0 00-.586 0L6.982 20.54a.562.562 0 01-.84-.61l1.285-5.386a.562.562 0 00-.182-.557l-4.204-3.602a.562.562 0 01.321-.988l5.518-.442a.563.563 0 00.475-.345L11.48 3.5z" />
                                    </svg>
                                </div>
                                <span className="text-sm font-semibold text-gray-700">ReviewMate</span>
                            </div>
                            <p className="text-sm text-gray-400">© {new Date().getFullYear()} ReviewMate. All rights reserved.</p>
                        </div>
                    </div>
                </footer>
            </div>
        </>
    );
}
