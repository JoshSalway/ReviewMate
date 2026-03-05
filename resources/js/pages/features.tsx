import { Head, Link } from '@inertiajs/react';

const features = [
    {
        title: 'Automated review requests',
        description: 'Send personalised email and SMS review requests after every job or sale. Set it once and ReviewMate handles the rest — including a follow-up 5 days later if they have not reviewed yet.',
        details: ['Personalised with customer name and business name', 'Email and SMS channels', 'Day-5 automatic follow-up', '30-day duplicate request guard'],
    },
    {
        title: 'AI reply suggestions',
        description: 'Get three AI-written reply options for any Google review in seconds. Powered by Claude (Anthropic). Pick one, tweak it, and post — without leaving ReviewMate.',
        details: ['3 AI reply options per review', 'Professional tone by default', 'Post directly to Google from ReviewMate', 'Saved reply templates for common responses'],
    },
    {
        title: 'Google Business Profile sync',
        description: 'Connect your Google Business Profile once and ReviewMate syncs your reviews every 2 hours automatically. See your full review history, rating trend, and reply status in one inbox.',
        details: ['OAuth connection — 2-minute setup', 'Auto-sync every 2 hours', 'Full review history', 'Reply without leaving ReviewMate'],
    },
    {
        title: 'Customer management',
        description: 'Import customers from CSV or add them manually. See exactly who has reviewed, who is pending, and who has not responded. Export any time.',
        details: ['CSV import with flexible column mapping', 'CSV export with review status', 'Bulk send to multiple customers', 'Unsubscribe management (CAN-SPAM compliant)'],
    },
    {
        title: 'Analytics & insights',
        description: 'Track your review count, average rating, request conversion rate, and pending replies. Multi-location support lets you compare performance across all locations.',
        details: ['Average rating over time chart', 'Conversion rate tracking', 'Pending reply counter', 'Multi-location comparison'],
    },
    {
        title: 'QR codes & quick send',
        description: 'Generate a branded QR code linking to your Google review page. Print it for your counter, include it in invoices, or share it digitally. Quick Send lets you send a one-off request in under 10 seconds.',
        details: ['Downloadable QR code image', 'Links directly to Google review form', 'Quick Send for ad-hoc requests', 'No customer record needed for quick send'],
    },
    {
        title: 'Integrations',
        description: 'Automatically trigger review requests when a job is completed or an invoice is paid. Connects with the tools Australian local businesses already use.',
        details: ['ServiceM8 (tradies — job completion)', 'Xero (invoice paid)', 'Cliniko (allied health — appointments)', 'Timely (salons — appointments)', 'Simpro (large tradie businesses)', 'Halaxy (GPs and allied health)', 'Generic webhook (Zapier / Make / Fergus)', 'Facebook Reviews page link'],
    },
    {
        title: 'Onboarding & templates',
        description: 'A guided 3-step onboarding wizard gets you set up in under 10 minutes. Choose your business type to get the right email template preloaded for your industry.',
        details: ['3-step wizard (business type, Google, template)', 'Pre-built templates for tradies, cafes, allied health', 'Editable email templates', 'Reply template library'],
    },
];

export default function Features() {
    return (
        <>
            <Head title="Features — ReviewMate" />

            <div className="min-h-screen bg-white text-gray-900 antialiased">

                {/* Nav */}
                <nav className="mx-auto flex max-w-6xl items-center justify-between px-6 py-5">
                    <Link href="/" className="flex items-center gap-2">
                        <div className="flex h-8 w-8 items-center justify-center rounded-lg bg-teal-600">
                            <svg className="h-4 w-4 text-white" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                            </svg>
                        </div>
                        <span className="text-lg font-bold tracking-tight">ReviewMate</span>
                    </Link>
                    <div className="flex items-center gap-4">
                        <Link href="/pricing" className="text-sm text-gray-500 hover:text-gray-900 transition-colors">Pricing</Link>
                        <Link href="/login" className="rounded-lg border border-gray-200 px-4 py-2 text-sm font-medium text-gray-600 hover:border-gray-300 hover:text-gray-900 transition-colors">
                            Sign in
                        </Link>
                    </div>
                </nav>

                {/* Hero */}
                <section className="mx-auto max-w-3xl px-6 pt-14 pb-16 text-center">
                    <h1 className="text-4xl font-extrabold tracking-tight sm:text-5xl">
                        Everything you need to dominate local Google search
                    </h1>
                    <p className="mt-4 text-lg text-gray-500">
                        ReviewMate handles the full review lifecycle — from request to reply — so you can focus on running your business.
                    </p>
                    <Link
                        href="/login"
                        className="mt-8 inline-flex items-center gap-2 rounded-xl bg-teal-600 px-8 py-3.5 text-sm font-semibold text-white shadow-md hover:bg-teal-700 transition-colors"
                    >
                        Start free — no credit card needed
                    </Link>
                </section>

                {/* Features */}
                <section className="bg-gray-50 py-20">
                    <div className="mx-auto max-w-6xl px-6">
                        <div className="grid gap-10 md:grid-cols-2">
                            {features.map((feature) => (
                                <div key={feature.title} className="rounded-2xl border border-gray-100 bg-white p-7 shadow-sm">
                                    <h3 className="mb-2 text-lg font-semibold text-gray-900">{feature.title}</h3>
                                    <p className="mb-4 text-sm text-gray-500 leading-relaxed">{feature.description}</p>
                                    <ul className="space-y-1.5">
                                        {feature.details.map((d) => (
                                            <li key={d} className="flex items-start gap-2 text-sm text-gray-600">
                                                <svg className="mt-0.5 h-4 w-4 shrink-0 text-teal-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={2.5}>
                                                    <path strokeLinecap="round" strokeLinejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                                                </svg>
                                                {d}
                                            </li>
                                        ))}
                                    </ul>
                                </div>
                            ))}
                        </div>
                    </div>
                </section>

                {/* CTA */}
                <section className="bg-teal-600 py-16">
                    <div className="mx-auto max-w-2xl px-6 text-center">
                        <h2 className="text-2xl font-bold text-white sm:text-3xl">See it in action</h2>
                        <p className="mt-3 text-teal-100">Start your free trial — no credit card, takes 10 minutes to set up.</p>
                        <Link
                            href="/login"
                            className="mt-6 inline-flex items-center gap-2 rounded-xl bg-white px-8 py-3.5 text-sm font-semibold text-teal-700 shadow-md hover:bg-teal-50 transition-colors"
                        >
                            Get started free
                        </Link>
                    </div>
                </section>

                {/* Footer */}
                <footer className="border-t border-gray-100 py-8">
                    <div className="mx-auto max-w-6xl px-6 flex flex-col items-center gap-3 sm:flex-row sm:justify-between">
                        <span className="text-sm font-semibold text-gray-700">ReviewMate</span>
                        <div className="flex items-center gap-4 text-xs text-gray-400">
                            <Link href="/pricing" className="hover:text-gray-600 transition-colors">Pricing</Link>
                            <Link href="/terms" className="hover:text-gray-600 transition-colors">Terms</Link>
                            <Link href="/privacy" className="hover:text-gray-600 transition-colors">Privacy</Link>
                            <span>&copy; {new Date().getFullYear()} ReviewMate</span>
                        </div>
                    </div>
                </footer>

            </div>
        </>
    );
}
