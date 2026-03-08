import { Head } from '@inertiajs/react';

interface Props {
    customerName: string;
    businessName: string;
}

export default function ReviewConfirmed({ customerName, businessName }: Props) {
    return (
        <>
            <Head title="Thank You!" />
            <div className="flex min-h-screen items-center justify-center bg-background p-4">
                <div className="max-w-md text-center">
                    <div className="mx-auto mb-6 flex h-20 w-20 items-center justify-center rounded-full bg-green-100">
                        <svg className="h-10 w-10 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M5 13l4 4L19 7" />
                        </svg>
                    </div>
                    <h1 className="mb-3 text-3xl font-bold text-foreground">Thanks for sharing that, {customerName}.</h1>
                    <p className="text-lg text-muted-foreground">
                        Your message has been sent privately to the team at{' '}
                        <span className="font-semibold text-foreground">{businessName}</span>.
                    </p>
                    <p className="mt-4 text-sm text-muted-foreground">
                        We appreciate you taking the time to share your experience — it helps us do better.
                    </p>
                    <p className="mt-6 text-sm text-muted-foreground">
                        Had a great experience you'd like to share publicly?{' '}
                        <span className="font-medium text-foreground">Leave us a Google review</span> — it means a lot to a small business.
                    </p>
                </div>
            </div>
        </>
    );
}
