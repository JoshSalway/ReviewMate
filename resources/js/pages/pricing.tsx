import { Head, Link } from '@inertiajs/react';
import { useState } from 'react';
import { SeoHead } from '@/components/seo-head';

function CheckIcon() {
    return (
        <svg className="h-5 w-5 text-teal-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={2.5}>
            <path strokeLinecap="round" strokeLinejoin="round" d="M4.5 12.75l6 6 9-13.5" />
        </svg>
    );
}

const plans = [
    {
        name: 'Free',
        price: '$0',
        period: null,
        description: 'Free forever. No credit card needed.',
        features: [
            '1 business location',
            'Up to 50 customers',
            '10 review requests / month',
            'AI reply suggestions',
            'Review inbox + dashboard',
            'Email review requests',
            'QR code page',
        ],
        cta: 'Get started free',
        highlighted: false,
    },
    {
        name: 'Starter',
        price: '$49',
        period: '/month',
        description: 'Everything you need to start getting reviews on autopilot.',
        features: [
            '1 business location',
            'Unlimited customers',
            'Unlimited review requests',
            'Email + SMS sending',
            'Automatic follow-up if they don\'t open the first email — doubles your review rate',
            'Weekly digest emails',
            'AI replies to your Google reviews every night — in your voice, automatically',
            'Google Business Profile sync',
            'CSV import / export',
            'Integration: ServiceM8, Xero, Cliniko, Timely, Simpro, Halaxy',
            'Email support',
        ],
        cta: 'Start free trial',
        highlighted: true,
    },
    {
        name: 'Pro',
        price: '$99',
        period: '/month',
        description: 'For businesses serious about their reputation. Automated follow-ups, AI replies, and more.',
        features: [
            'Up to 5 business locations',
            'Everything in Starter',
            'Multi-location analytics',
            'Location-by-location comparison',
            'Priority support',
        ],
        cta: 'Start free trial',
        highlighted: false,
    },
];

const faqs = [
    {
        question: 'How does the free trial work?',
        answer: 'Start free with up to 3 customers. No credit card required. Upgrade anytime when you\'re ready to grow.',
    },
    {
        question: 'Can I cancel anytime?',
        answer: 'Yes. Cancel with one click from your billing settings. No lock-in contracts, no cancellation fees.',
    },
    {
        question: 'How do the Google review requests work?',
        answer: 'You send a request via email or SMS to your customer. They tap a link, choose a star rating, and if it\'s 4–5 stars they\'re guided to leave a review on Google. It takes them 30 seconds.',
    },
    {
        question: 'Which SMS provider do you use?',
        answer: 'We use ClickSend for Australian numbers — one of the cheapest providers for AU SMS at around $0.03–0.04/message. International numbers use Twilio as a fallback.',
    },
    {
        question: 'Do my customers need an account?',
        answer: 'No. Your customers receive a simple email or SMS and tap a link. No app, no login, no friction.',
    },
    {
        question: 'What integrations do you support?',
        answer: 'We integrate with ServiceM8, Xero, Cliniko, Timely, Simpro, Halaxy, and more via webhooks. New integrations are added regularly.',
    },
    {
        question: 'Is my data safe?',
        answer: 'Yes. All data is encrypted in transit (HTTPS) and at rest. We\'re hosted on enterprise-grade infrastructure and never share your customer data with third parties.',
    },
    {
        question: 'What currency are the prices in?',
        answer: 'All prices are in Australian dollars (AUD). GST is not included for ABN-registered businesses.',
    },
    {
        question: 'Do I need a Google Business Profile?',
        answer: 'Yes, to sync reviews and reply via ReviewMate. You can still send review requests without it — customers will be linked to your Google review page via their Google Place ID.',
    },
    {
        question: 'Can I change plans at any time?',
        answer: 'Yes. Upgrade or downgrade from your billing settings. Upgrades take effect immediately.',
    },
];

function FaqAccordion({ items }: { items: { question: string; answer: string }[] }) {
    const [openIndex, setOpenIndex] = useState<number | null>(null);

    return (
        <div className="divide-y divide-border rounded-xl border border-border overflow-hidden">
            {items.map((item, index) => {
                const isOpen = openIndex === index;
                return (
                    <div key={item.question}>
                        <button
                            onClick={() => setOpenIndex(isOpen ? null : index)}
                            className="flex w-full items-center justify-between gap-4 px-6 py-5 text-left transition-colors hover:bg-muted/50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-teal-500"
                            aria-expanded={isOpen}
                        >
                            <span className="font-semibold text-foreground">{item.question}</span>
                            <span className={`flex h-5 w-5 shrink-0 items-center justify-center rounded-full border border-border text-muted-foreground transition-transform duration-200 ${isOpen ? 'rotate-45' : ''}`}>
                                <svg className="h-3 w-3" fill="none" viewBox="0 0 12 12" stroke="currentColor" strokeWidth={2}>
                                    <path strokeLinecap="round" strokeLinejoin="round" d="M6 1v10M1 6h10" />
                                </svg>
                            </span>
                        </button>
                        {isOpen && (
                            <div className="px-6 pb-5">
                                <p className="text-sm text-muted-foreground leading-relaxed">{item.answer}</p>
                            </div>
                        )}
                    </div>
                );
            })}
        </div>
    );
}

export default function Pricing() {
    return (
        <>
            <SeoHead
                title="Pricing — ReviewMate"
                description="Start free, upgrade when you're ready. ReviewMate's Starter plan is $49/month — less than the value of one new 5-star review. No contracts, cancel anytime."
            />

            <div className="min-h-screen bg-background text-foreground antialiased">

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
                        <Link href="/features" className="text-sm text-muted-foreground hover:text-foreground transition-colors">Features</Link>
                        <Link href="/login" className="rounded-lg border border-border px-4 py-2 text-sm font-medium text-muted-foreground hover:border-border hover:text-foreground transition-colors">
                            Sign in
                        </Link>
                    </div>
                </nav>

                {/* Hero */}
                <section className="mx-auto max-w-3xl px-6 pt-14 pb-10 text-center">
                    <h1 className="text-4xl font-extrabold tracking-tight sm:text-5xl">
                        Simple, honest pricing
                    </h1>
                    <p className="mt-4 text-lg text-muted-foreground">
                        No setup fees. No lock-in contracts. Start free, upgrade when you need to.
                    </p>
                    <p className="mt-2 text-sm text-muted-foreground/70">
                        14-day free trial on paid plans · No contracts · Cancel anytime
                    </p>
                </section>

                {/* Plans */}
                <section className="mx-auto max-w-6xl px-6 pb-20">
                    <div className="grid gap-6 md:grid-cols-3">
                        {plans.map((plan) => (
                            <div
                                key={plan.name}
                                className={`rounded-2xl p-7 ${plan.highlighted
                                    ? 'relative border-2 border-teal-500 bg-card shadow-xl'
                                    : 'border border-border bg-card'
                                }`}
                            >
                                {plan.highlighted && (
                                    <div className="absolute -top-3.5 left-1/2 -translate-x-1/2">
                                        <span className="animate-pulse rounded-full bg-teal-500 px-3 py-1 text-xs font-bold text-white shadow">Most popular</span>
                                    </div>
                                )}
                                <p className={`mb-1 text-xs font-semibold uppercase tracking-widest ${plan.highlighted ? 'text-teal-600' : 'text-muted-foreground'}`}>
                                    {plan.name}
                                </p>
                                <div className="mb-1 flex items-end gap-1">
                                    <span className="text-4xl font-extrabold text-foreground">{plan.price}</span>
                                    {plan.period && <span className="mb-1 text-sm text-muted-foreground">{plan.period}</span>}
                                </div>
                                <p className="mb-5 text-sm text-muted-foreground">{plan.description}</p>
                                <Link
                                    href="/login"
                                    className={`mb-6 block w-full rounded-xl py-2.5 text-center text-sm font-semibold transition-all duration-200 ${plan.highlighted
                                        ? 'relative overflow-hidden bg-teal-600 text-white hover:bg-teal-700 hover:shadow-lg hover:-translate-y-0.5 before:absolute before:inset-0 before:bg-white/10 before:translate-x-[-100%] hover:before:translate-x-[100%] before:transition-transform before:duration-500 before:skew-x-12'
                                        : 'border border-border text-foreground hover:border-border hover:bg-muted/50 hover:shadow-lg hover:-translate-y-0.5'
                                    }`}
                                >
                                    {plan.cta}
                                </Link>
                                <ul className="space-y-2.5">
                                    {plan.features.map((f) => (
                                        <li key={f} className="flex items-start gap-2 text-sm text-muted-foreground">
                                            <CheckIcon />
                                            {f}
                                        </li>
                                    ))}
                                </ul>
                            </div>
                        ))}
                    </div>
                </section>

                {/* FAQ */}
                <section className="bg-muted/50 py-20">
                    <div className="mx-auto max-w-3xl px-6">
                        <h2 className="mb-10 text-center text-2xl font-bold tracking-tight sm:text-3xl">Frequently asked questions</h2>
                        <FaqAccordion items={faqs} />
                    </div>
                </section>

                {/* CTA */}
                <section className="py-16 text-center">
                    <p className="text-muted-foreground mb-4">Ready to get more reviews on autopilot?</p>
                    <Link
                        href="/login"
                        className="inline-flex items-center gap-2 rounded-xl bg-teal-600 px-8 py-3.5 text-sm font-semibold text-white shadow-md hover:bg-teal-700 transition-all duration-200 hover:shadow-lg hover:-translate-y-0.5"
                    >
                        Start free — no credit card needed
                    </Link>
                </section>

                {/* Footer */}
                <footer className="border-t border-border py-8">
                    <div className="mx-auto max-w-6xl px-6 flex flex-col items-center gap-3 sm:flex-row sm:justify-between">
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
