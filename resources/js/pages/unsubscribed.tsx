import { Head } from '@inertiajs/react';

export default function Unsubscribed() {
    return (
        <>
            <Head title="Unsubscribed" />
            <div className="flex min-h-screen items-center justify-center bg-gray-50 p-4">
                <div className="max-w-md text-center">
                    <div className="mx-auto mb-6 flex h-16 w-16 items-center justify-center rounded-full bg-green-100">
                        <svg className="h-8 w-8 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M5 13l4 4L19 7" />
                        </svg>
                    </div>
                    <h1 className="mb-2 text-2xl font-bold text-gray-900">You're unsubscribed</h1>
                    <p className="text-gray-500">
                        You won't receive any more review request emails from this business.
                    </p>
                </div>
            </div>
        </>
    );
}
