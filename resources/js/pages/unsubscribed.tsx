import { Head } from '@inertiajs/react';

interface Props {
    businessName?: string;
    confirmUrl?: string;
}

export default function Unsubscribed({ businessName, confirmUrl }: Props) {
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
                        You won't receive any more review request emails{businessName ? ` from ${businessName}` : ''}.
                    </p>

                    {confirmUrl && (
                        <div className="mt-8 rounded-lg border border-gray-200 bg-white p-6 shadow-sm">
                            <p className="mb-4 text-sm font-medium text-gray-700">
                                Before you go — did you get a chance to leave{businessName ? ` ${businessName}` : ' them'} a review?
                            </p>
                            <div className="flex flex-col gap-3 sm:flex-row sm:justify-center">
                                <a
                                    href={confirmUrl}
                                    className="inline-flex items-center justify-center rounded-md bg-green-600 px-4 py-2 text-sm font-medium text-white hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2"
                                >
                                    ✓ Yes, I already reviewed them
                                </a>
                            </div>
                        </div>
                    )}
                </div>
            </div>
        </>
    );
}
