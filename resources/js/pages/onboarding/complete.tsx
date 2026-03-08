import { Head, router } from '@inertiajs/react';
import { Button } from '@/components/ui/button';

interface Props {
    business: {
        name: string;
        type: string;
    };
}

export default function OnboardingComplete({ business }: Props) {
    return (
        <>
            <Head title="You're all set! - ReviewMate" />
            <div className="flex min-h-screen items-center justify-center bg-background p-4">
                <div className="w-full max-w-lg">
                    {/* Celebration header */}
                    <div className="mb-8 text-center">
                        <div className="mb-4 text-5xl">🎉</div>
                        <h1 className="text-3xl font-bold text-foreground">You're all set!</h1>
                        <p className="mt-2 text-lg text-muted-foreground">
                            {business.name} is ready to collect Google reviews automatically.
                        </p>
                    </div>

                    {/* What happens next */}
                    <div className="mb-8 rounded-xl bg-card p-6 shadow-sm ring-1 ring-border">
                        <h2 className="mb-5 text-center text-sm font-semibold uppercase tracking-wide text-muted-foreground">
                            Here's how it works
                        </h2>
                        <div className="space-y-5">
                            <div className="flex gap-4">
                                <div className="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-teal-100 text-lg">
                                    👥
                                </div>
                                <div>
                                    <p className="font-semibold text-foreground">Add your happy customers</p>
                                    <p className="mt-0.5 text-sm text-muted-foreground">
                                        Add a few customers you know were happy — takes 2 minutes. Or import your whole list at once.
                                    </p>
                                </div>
                            </div>
                            <div className="flex gap-4">
                                <div className="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-teal-100 text-lg">
                                    ✉️
                                </div>
                                <div>
                                    <p className="font-semibold text-foreground">We send the request — you focus on the job</p>
                                    <p className="mt-0.5 text-sm text-muted-foreground">
                                        ReviewMate sends a friendly, personalised email asking for a review. No chasing, no awkward conversations.
                                    </p>
                                </div>
                            </div>
                            <div className="flex gap-4">
                                <div className="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-teal-100 text-lg">
                                    ⭐
                                </div>
                                <div>
                                    <p className="font-semibold text-foreground">Reviews land on Google while you sleep</p>
                                    <p className="mt-0.5 text-sm text-muted-foreground">
                                        Most businesses get their first new review within 48 hours. We'll notify you the moment it arrives.
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    {/* CTAs */}
                    <div className="space-y-3">
                        <Button
                            className="w-full bg-teal-600 hover:bg-teal-700 text-white text-base py-6"
                            onClick={() => router.visit('/customers')}
                        >
                            Add my first customers →
                        </Button>
                        <button
                            type="button"
                            className="w-full text-center text-sm text-muted-foreground hover:text-foreground"
                            onClick={() => router.visit('/quick-send')}
                        >
                            Or send a test request to myself first
                        </button>
                    </div>

                    <p className="mt-6 text-center text-xs text-muted-foreground">
                        You can always come back to this later from the dashboard.
                    </p>
                </div>
            </div>
        </>
    );
}
