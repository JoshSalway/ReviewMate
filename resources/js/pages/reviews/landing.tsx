import { Head, router } from '@inertiajs/react';
import { useState } from 'react';

interface Props {
    token: string;
    businessName: string;
    googleReviewUrl: string | null;
}

export default function ReviewLanding({ token, businessName, googleReviewUrl }: Props) {
    const [rating, setRating] = useState<number | null>(null);
    const [hovered, setHovered] = useState<number | null>(null);
    const [feedback, setFeedback] = useState('');
    const [submitting, setSubmitting] = useState(false);
    const [submitted, setSubmitted] = useState(false);

    const isHappy = rating !== null && rating >= 4;
    const isUnhappy = rating !== null && rating <= 3;

    const effectiveRating = hovered ?? rating;

    const googleUrl = googleReviewUrl ?? 'https://www.google.com/search?q=' + encodeURIComponent(businessName + ' reviews');

    const handleGoogleClick = () => {
        if (rating === null) return;
        // Record that they chose Google (happy path)
        router.post(
            `/r/${token}/feedback`,
            { rating, feedback: null },
            { preserveScroll: true },
        );
        window.location.href = googleUrl;
    };

    const handleSubmitFeedback = () => {
        if (rating === null) return;
        setSubmitting(true);
        router.post(
            `/r/${token}/feedback`,
            { rating, feedback: feedback.trim() || null },
            {
                preserveScroll: true,
                onSuccess: () => setSubmitted(true),
                onFinish: () => setSubmitting(false),
            },
        );
    };

    if (submitted) {
        return (
            <>
                <Head title="Thank you!" />
                <div className="flex min-h-screen items-center justify-center bg-background p-4">
                    <div className="max-w-md w-full text-center">
                        <div className="mx-auto mb-6 flex h-20 w-20 items-center justify-center rounded-full bg-teal-100">
                            <svg className="h-10 w-10 text-teal-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M5 13l4 4L19 7" />
                            </svg>
                        </div>
                        <h1 className="mb-3 text-2xl font-bold text-foreground">Thank you for your feedback</h1>
                        <p className="text-muted-foreground">
                            We've passed your feedback on to <span className="font-semibold text-foreground">{businessName}</span>. They appreciate you taking the time.
                        </p>
                        {googleUrl && (
                            <p className="mt-6 text-sm text-muted-foreground">
                                Want to share your experience publicly?{' '}
                                <a
                                    href={googleUrl}
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    className="text-teal-600 underline hover:text-teal-700"
                                >
                                    Leave a Google review
                                </a>
                            </p>
                        )}
                    </div>
                </div>
            </>
        );
    }

    return (
        <>
            <Head title={`Rate your experience at ${businessName}`} />
            <div className="flex min-h-screen items-center justify-center bg-background p-4">
                <div className="w-full max-w-md">
                    {/* Card */}
                    <div className="rounded-2xl bg-card p-8 shadow-sm ring-1 ring-border">
                        {/* Logo/brand placeholder */}
                        <div className="mb-6 text-center">
                            <div className="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-full bg-teal-600 text-xl font-bold text-white">
                                {businessName.charAt(0).toUpperCase()}
                            </div>
                            <h1 className="text-xl font-bold text-foreground">{businessName}</h1>
                        </div>

                        {/* Question */}
                        <p className="mb-6 text-center text-lg font-medium text-foreground">
                            How was your experience?
                        </p>

                        {/* Star picker — ALL stars always visible (compliance) */}
                        <div className="mb-8 flex items-center justify-center gap-3">
                            {[1, 2, 3, 4, 5].map((star) => (
                                <button
                                    key={star}
                                    type="button"
                                    onMouseEnter={() => setHovered(star)}
                                    onMouseLeave={() => setHovered(null)}
                                    onClick={() => setRating(star)}
                                    aria-label={`${star} star${star !== 1 ? 's' : ''}`}
                                    className="transition-transform hover:scale-110 focus:outline-none"
                                >
                                    <svg
                                        className={`h-12 w-12 transition-colors ${
                                            effectiveRating !== null && star <= effectiveRating
                                                ? 'text-yellow-400'
                                                : 'text-muted'
                                        }`}
                                        fill="currentColor"
                                        viewBox="0 0 20 20"
                                    >
                                        <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                                    </svg>
                                </button>
                            ))}
                        </div>

                        {/* Happy path (4-5 stars) */}
                        {isHappy && (
                            <div className="space-y-4 text-center">
                                <div>
                                    <p className="mb-1 font-semibold text-foreground">Glad to hear it!</p>
                                    <p className="text-sm text-muted-foreground">
                                        Would you mind sharing your experience on Google? It really helps us.
                                    </p>
                                </div>
                                <a
                                    href={googleUrl}
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    onClick={() =>
                                        router.post(`/r/${token}/feedback`, { rating, feedback: null }, { preserveScroll: true })
                                    }
                                    className="flex w-full items-center justify-center gap-2 rounded-xl bg-teal-600 px-6 py-4 text-base font-semibold text-white shadow-sm transition hover:bg-teal-700"
                                >
                                    <svg className="h-5 w-5" viewBox="0 0 24 24" fill="currentColor">
                                        <path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" fill="#4285F4"/>
                                        <path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853"/>
                                        <path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z" fill="#FBBC05"/>
                                        <path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" fill="#EA4335"/>
                                    </svg>
                                    Leave a Google Review
                                </a>
                                <button
                                    type="button"
                                    onClick={() => setRating(null)}
                                    className="text-sm text-muted-foreground hover:text-foreground underline"
                                >
                                    Change my rating
                                </button>
                            </div>
                        )}

                        {/* Unhappy path (1-3 stars) */}
                        {isUnhappy && (
                            <div className="space-y-4">
                                <div className="text-center">
                                    <p className="mb-1 font-semibold text-foreground">We're sorry to hear that.</p>
                                    <p className="text-sm text-muted-foreground">
                                        Tell us what went wrong — we'd love the chance to make it right.
                                    </p>
                                </div>
                                <textarea
                                    value={feedback}
                                    onChange={(e) => setFeedback(e.target.value)}
                                    placeholder="What could we have done better?"
                                    rows={4}
                                    className="w-full rounded-xl border border-border bg-background p-4 text-sm text-foreground placeholder-muted-foreground focus:border-teal-500 focus:outline-none focus:ring-2 focus:ring-teal-100"
                                />
                                <button
                                    type="button"
                                    onClick={handleSubmitFeedback}
                                    disabled={submitting}
                                    className="w-full rounded-xl bg-teal-600 px-6 py-4 text-base font-semibold text-white shadow-sm transition hover:bg-teal-700 disabled:opacity-60"
                                >
                                    {submitting ? 'Sending...' : 'Send Private Feedback'}
                                </button>

                                {/* Compliance: Google always accessible */}
                                <p className="text-center text-sm text-muted-foreground">
                                    Or if you prefer,{' '}
                                    <a
                                        href={googleUrl}
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        className="text-muted-foreground underline hover:text-foreground"
                                        onClick={() =>
                                            router.post(`/r/${token}/feedback`, { rating, feedback: feedback.trim() || null }, { preserveScroll: true })
                                        }
                                    >
                                        leave a public Google review
                                    </a>
                                </p>

                                <button
                                    type="button"
                                    onClick={() => setRating(null)}
                                    className="w-full text-center text-sm text-muted-foreground hover:text-foreground underline"
                                >
                                    Change my rating
                                </button>
                            </div>
                        )}

                        {/* No rating yet — prompt */}
                        {rating === null && (
                            <p className="text-center text-sm text-muted-foreground">
                                Select a star rating above
                            </p>
                        )}
                    </div>

                    <p className="mt-6 text-center text-xs text-muted-foreground">
                        Powered by ReviewMate
                    </p>
                </div>
            </div>
        </>
    );
}
