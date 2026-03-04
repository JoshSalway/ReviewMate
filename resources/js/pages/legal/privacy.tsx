import { Head, Link } from '@inertiajs/react';

function Logo() {
    return (
        <div className="flex items-center gap-2">
            <div className="flex h-8 w-8 items-center justify-center rounded-lg bg-teal-600">
                <svg className="h-4 w-4 text-white" fill="currentColor" viewBox="0 0 20 20">
                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                </svg>
            </div>
            <span className="text-lg font-bold tracking-tight">ReviewMate</span>
        </div>
    );
}

export default function Privacy() {
    return (
        <>
            <Head title="Privacy Policy — ReviewMate" />
            <div className="min-h-screen bg-white text-gray-900 antialiased">

                {/* Nav */}
                <nav className="mx-auto flex max-w-4xl items-center justify-between px-6 py-5">
                    <Link href="/">
                        <Logo />
                    </Link>
                    <Link
                        href="/terms"
                        className="text-sm text-gray-500 hover:text-gray-900 transition-colors"
                    >
                        Terms of Service
                    </Link>
                </nav>

                {/* Content */}
                <main className="mx-auto max-w-3xl px-6 pb-24 pt-10">
                    <div className="mb-10 border-b border-gray-100 pb-8">
                        <h1 className="text-4xl font-extrabold tracking-tight text-gray-900">Privacy Policy</h1>
                        <p className="mt-3 text-sm text-gray-400">Last updated: 4 March 2026</p>
                    </div>

                    <div className="prose prose-gray max-w-none space-y-10 text-[15px] leading-relaxed text-gray-700">

                        <section>
                            <p>
                                This Privacy Policy explains how ReviewMate ("we", "our", "us") collects, uses, stores, and shares information when you use our platform at{' '}
                                <a href="https://reviewmate.com.au" className="text-teal-600 underline">reviewmate.com.au</a>. We are committed to protecting your privacy and handling your data in accordance with the <strong>Privacy Act 1988 (Cth)</strong> and the Australian Privacy Principles (APPs).
                            </p>
                        </section>

                        <section>
                            <h2 className="mb-3 text-xl font-bold text-gray-900">1. Information We Collect</h2>

                            <h3 className="mb-2 mt-5 font-semibold text-gray-900">Account information</h3>
                            <p>
                                When you sign up, we collect your name and email address via <strong>WorkOS</strong> (our authentication provider). We also collect your Google account details when you connect your Google Business Profile via OAuth.
                            </p>

                            <h3 className="mb-2 mt-5 font-semibold text-gray-900">Business information</h3>
                            <p>
                                We collect the name, type, location, and owner details of the business(es) you register with ReviewMate. We also store your Google Business Profile account and location identifiers and your Google Place ID.
                            </p>

                            <h3 className="mb-2 mt-5 font-semibold text-gray-900">Customer data you upload</h3>
                            <p>
                                To send review requests, you provide us with your customers' names, email addresses, and/or phone numbers. You are responsible for the lawfulness of uploading this data. We process it only to deliver the service on your behalf.
                            </p>

                            <h3 className="mb-2 mt-5 font-semibold text-gray-900">Review data</h3>
                            <p>
                                We sync your Google reviews from the Google Business Profile API and store review content, ratings, reviewer names, and any replies you post through ReviewMate.
                            </p>

                            <h3 className="mb-2 mt-5 font-semibold text-gray-900">Usage data</h3>
                            <p>
                                We collect standard server logs including IP addresses, browser type, pages visited, and timestamps. This helps us diagnose issues and understand how the product is used.
                            </p>

                            <h3 className="mb-2 mt-5 font-semibold text-gray-900">Payment data</h3>
                            <p>
                                Payments are processed by <strong>Stripe</strong>. We do not store your full card number, CVV, or bank account details. Stripe provides us with a token and basic billing information (last 4 digits, card type, billing email) for subscription management.
                            </p>
                        </section>

                        <section>
                            <h2 className="mb-3 text-xl font-bold text-gray-900">2. How We Use Your Information</h2>
                            <ul className="list-disc space-y-2 pl-5">
                                <li>To provide, operate, and improve the ReviewMate platform.</li>
                                <li>To send review request emails and SMS messages to your customers on your behalf.</li>
                                <li>To sync reviews from your Google Business Profile and enable you to reply to them.</li>
                                <li>To generate AI reply suggestions using Anthropic's Claude API — review text is sent to Anthropic's servers for this purpose.</li>
                                <li>To process subscription payments and manage your billing via Stripe.</li>
                                <li>To send you transactional emails (weekly digests, review alerts, billing notifications) relevant to your account.</li>
                                <li>To comply with legal obligations and enforce our Terms of Service.</li>
                            </ul>
                            <p className="mt-3">
                                We do not sell your personal information or your customers' personal information to third parties. We do not use your data for advertising purposes.
                            </p>
                        </section>

                        <section>
                            <h2 className="mb-3 text-xl font-bold text-gray-900">3. Third-Party Service Providers</h2>
                            <p className="mb-4">
                                We share data with the following sub-processors to deliver ReviewMate's features. Each provider has been chosen for its security and compliance standards.
                            </p>

                            <div className="overflow-hidden rounded-xl border border-gray-200">
                                <table className="w-full text-sm">
                                    <thead>
                                        <tr className="bg-gray-50 text-left">
                                            <th className="px-4 py-3 font-semibold text-gray-900">Provider</th>
                                            <th className="px-4 py-3 font-semibold text-gray-900">Purpose</th>
                                            <th className="px-4 py-3 font-semibold text-gray-900">Data shared</th>
                                        </tr>
                                    </thead>
                                    <tbody className="divide-y divide-gray-100">
                                        <tr>
                                            <td className="px-4 py-3 font-medium">WorkOS</td>
                                            <td className="px-4 py-3 text-gray-600">Authentication</td>
                                            <td className="px-4 py-3 text-gray-600">Name, email</td>
                                        </tr>
                                        <tr className="bg-gray-50/50">
                                            <td className="px-4 py-3 font-medium">Google</td>
                                            <td className="px-4 py-3 text-gray-600">Business Profile API, OAuth</td>
                                            <td className="px-4 py-3 text-gray-600">Google account tokens, location data, reviews</td>
                                        </tr>
                                        <tr>
                                            <td className="px-4 py-3 font-medium">Stripe</td>
                                            <td className="px-4 py-3 text-gray-600">Payments & billing</td>
                                            <td className="px-4 py-3 text-gray-600">Name, email, billing details</td>
                                        </tr>
                                        <tr className="bg-gray-50/50">
                                            <td className="px-4 py-3 font-medium">Twilio</td>
                                            <td className="px-4 py-3 text-gray-600">SMS delivery</td>
                                            <td className="px-4 py-3 text-gray-600">Customer phone numbers, message content</td>
                                        </tr>
                                        <tr>
                                            <td className="px-4 py-3 font-medium">Anthropic</td>
                                            <td className="px-4 py-3 text-gray-600">AI reply suggestions</td>
                                            <td className="px-4 py-3 text-gray-600">Review text, business name and type</td>
                                        </tr>
                                        <tr className="bg-gray-50/50">
                                            <td className="px-4 py-3 font-medium">Mailgun / Resend</td>
                                            <td className="px-4 py-3 text-gray-600">Transactional email</td>
                                            <td className="px-4 py-3 text-gray-600">Customer names, emails, message content</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>

                            <p className="mt-4 text-sm text-gray-500">
                                Some of these providers are located outside Australia (United States). Where data is transferred internationally, we take reasonable steps to ensure appropriate protections are in place consistent with the APPs.
                            </p>
                        </section>

                        <section>
                            <h2 className="mb-3 text-xl font-bold text-gray-900">4. Data Retention</h2>
                            <ul className="list-disc space-y-2 pl-5">
                                <li>Your account and business data is retained while your account is active.</li>
                                <li>After account deletion, data is retained for 30 days before permanent deletion to allow you to recover or export.</li>
                                <li>Billing records may be retained for up to 7 years to meet Australian tax and accounting obligations.</li>
                                <li>Anonymised, aggregated analytics data (no personal information) may be retained indefinitely.</li>
                            </ul>
                        </section>

                        <section>
                            <h2 className="mb-3 text-xl font-bold text-gray-900">5. Cookies</h2>
                            <p>
                                ReviewMate uses strictly necessary cookies to maintain your login session and protect against CSRF attacks. We do not use tracking cookies or third-party advertising cookies.
                            </p>
                        </section>

                        <section>
                            <h2 className="mb-3 text-xl font-bold text-gray-900">6. Security</h2>
                            <p>
                                We implement industry-standard security measures including HTTPS-only access, encrypted storage of sensitive credentials (Google OAuth tokens), hashed passwords, and regular dependency updates. No method of transmission over the internet is 100% secure; we cannot guarantee absolute security, but we take the protection of your data seriously.
                            </p>
                        </section>

                        <section>
                            <h2 className="mb-3 text-xl font-bold text-gray-900">7. Your Rights</h2>
                            <p className="mb-3">
                                Under the Australian Privacy Principles you have the right to:
                            </p>
                            <ul className="list-disc space-y-2 pl-5">
                                <li><strong>Access</strong> the personal information we hold about you.</li>
                                <li><strong>Correct</strong> inaccurate or out-of-date personal information.</li>
                                <li><strong>Delete</strong> your account and associated personal data (subject to retention obligations above).</li>
                                <li><strong>Complain</strong> to the Office of the Australian Information Commissioner (OAIC) at <a href="https://www.oaic.gov.au" target="_blank" rel="noopener noreferrer" className="text-teal-600 underline">oaic.gov.au</a> if you believe we have mishandled your information.</li>
                            </ul>
                            <p className="mt-3">
                                To exercise any of these rights, contact us at <a href="mailto:hello@reviewmate.com.au" className="text-teal-600 underline">hello@reviewmate.com.au</a>. We will respond within 30 days.
                            </p>
                        </section>

                        <section>
                            <h2 className="mb-3 text-xl font-bold text-gray-900">8. Email and SMS Communications</h2>
                            <p>
                                We send transactional emails to your registered email address (review alerts, billing confirmations, weekly digests). You can manage your notification preferences in the app under Settings → Notifications. Every review request email sent to your customers includes an unsubscribe link in compliance with the <strong>Spam Act 2003 (Cth)</strong>.
                            </p>
                        </section>

                        <section>
                            <h2 className="mb-3 text-xl font-bold text-gray-900">9. Children's Privacy</h2>
                            <p>
                                ReviewMate is not directed at children under 18. We do not knowingly collect personal information from children. If you believe a child has provided us with personal information, contact us and we will delete it promptly.
                            </p>
                        </section>

                        <section>
                            <h2 className="mb-3 text-xl font-bold text-gray-900">10. Changes to This Policy</h2>
                            <p>
                                We may update this Privacy Policy from time to time. We will notify you of material changes by email. The "Last updated" date at the top of this page reflects the most recent revision. Continued use of ReviewMate after changes take effect constitutes acceptance of the updated policy.
                            </p>
                        </section>

                        <section>
                            <h2 className="mb-3 text-xl font-bold text-gray-900">11. Contact Us</h2>
                            <p>
                                For privacy-related enquiries, complaints, or to exercise your rights, please contact our privacy team at:
                            </p>
                            <div className="mt-3 rounded-xl border border-gray-200 bg-gray-50 p-5 text-sm">
                                <p className="font-semibold text-gray-900">ReviewMate</p>
                                <p className="mt-1 text-gray-600">
                                    Email:{' '}
                                    <a href="mailto:hello@reviewmate.com.au" className="text-teal-600 underline">
                                        hello@reviewmate.com.au
                                    </a>
                                </p>
                                <p className="text-gray-600">Australia</p>
                            </div>
                        </section>

                    </div>
                </main>

                {/* Footer */}
                <footer className="border-t border-gray-100 py-8">
                    <div className="mx-auto max-w-6xl px-6 flex flex-col items-center gap-3 sm:flex-row sm:justify-between">
                        <div className="flex items-center gap-2">
                            <div className="flex h-5 w-5 items-center justify-center rounded bg-teal-600">
                                <svg className="h-3 w-3 text-white" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                                </svg>
                            </div>
                            <span className="text-sm font-semibold text-gray-700">ReviewMate</span>
                        </div>
                        <div className="flex items-center gap-4 text-xs text-gray-400">
                            <Link href="/terms" className="hover:text-gray-600 transition-colors">Terms</Link>
                            <Link href="/privacy" className="hover:text-gray-600 transition-colors font-medium text-gray-600">Privacy</Link>
                            <span>&copy; {new Date().getFullYear()} ReviewMate</span>
                        </div>
                    </div>
                </footer>

            </div>
        </>
    );
}
