import { Head } from '@inertiajs/react';

export default function Login() {
    return (
        <>
            <Head title="Sign in — ReviewMate" />
            <div className="flex min-h-screen flex-col items-center justify-center bg-gray-50 px-4">
                <div className="w-full max-w-sm">
                    {/* Logo */}
                    <div className="mb-8 flex flex-col items-center gap-3 text-center">
                        <div className="flex h-12 w-12 items-center justify-center rounded-xl bg-teal-600 shadow-sm">
                            <svg className="h-6 w-6 text-white" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                            </svg>
                        </div>
                        <div>
                            <h1 className="text-2xl font-bold text-gray-900">ReviewMate</h1>
                            <p className="mt-1 text-sm text-gray-500">Sign in to your account</p>
                        </div>
                    </div>

                    {/* Card */}
                    <div className="rounded-xl bg-white p-8 shadow-sm ring-1 ring-gray-100">
                        <a
                            href="/auth/redirect"
                            className="flex w-full items-center justify-center gap-2 rounded-lg bg-teal-600 px-4 py-3 text-sm font-semibold text-white shadow-sm transition-colors hover:bg-teal-700"
                        >
                            Continue with WorkOS
                            <svg className="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={2}>
                                <path strokeLinecap="round" strokeLinejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3" />
                            </svg>
                        </a>

                        <p className="mt-5 text-center text-xs text-gray-400 leading-relaxed">
                            By signing in or creating an account you agree to our{' '}
                            <a href="/terms" className="text-teal-600 underline hover:text-teal-700">
                                Terms of Service
                            </a>{' '}
                            and{' '}
                            <a href="/privacy" className="text-teal-600 underline hover:text-teal-700">
                                Privacy Policy
                            </a>
                            .
                        </p>
                    </div>

                    <p className="mt-6 text-center text-xs text-gray-400">
                        <a href="/" className="hover:text-gray-600 transition-colors">
                            &larr; Back to ReviewMate.com.au
                        </a>
                    </p>
                </div>
            </div>
        </>
    );
}
