import { Head, Link } from '@inertiajs/react';

const releases = [
    {
        version: '1.5.0',
        date: '2026-03-04',
        type: 'feature',
        title: 'Integrations expansion',
        changes: [
            'Added Simpro integration (large tradie businesses — OAuth)',
            'Added Halaxy integration (GPs and allied health — API key)',
            'Added generic incoming webhook (Zapier, Make, Fergus)',
            'Added Facebook Reviews page link support',
            'Added QR code page with downloadable image',
            'Google Places rating stats now shown on dashboard',
        ],
    },
    {
        version: '1.4.0',
        date: '2026-03-03',
        type: 'feature',
        title: 'Allied health and salon integrations',
        changes: [
            'Added Cliniko integration (allied health — API key, appointment polling)',
            'Added Timely integration (salons/beauty — OAuth, appointment webhook)',
            'Automated review requests on appointment completion for both',
        ],
    },
    {
        version: '1.3.0',
        date: '2026-03-03',
        type: 'feature',
        title: 'ServiceM8 and Xero integrations',
        changes: [
            'Added ServiceM8 OAuth integration for tradies (job completion webhook)',
            'Added Xero invoice-paid webhook integration',
            'Auto-send review requests on job completion or invoice payment',
            'Integrations settings page with connection status for all providers',
        ],
    },
    {
        version: '1.2.0',
        date: '2026-03-03',
        type: 'feature',
        title: 'SMS with ClickSend, multi-business, analytics',
        changes: [
            'Added ClickSend as default SMS provider (cheaper AU rates)',
            'Twilio remains as fallback SMS provider',
            'Multi-business support with session-based switching',
            'Multi-location analytics page',
            'Subscription billing via Stripe Cashier',
        ],
    },
    {
        version: '1.1.0',
        date: '2026-03-03',
        type: 'feature',
        title: 'AI replies, Google sync, templates',
        changes: [
            'AI review reply suggestions via Claude (Anthropic)',
            'Post replies to Google directly from ReviewMate',
            'Auto-sync Google reviews every 2 hours',
            'Email template editor',
            'Saved reply templates',
            'Email flow visualiser',
        ],
    },
    {
        version: '1.0.0',
        date: '2026-03-03',
        type: 'launch',
        title: 'Initial launch',
        changes: [
            'WorkOS authentication (passwordless, SSO-grade)',
            'Business + customer management',
            'Email review request sending with personalisation',
            'SMS review requests via Twilio',
            'Bulk send and Quick Send',
            'Review request tracking link',
            'Automated day-5 follow-ups',
            'Google Business Profile OAuth',
            'CSV import / export',
            'Unsubscribe management',
            '3-step onboarding wizard',
            'Free plan with limits (50 customers, 10 requests/month)',
            'Stripe billing (Starter $49/mo, Pro $99/mo)',
            'Terms of Service and Privacy Policy',
        ],
    },
];

const typeBadge: Record<string, string> = {
    launch: 'bg-teal-100 text-teal-700',
    feature: 'bg-blue-100 text-blue-700',
    fix: 'bg-orange-100 text-orange-700',
};

export default function Changelog() {
    return (
        <>
            <Head title="Changelog — ReviewMate" />

            <div className="min-h-screen bg-background text-foreground antialiased">

                {/* Nav */}
                <nav className="mx-auto flex max-w-4xl items-center justify-between px-6 py-5">
                    <Link href="/" className="flex items-center gap-2">
                        <div className="flex h-8 w-8 items-center justify-center rounded-lg bg-teal-600">
                            <svg className="h-4 w-4 text-white" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                            </svg>
                        </div>
                        <span className="text-lg font-bold tracking-tight">ReviewMate</span>
                    </Link>
                    <Link href="/login" className="rounded-lg border border-border px-4 py-2 text-sm font-medium text-muted-foreground hover:border-border hover:text-foreground transition-colors">
                        Sign in
                    </Link>
                </nav>

                {/* Header */}
                <section className="mx-auto max-w-3xl px-6 pt-12 pb-10">
                    <h1 className="text-3xl font-extrabold tracking-tight sm:text-4xl">Changelog</h1>
                    <p className="mt-3 text-muted-foreground">Everything new in ReviewMate, most recent first.</p>
                </section>

                {/* Releases */}
                <section className="mx-auto max-w-3xl px-6 pb-20">
                    <div className="relative border-l border-border pl-8 space-y-12">
                        {releases.map((release) => (
                            <div key={release.version} className="relative">
                                <div className="absolute -left-10 top-1.5 flex h-5 w-5 items-center justify-center rounded-full border-2 border-teal-500 bg-card">
                                    <div className="h-2 w-2 rounded-full bg-teal-500" />
                                </div>
                                <div className="flex flex-wrap items-center gap-2 mb-2">
                                    <span className="text-xs font-mono text-muted-foreground">{release.date}</span>
                                    <span className={`rounded-full px-2 py-0.5 text-xs font-semibold ${typeBadge[release.type] ?? 'bg-muted text-muted-foreground'}`}>
                                        {release.type}
                                    </span>
                                    <span className="text-xs font-mono text-muted-foreground">v{release.version}</span>
                                </div>
                                <h3 className="text-lg font-semibold text-foreground mb-3">{release.title}</h3>
                                <ul className="space-y-1.5">
                                    {release.changes.map((change) => (
                                        <li key={change} className="flex items-start gap-2 text-sm text-muted-foreground">
                                            <svg className="mt-0.5 h-4 w-4 shrink-0 text-teal-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={2.5}>
                                                <path strokeLinecap="round" strokeLinejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                                            </svg>
                                            {change}
                                        </li>
                                    ))}
                                </ul>
                            </div>
                        ))}
                    </div>
                </section>

                {/* Footer */}
                <footer className="border-t border-border py-8">
                    <div className="mx-auto max-w-4xl px-6 flex flex-col items-center gap-3 sm:flex-row sm:justify-between">
                        <span className="text-sm font-semibold text-foreground">ReviewMate</span>
                        <div className="flex items-center gap-4 text-xs text-muted-foreground">
                            <Link href="/terms" className="hover:text-muted-foreground transition-colors">Terms</Link>
                            <Link href="/privacy" className="hover:text-muted-foreground transition-colors">Privacy</Link>
                            <span>&copy; {new Date().getFullYear()} ReviewMate</span>
                        </div>
                    </div>
                </footer>

            </div>
        </>
    );
}
