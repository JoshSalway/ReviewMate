import { Head, useForm } from '@inertiajs/react';
import type { FormEvent} from 'react';
import { useState } from 'react';

function StarIcon() {
    return (
        <svg className="h-5 w-5 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
        </svg>
    );
}

function CheckIcon() {
    return (
        <svg className="h-5 w-5 text-teal-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={2.5}>
            <path strokeLinecap="round" strokeLinejoin="round" d="M4.5 12.75l6 6 9-13.5" />
        </svg>
    );
}

function XIcon() {
    return (
        <svg className="h-5 w-5 text-red-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={2.5}>
            <path strokeLinecap="round" strokeLinejoin="round" d="M6 18L18 6M6 6l12 12" />
        </svg>
    );
}

const features = [
    {
        icon: (
            <svg className="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={1.5}>
                <path strokeLinecap="round" strokeLinejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75" />
            </svg>
        ),
        title: 'Automated review requests',
        description: 'Send personalised email and SMS review requests after every job or sale — automatically, with smart follow-ups built in.',
    },
    {
        icon: (
            <svg className="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={1.5}>
                <path strokeLinecap="round" strokeLinejoin="round" d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09z" />
            </svg>
        ),
        title: 'AI reply suggestions',
        description: 'Get three AI-written reply options for any Google review in seconds. Pick one, tweak it, and post — without leaving ReviewMate.',
    },
    {
        icon: (
            <svg className="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={1.5}>
                <path strokeLinecap="round" strokeLinejoin="round" d="M11.48 3.499a.562.562 0 011.04 0l2.125 5.111a.563.563 0 00.475.345l5.518.442c.499.04.701.663.321.988l-4.204 3.602a.563.563 0 00-.182.557l1.285 5.385a.562.562 0 01-.84.61l-4.725-2.885a.563.563 0 00-.586 0L6.982 20.54a.562.562 0 01-.84-.61l1.285-5.386a.562.562 0 00-.182-.557l-4.204-3.602a.562.562 0 01.321-.988l5.518-.442a.563.563 0 00.475-.345L11.48 3.5z" />
            </svg>
        ),
        title: 'Review inbox & alerts',
        description: 'All your Google reviews in one place. Instant alerts when new reviews come in so you never miss the chance to respond.',
    },
    {
        icon: (
            <svg className="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={1.5}>
                <path strokeLinecap="round" strokeLinejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z" />
            </svg>
        ),
        title: 'Analytics & insights',
        description: 'Track your review count, average rating, request conversion rate, and pending replies. Multi-location support for growing businesses.',
    },
    {
        icon: (
            <svg className="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={1.5}>
                <path strokeLinecap="round" strokeLinejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z" />
            </svg>
        ),
        title: 'Customer management',
        description: "Import customers from CSV or add them manually. See exactly who's reviewed, who's pending, and who hasn't responded.",
    },
    {
        icon: (
            <svg className="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={1.5}>
                <path strokeLinecap="round" strokeLinejoin="round" d="M3.75 4.875c0-.621.504-1.125 1.125-1.125h4.5c.621 0 1.125.504 1.125 1.125v4.5c0 .621-.504 1.125-1.125 1.125h-4.5A1.125 1.125 0 013.75 9.375v-4.5zM3.75 14.625c0-.621.504-1.125 1.125-1.125h4.5c.621 0 1.125.504 1.125 1.125v4.5c0 .621-.504 1.125-1.125 1.125h-4.5a1.125 1.125 0 01-1.125-1.125v-4.5zM13.5 4.875c0-.621.504-1.125 1.125-1.125h4.5c.621 0 1.125.504 1.125 1.125v4.5c0 .621-.504 1.125-1.125 1.125h-4.5A1.125 1.125 0 0113.5 9.375v-4.5z" />
            </svg>
        ),
        title: 'QR codes & quick send',
        description: 'Generate a QR code for your counter or receipts. Send a one-off review request in under 10 seconds with Quick Send.',
    },
];

const steps = [
    {
        number: '01',
        title: 'Add your customers',
        description: 'Import a CSV or add customers manually. Takes about 2 minutes to get started.',
    },
    {
        number: '02',
        title: 'ReviewMate does the asking',
        description: "Personalised emails and SMS go out at the right time, with a follow-up 5 days later if they haven't reviewed yet.",
    },
    {
        number: '03',
        title: 'Watch your rating climb',
        description: 'Reviews roll in. Reply with AI suggestions in seconds. Your Google ranking improves organically.',
    },
];

const testimonials = [
    {
        quote: "We got 14 new reviews in our first month. I didn't have to do anything.",
        author: 'Sarah K.',
        role: 'Cafe owner',
    },
    {
        quote: "Finally — a tool that asks for reviews without me having to remember.",
        author: 'Dan M.',
        role: 'Electrician',
    },
    {
        quote: 'Our Google rating went from 4.1 to 4.7 in 6 weeks.',
        author: 'Anita R.',
        role: 'Physio clinic',
    },
];

const comparisonRows = [
    { feature: 'Starting price', reviewmate: '$0 free / $49/mo', nicejob: '$75/mo', birdeye: '$299/mo', podium: '$399/mo' },
    { feature: 'AI reply suggestions', reviewmate: true, nicejob: false, birdeye: false, podium: false },
    { feature: 'Google API integration', reviewmate: true, nicejob: true, birdeye: true, podium: true },
    { feature: 'SMS review requests', reviewmate: true, nicejob: true, birdeye: true, podium: true },
    { feature: 'Email review requests', reviewmate: true, nicejob: true, birdeye: true, podium: true },
    { feature: 'Free plan available', reviewmate: true, nicejob: false, birdeye: false, podium: false },
    { feature: 'No lock-in contract', reviewmate: true, nicejob: false, birdeye: false, podium: false },
];

const faqs = [
    {
        question: 'Does it work for SMS and email?',
        answer: 'Yes, both. SMS uses Twilio and works with Australian numbers. Email is sent from your ReviewMate account with personalised content.',
    },
    {
        question: 'Do I need a Google Business Profile?',
        answer: 'Yes — you need a verified Google Business Profile to sync and reply to reviews. ReviewMate helps you connect it in the onboarding wizard (takes about 2 minutes).',
    },
    {
        question: 'Is there a free plan?',
        answer: 'Yes — 1 location, 50 customers, and 10 requests per month. No credit card needed to get started.',
    },
    {
        question: 'Can I cancel any time?',
        answer: 'Yes. No contracts, no lock-in. Cancel from your billing settings at any time and you will not be charged again.',
    },
    {
        question: 'What businesses does it work for?',
        answer: 'Any local business with a Google Business Profile — tradies, cafes, salons, gyms, health clinics, retail, professional services, and more.',
    },
];

function ComparisonCell({ value }: { value: boolean | string }) {
    if (typeof value === 'boolean') {
        return value ? <CheckIcon /> : <XIcon />;
    }
    return <span className="text-sm font-medium text-foreground">{value}</span>;
}

function FaqItem({ question, answer }: { question: string; answer: string }) {
    const [open, setOpen] = useState(false);

    return (
        <div className="border-b border-border py-5">
            <button
                className="flex w-full items-center justify-between text-left"
                onClick={() => setOpen(!open)}
            >
                <span className="text-base font-medium text-foreground">{question}</span>
                <svg
                    className={`h-5 w-5 text-muted-foreground shrink-0 transition-transform ${open ? 'rotate-180' : ''}`}
                    fill="none"
                    viewBox="0 0 24 24"
                    stroke="currentColor"
                    strokeWidth={2}
                >
                    <path strokeLinecap="round" strokeLinejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" />
                </svg>
            </button>
            {open && <p className="mt-3 text-sm text-muted-foreground leading-relaxed">{answer}</p>}
        </div>
    );
}

interface Props {
    count?: number;
}

function HeroWaitlistForm() {
    const { data, setData, post, processing, errors, wasSuccessful } = useForm({
        name: '',
        email: '',
        business_type: '',
    });

    function handleSubmit(e: FormEvent) {
        e.preventDefault();
        post('/waitlist');
    }

    if (wasSuccessful) {
        return (
            <div className="mt-8 inline-flex items-center gap-3 rounded-xl border border-teal-200 bg-teal-50 px-6 py-4 text-sm text-teal-700">
                <svg className="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={2}>
                    <path strokeLinecap="round" strokeLinejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                </svg>
                <span><strong>You're on the list!</strong> We'll email you when your spot is ready.</span>
            </div>
        );
    }

    return (
        <form onSubmit={handleSubmit} className="mt-8 flex flex-col items-center gap-3 sm:flex-row sm:justify-center">
            <div className="w-full sm:w-56">
                <input
                    type="text"
                    placeholder="Your name"
                    value={data.name}
                    onChange={(e) => setData('name', e.target.value)}
                    className="w-full rounded-lg border border-border px-4 py-2.5 text-sm text-foreground placeholder-gray-400 shadow-sm focus:border-teal-400 focus:outline-none focus:ring-2 focus:ring-teal-100"
                />
                {errors.name && <p className="mt-1 text-xs text-red-500">{errors.name}</p>}
            </div>
            <div className="w-full sm:w-64">
                <input
                    type="email"
                    placeholder="Work email"
                    value={data.email}
                    onChange={(e) => setData('email', e.target.value)}
                    className="w-full rounded-lg border border-border px-4 py-2.5 text-sm text-foreground placeholder-gray-400 shadow-sm focus:border-teal-400 focus:outline-none focus:ring-2 focus:ring-teal-100"
                />
                {errors.email && <p className="mt-1 text-xs text-red-500">{errors.email}</p>}
            </div>
            <button
                type="submit"
                disabled={processing}
                className="inline-flex items-center gap-2 rounded-xl bg-teal-600 px-7 py-2.5 text-sm font-semibold text-white shadow-md hover:bg-teal-700 disabled:opacity-60 transition-colors"
            >
                {processing ? 'Joining…' : 'Join the waitlist'}
                {!processing && (
                    <svg className="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={2.5}>
                        <path strokeLinecap="round" strokeLinejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3" />
                    </svg>
                )}
            </button>
        </form>
    );
}

export default function Welcome({ count }: Props) {
    return (
        <>
            <Head title="ReviewMate — More 5-star Google reviews, automatically" />

            <div className="min-h-screen bg-background text-foreground antialiased">

                {/* Nav */}
                <nav className="mx-auto flex max-w-6xl items-center justify-between px-6 py-5">
                    <div className="flex items-center gap-2">
                        <div className="flex h-8 w-8 items-center justify-center rounded-lg bg-teal-600">
                            <svg className="h-4 w-4 text-white" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                            </svg>
                        </div>
                        <span className="text-lg font-bold tracking-tight">ReviewMate</span>
                    </div>
                    <a
                        href="/login"
                        className="rounded-lg border border-border px-4 py-2 text-sm font-medium text-muted-foreground hover:border-border hover:text-foreground transition-colors"
                    >
                        Sign in
                    </a>
                </nav>

                {/* Hero */}
                <section className="mx-auto max-w-6xl px-6 pt-16 pb-20 text-center">
                    <div className="mb-6 inline-flex items-center gap-2 rounded-full border border-amber-100 bg-amber-50 px-4 py-1.5 text-sm font-medium text-amber-700">
                        <svg className="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={2}>
                            <path strokeLinecap="round" strokeLinejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                        </svg>
                        Currently in private beta — join the waitlist
                    </div>

                    <h1 className="mx-auto max-w-3xl text-5xl font-extrabold tracking-tight sm:text-6xl lg:text-7xl leading-[1.05]">
                        More 5-star Google reviews.{' '}
                        <span className="text-teal-600">On autopilot.</span>
                    </h1>

                    <p className="mx-auto mt-6 max-w-xl text-lg text-muted-foreground leading-relaxed">
                        ReviewMate automatically asks your customers for reviews, follows up, and helps you respond with AI — so you spend less time chasing and more time running your business.
                    </p>

                    <div className="mt-6 flex items-center justify-center gap-1.5">
                        {[...Array(5)].map((_, i) => <StarIcon key={i} />)}
                    </div>

                    {count !== undefined && count > 0 && (
                        <p className="mt-2 text-sm text-muted-foreground">
                            {count.toLocaleString()} {count === 1 ? 'business' : 'businesses'} already on the list
                        </p>
                    )}

                    <HeroWaitlistForm />

                    <p className="mt-3 text-xs text-muted-foreground">We'll email you when your spot is ready. No spam, ever.</p>
                </section>

                {/* Social proof / testimonials */}
                <section className="bg-muted/50 py-16">
                    <div className="mx-auto max-w-6xl px-6">
                        <div className="mb-10 text-center">
                            <p className="text-sm font-semibold uppercase tracking-widest text-muted-foreground">What customers say</p>
                        </div>
                        <div className="grid gap-6 md:grid-cols-3">
                            {testimonials.map((t) => (
                                <div key={t.author} className="rounded-2xl border border-border bg-card p-6 shadow-sm">
                                    <div className="mb-4 flex gap-0.5">
                                        {[...Array(5)].map((_, i) => <StarIcon key={i} />)}
                                    </div>
                                    <p className="text-sm text-foreground leading-relaxed">"{t.quote}"</p>
                                    <div className="mt-4">
                                        <p className="text-sm font-semibold text-foreground">{t.author}</p>
                                        <p className="text-xs text-muted-foreground">{t.role}</p>
                                    </div>
                                </div>
                            ))}
                        </div>
                    </div>
                </section>

                {/* How it works */}
                <section className="py-20">
                    <div className="mx-auto max-w-6xl px-6">
                        <div className="mb-14 text-center">
                            <h2 className="text-3xl font-bold tracking-tight sm:text-4xl">How it works</h2>
                            <p className="mt-3 text-muted-foreground">Set up in under 10 minutes. Results in days.</p>
                        </div>
                        <div className="grid gap-10 md:grid-cols-3">
                            {steps.map((step, i) => (
                                <div key={step.number} className="relative">
                                    {i < steps.length - 1 && (
                                        <div className="absolute top-8 left-full hidden w-full border-t-2 border-dashed border-border md:block" style={{ width: 'calc(100% - 2rem)', left: 'calc(100% + 1rem)' }} />
                                    )}
                                    <div className="mb-4 text-6xl font-black text-teal-50 select-none leading-none">{step.number}</div>
                                    <h3 className="mb-2 text-lg font-semibold text-foreground">{step.title}</h3>
                                    <p className="text-sm text-muted-foreground leading-relaxed">{step.description}</p>
                                </div>
                            ))}
                        </div>
                    </div>
                </section>

                {/* Features grid */}
                <section className="bg-muted/50 py-20">
                    <div className="mx-auto max-w-6xl px-6">
                        <div className="mb-14 text-center">
                            <h2 className="text-3xl font-bold tracking-tight sm:text-4xl">Everything you need</h2>
                            <p className="mt-3 text-muted-foreground max-w-lg mx-auto">
                                One tool to get more reviews, manage them, and turn them into a competitive advantage.
                            </p>
                        </div>
                        <div className="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                            {features.map((feature) => (
                                <div key={feature.title} className="group rounded-2xl border border-border bg-card p-6 hover:border-teal-100 hover:shadow-md transition-all">
                                    <div className="mb-4 flex h-11 w-11 items-center justify-center rounded-xl bg-teal-50 text-teal-600 group-hover:bg-teal-100 transition-colors">
                                        {feature.icon}
                                    </div>
                                    <h3 className="mb-2 text-base font-semibold text-foreground">{feature.title}</h3>
                                    <p className="text-sm text-muted-foreground leading-relaxed">{feature.description}</p>
                                </div>
                            ))}
                        </div>
                    </div>
                </section>

                {/* Competitor comparison */}
                <section className="py-20">
                    <div className="mx-auto max-w-5xl px-6">
                        <div className="mb-12 text-center">
                            <h2 className="text-3xl font-bold tracking-tight sm:text-4xl">How we compare</h2>
                            <p className="mt-3 text-muted-foreground">5–10x cheaper than the alternatives, with features they don't have.</p>
                        </div>
                        <div className="overflow-x-auto rounded-2xl border border-border shadow-sm">
                            <table className="w-full text-left">
                                <thead>
                                    <tr className="border-b border-border bg-muted">
                                        <th className="px-6 py-4 text-sm font-semibold text-muted-foreground">Feature</th>
                                        <th className="px-6 py-4 text-sm font-bold text-teal-700">ReviewMate</th>
                                        <th className="px-6 py-4 text-sm font-semibold text-muted-foreground">NiceJob</th>
                                        <th className="px-6 py-4 text-sm font-semibold text-muted-foreground">Birdeye</th>
                                        <th className="px-6 py-4 text-sm font-semibold text-muted-foreground">Podium</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {comparisonRows.map((row, idx) => (
                                        <tr key={row.feature} className={`border-b border-border ${idx % 2 === 0 ? 'bg-card' : 'bg-muted/50'}`}>
                                            <td className="px-6 py-4 text-sm text-muted-foreground">{row.feature}</td>
                                            <td className="px-6 py-4">
                                                <div className="flex items-center">
                                                    <ComparisonCell value={row.reviewmate} />
                                                </div>
                                            </td>
                                            <td className="px-6 py-4">
                                                <div className="flex items-center">
                                                    <ComparisonCell value={row.nicejob} />
                                                </div>
                                            </td>
                                            <td className="px-6 py-4">
                                                <div className="flex items-center">
                                                    <ComparisonCell value={row.birdeye} />
                                                </div>
                                            </td>
                                            <td className="px-6 py-4">
                                                <div className="flex items-center">
                                                    <ComparisonCell value={row.podium} />
                                                </div>
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    </div>
                </section>

                {/* Pricing */}
                <section className="bg-muted/50 py-20">
                    <div className="mx-auto max-w-6xl px-6">
                        <div className="mb-12 text-center">
                            <h2 className="text-3xl font-bold tracking-tight sm:text-4xl">Simple, honest pricing</h2>
                            <p className="mt-3 text-muted-foreground">No setup fees. No lock-in. Cancel any time.</p>
                        </div>
                        <div className="mx-auto grid max-w-4xl gap-6 md:grid-cols-3">
                            <div className="rounded-2xl border border-border bg-card p-7">
                                <p className="mb-1 text-xs font-semibold uppercase tracking-widest text-muted-foreground">Free</p>
                                <p className="mb-5 text-4xl font-extrabold text-foreground">$0</p>
                                <ul className="space-y-2.5 text-sm text-muted-foreground">
                                    {['1 business location', 'Up to 50 customers', '10 requests / month', 'AI reply suggestions', 'Review dashboard'].map((f) => (
                                        <li key={f} className="flex items-center gap-2"><CheckIcon />{f}</li>
                                    ))}
                                </ul>
                            </div>

                            <div className="relative rounded-2xl border-2 border-teal-500 bg-card p-7 shadow-xl">
                                <div className="absolute -top-3.5 left-1/2 -translate-x-1/2">
                                    <span className="rounded-full bg-teal-500 px-3 py-1 text-xs font-bold text-white shadow">Most popular</span>
                                </div>
                                <p className="mb-1 text-xs font-semibold uppercase tracking-widest text-teal-600">Starter</p>
                                <div className="mb-1 flex items-end gap-1">
                                    <span className="text-4xl font-extrabold text-foreground">$49</span>
                                    <span className="mb-1 text-sm text-muted-foreground">/month</span>
                                </div>
                                <p className="mb-5 text-xs text-muted-foreground">per location</p>
                                <ul className="space-y-2.5 text-sm text-muted-foreground">
                                    {['1 business location', 'Unlimited customers', 'Unlimited requests', 'Automated follow-ups', 'Email & SMS sending', 'Weekly digest emails', 'Reply templates'].map((f) => (
                                        <li key={f} className="flex items-center gap-2"><CheckIcon />{f}</li>
                                    ))}
                                </ul>
                            </div>

                            <div className="rounded-2xl border border-border bg-card p-7">
                                <p className="mb-1 text-xs font-semibold uppercase tracking-widest text-muted-foreground">Pro</p>
                                <div className="mb-1 flex items-end gap-1">
                                    <span className="text-4xl font-extrabold text-foreground">$99</span>
                                    <span className="mb-1 text-sm text-muted-foreground">/month</span>
                                </div>
                                <p className="mb-5 text-xs text-muted-foreground">up to 5 locations</p>
                                <ul className="space-y-2.5 text-sm text-muted-foreground">
                                    {['Up to 5 locations', 'Everything in Starter', 'Multi-location analytics', 'Priority support'].map((f) => (
                                        <li key={f} className="flex items-center gap-2"><CheckIcon />{f}</li>
                                    ))}
                                </ul>
                            </div>
                        </div>

                        <div className="mt-10 text-center">
                            <a
                                href="/waitlist"
                                className="inline-flex items-center gap-2 rounded-xl bg-teal-600 px-8 py-3.5 text-sm font-semibold text-white shadow-md hover:bg-teal-700 transition-colors"
                            >
                                Join the waitlist
                            </a>
                            <p className="mt-2 text-xs text-muted-foreground">We'll notify you when your spot is ready.</p>
                        </div>
                    </div>
                </section>

                {/* FAQ */}
                <section className="py-20">
                    <div className="mx-auto max-w-3xl px-6">
                        <div className="mb-12 text-center">
                            <h2 className="text-3xl font-bold tracking-tight sm:text-4xl">Frequently asked questions</h2>
                        </div>
                        <div>
                            {faqs.map((faq) => (
                                <FaqItem key={faq.question} question={faq.question} answer={faq.answer} />
                            ))}
                        </div>
                    </div>
                </section>

                {/* Bottom CTA */}
                <section className="bg-teal-600 py-20">
                    <div className="mx-auto max-w-2xl px-6 text-center">
                        <h2 className="text-3xl font-bold tracking-tight text-white sm:text-4xl">Ready to get more reviews?</h2>
                        <p className="mt-4 text-teal-100 leading-relaxed">
                            Join Australian small businesses waiting to use ReviewMate and build their Google reputation on autopilot.
                        </p>
                        <a
                            href="/waitlist"
                            className="mt-8 inline-flex items-center gap-2 rounded-xl bg-white px-8 py-3.5 text-sm font-semibold text-teal-700 shadow-md hover:bg-teal-50 transition-colors"
                        >
                            Join the waitlist
                            <svg className="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={2.5}>
                                <path strokeLinecap="round" strokeLinejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3" />
                            </svg>
                        </a>
                        <p className="mt-3 text-sm text-teal-100 opacity-80">We'll email you when your spot is ready.</p>
                    </div>
                </section>

                {/* Footer */}
                <footer className="border-t border-border py-8">
                    <div className="mx-auto max-w-6xl px-6 flex flex-col items-center gap-3 sm:flex-row sm:justify-between">
                        <div className="flex items-center gap-2">
                            <div className="flex h-5 w-5 items-center justify-center rounded bg-teal-600">
                                <svg className="h-3 w-3 text-white" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                                </svg>
                            </div>
                            <span className="text-sm font-semibold text-foreground">ReviewMate</span>
                        </div>
                        <div className="flex items-center gap-4 text-xs text-muted-foreground">
                            <a href="/terms" className="hover:text-muted-foreground transition-colors">Terms</a>
                            <a href="/privacy" className="hover:text-muted-foreground transition-colors">Privacy</a>
                            <span>&copy; {new Date().getFullYear()} ReviewMate. Built for Australian small businesses.</span>
                        </div>
                    </div>
                </footer>

            </div>
        </>
    );
}
