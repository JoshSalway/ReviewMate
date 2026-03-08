import { Head, useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';

interface Props {
    count: number;
}

const businessTypes = [
    { value: '', label: 'Select your business type' },
    { value: 'cafe', label: 'Cafe / Restaurant' },
    { value: 'tradie', label: 'Tradie (plumber, electrician, builder…)' },
    { value: 'salon', label: 'Hair / Beauty Salon' },
    { value: 'gym', label: 'Gym / Fitness Studio' },
    { value: 'allied_health', label: 'Allied Health (physio, chiro, osteo…)' },
    { value: 'dental', label: 'Dental / Medical Practice' },
    { value: 'retail', label: 'Retail Shop' },
    { value: 'cleaning', label: 'Cleaning Service' },
    { value: 'real_estate', label: 'Real Estate' },
    { value: 'other', label: 'Other' },
];

export default function Waitlist({ count }: Props) {
    const { data, setData, post, processing, errors, wasSuccessful } = useForm({
        name: '',
        email: '',
        business_type: '',
    });

    function handleSubmit(e: FormEvent) {
        e.preventDefault();
        post('/waitlist');
    }

    return (
        <>
            <Head title="Join the Waitlist — ReviewMate" />

            <div className="min-h-screen bg-background text-foreground antialiased">

                {/* Nav */}
                <nav className="mx-auto flex max-w-5xl items-center justify-between px-6 py-5">
                    <a href="/" className="flex items-center gap-2">
                        <div className="flex h-8 w-8 items-center justify-center rounded-lg bg-teal-600">
                            <svg className="h-4 w-4 text-white" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                            </svg>
                        </div>
                        <span className="text-lg font-bold tracking-tight">ReviewMate</span>
                    </a>
                    <a
                        href="/login"
                        className="rounded-lg border border-border px-4 py-2 text-sm font-medium text-muted-foreground hover:border-border hover:text-foreground transition-colors"
                    >
                        Sign in
                    </a>
                </nav>

                {/* Hero */}
                <section className="mx-auto max-w-5xl px-6 pt-16 pb-10 text-center">
                    <div className="mb-5 inline-flex items-center gap-2 rounded-full border border-amber-100 bg-amber-50 px-4 py-1.5 text-sm font-medium text-amber-700">
                        <svg className="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={2}>
                            <path strokeLinecap="round" strokeLinejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                        </svg>
                        Currently in private beta
                    </div>

                    <h1 className="mx-auto max-w-2xl text-4xl font-extrabold tracking-tight sm:text-5xl leading-tight">
                        ReviewMate is launching soon.{' '}
                        <span className="text-teal-600">Get early access.</span>
                    </h1>

                    <p className="mx-auto mt-5 max-w-lg text-lg text-muted-foreground leading-relaxed">
                        Automated review requests, AI reply suggestions, and Google reputation management for Australian small businesses.
                        Join the waitlist and we'll notify you when your spot is ready.
                    </p>

                    {count > 0 && (
                        <p className="mt-3 text-sm text-muted-foreground">
                            {count.toLocaleString()} {count === 1 ? 'business' : 'businesses'} already on the list
                        </p>
                    )}
                </section>

                {/* Form / Success */}
                <section className="mx-auto max-w-md px-6 pb-20">
                    {wasSuccessful ? (
                        <div className="rounded-2xl border border-teal-100 bg-teal-50 px-8 py-10 text-center">
                            <div className="mb-4 flex justify-center">
                                <div className="flex h-14 w-14 items-center justify-center rounded-full bg-teal-100">
                                    <svg className="h-7 w-7 text-teal-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={2}>
                                        <path strokeLinecap="round" strokeLinejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                                    </svg>
                                </div>
                            </div>
                            <h2 className="text-xl font-bold text-foreground">You're on the list!</h2>
                            <p className="mt-2 text-sm text-muted-foreground leading-relaxed">
                                We'll send you an email as soon as your spot is ready. Keep an eye on your inbox.
                            </p>
                        </div>
                    ) : (
                        <form
                            onSubmit={handleSubmit}
                            className="rounded-2xl border border-border bg-card px-8 py-8 shadow-sm"
                        >
                            <h2 className="mb-6 text-xl font-bold text-foreground">Reserve your spot</h2>

                            <div className="space-y-4">
                                <div>
                                    <label htmlFor="name" className="mb-1.5 block text-sm font-medium text-foreground">
                                        Your name
                                    </label>
                                    <input
                                        id="name"
                                        type="text"
                                        autoComplete="name"
                                        value={data.name}
                                        onChange={(e) => setData('name', e.target.value)}
                                        placeholder="Sarah Johnson"
                                        className="w-full rounded-lg border border-border px-3.5 py-2.5 text-sm text-foreground placeholder-gray-400 focus:border-teal-400 focus:outline-none focus:ring-2 focus:ring-teal-100"
                                    />
                                    {errors.name && (
                                        <p className="mt-1 text-xs text-red-500">{errors.name}</p>
                                    )}
                                </div>

                                <div>
                                    <label htmlFor="email" className="mb-1.5 block text-sm font-medium text-foreground">
                                        Work email
                                    </label>
                                    <input
                                        id="email"
                                        type="email"
                                        autoComplete="email"
                                        value={data.email}
                                        onChange={(e) => setData('email', e.target.value)}
                                        placeholder="sarah@mybusiness.com.au"
                                        className="w-full rounded-lg border border-border px-3.5 py-2.5 text-sm text-foreground placeholder-gray-400 focus:border-teal-400 focus:outline-none focus:ring-2 focus:ring-teal-100"
                                    />
                                    {errors.email && (
                                        <p className="mt-1 text-xs text-red-500">{errors.email}</p>
                                    )}
                                </div>

                                <div>
                                    <label htmlFor="business_type" className="mb-1.5 block text-sm font-medium text-foreground">
                                        Business type <span className="text-muted-foreground">(optional)</span>
                                    </label>
                                    <select
                                        id="business_type"
                                        value={data.business_type}
                                        onChange={(e) => setData('business_type', e.target.value)}
                                        className="w-full rounded-lg border border-border px-3.5 py-2.5 text-sm text-foreground focus:border-teal-400 focus:outline-none focus:ring-2 focus:ring-teal-100"
                                    >
                                        {businessTypes.map((bt) => (
                                            <option key={bt.value} value={bt.value}>{bt.label}</option>
                                        ))}
                                    </select>
                                    {errors.business_type && (
                                        <p className="mt-1 text-xs text-red-500">{errors.business_type}</p>
                                    )}
                                </div>
                            </div>

                            <button
                                type="submit"
                                disabled={processing}
                                className="mt-6 w-full rounded-xl bg-teal-600 py-3 text-sm font-semibold text-white shadow-sm hover:bg-teal-700 disabled:opacity-60 transition-colors"
                            >
                                {processing ? 'Joining…' : 'Join the waitlist'}
                            </button>

                            <p className="mt-3 text-center text-xs text-muted-foreground">
                                We'll only email you when your spot is ready. No spam.
                            </p>
                        </form>
                    )}
                </section>

                {/* Mini features */}
                <section className="border-t border-border bg-muted/50 py-16">
                    <div className="mx-auto max-w-5xl px-6">
                        <p className="mb-10 text-center text-sm font-semibold uppercase tracking-widest text-muted-foreground">What you'll get</p>
                        <div className="grid gap-6 sm:grid-cols-3">
                            {[
                                {
                                    title: 'Automated review requests',
                                    desc: 'Email and SMS requests go out automatically after every job. Follow-ups included.',
                                },
                                {
                                    title: 'AI reply suggestions',
                                    desc: 'Reply to any Google review in seconds with three AI-written options.',
                                },
                                {
                                    title: 'Review inbox & analytics',
                                    desc: 'All your reviews in one place. Track your rating, reply rate, and request conversions.',
                                },
                            ].map((f) => (
                                <div key={f.title} className="rounded-xl border border-border bg-card p-5">
                                    <div className="mb-2 h-2 w-8 rounded-full bg-teal-500" />
                                    <h3 className="mb-1 text-sm font-semibold text-foreground">{f.title}</h3>
                                    <p className="text-sm text-muted-foreground leading-relaxed">{f.desc}</p>
                                </div>
                            ))}
                        </div>
                    </div>
                </section>

                {/* Footer */}
                <footer className="border-t border-border py-8">
                    <div className="mx-auto max-w-5xl px-6 flex flex-col items-center gap-3 sm:flex-row sm:justify-between">
                        <span className="text-sm font-semibold text-foreground">ReviewMate</span>
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
