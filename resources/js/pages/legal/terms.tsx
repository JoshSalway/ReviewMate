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

export default function Terms() {
    return (
        <>
            <Head title="Terms of Service — ReviewMate" />
            <div className="min-h-screen bg-white text-gray-900 antialiased">

                {/* Nav */}
                <nav className="mx-auto flex max-w-4xl items-center justify-between px-6 py-5">
                    <Link href="/">
                        <Logo />
                    </Link>
                    <Link
                        href="/privacy"
                        className="text-sm text-gray-500 hover:text-gray-900 transition-colors"
                    >
                        Privacy Policy
                    </Link>
                </nav>

                {/* Content */}
                <main className="mx-auto max-w-3xl px-6 pb-24 pt-10">
                    <div className="mb-10 border-b border-gray-100 pb-8">
                        <h1 className="text-4xl font-extrabold tracking-tight text-gray-900">Terms of Service</h1>
                        <p className="mt-3 text-sm text-gray-400">Last updated: 4 March 2026</p>
                    </div>

                    <div className="prose prose-gray max-w-none space-y-10 text-[15px] leading-relaxed text-gray-700">

                        <section>
                            <p>
                                These Terms of Service ("Terms") govern your access to and use of ReviewMate ("we", "our", "us"), a Google review management platform operated by ReviewMate. By creating an account or using our service you agree to be bound by these Terms. If you do not agree, do not use ReviewMate.
                            </p>
                        </section>

                        <section>
                            <h2 className="mb-3 text-xl font-bold text-gray-900">1. The Service</h2>
                            <p>
                                ReviewMate is a software-as-a-service (SaaS) platform that helps Australian small businesses collect and manage Google reviews. Core features include automated review request emails and SMS, Google Business Profile integration, AI-generated reply suggestions, and customer management tools.
                            </p>
                        </section>

                        <section>
                            <h2 className="mb-3 text-xl font-bold text-gray-900">2. Eligibility</h2>
                            <p>
                                You must be at least 18 years old and operate a legitimate business to use ReviewMate. By creating an account you represent that you have the legal authority to bind yourself (and, if applicable, your organisation) to these Terms.
                            </p>
                        </section>

                        <section>
                            <h2 className="mb-3 text-xl font-bold text-gray-900">3. Accounts</h2>
                            <p>
                                You are responsible for maintaining the confidentiality of your account credentials and for all activity that occurs under your account. Notify us immediately at <a href="mailto:hello@reviewmate.com.au" className="text-teal-600 underline">hello@reviewmate.com.au</a> if you suspect unauthorised access. We are not liable for loss or damage arising from your failure to protect your credentials.
                            </p>
                        </section>

                        <section>
                            <h2 className="mb-3 text-xl font-bold text-gray-900">4. Subscriptions and Billing</h2>
                            <p className="mb-3">
                                ReviewMate offers a free tier and paid plans billed on a monthly recurring basis. Billing is processed by <strong>Stripe</strong>, a third-party payment processor. By subscribing you also agree to <a href="https://stripe.com/au/legal/ssa" target="_blank" rel="noopener noreferrer" className="text-teal-600 underline">Stripe's terms of service</a>. We do not store your full card details on our servers.
                            </p>
                            <ul className="list-disc space-y-1.5 pl-5">
                                <li>Subscription fees are charged in advance at the start of each billing period.</li>
                                <li>All prices are in Australian dollars (AUD) and include GST where applicable.</li>
                                <li>You may cancel your subscription at any time. Your access continues until the end of the current billing period — no refunds are issued for partial periods.</li>
                                <li>We reserve the right to change pricing with 30 days' notice to your registered email address.</li>
                                <li>Downgrading to the free plan may result in the loss of features or data that exceed free-plan limits.</li>
                            </ul>
                        </section>

                        <section>
                            <h2 className="mb-3 text-xl font-bold text-gray-900">5. Acceptable Use</h2>
                            <p className="mb-3">You agree to use ReviewMate only for lawful purposes and in accordance with these Terms. You must not:</p>
                            <ul className="list-disc space-y-1.5 pl-5">
                                <li>Send review requests to individuals who have not had a genuine transaction or interaction with your business.</li>
                                <li>Send review requests to individuals who have unsubscribed from your communications.</li>
                                <li>Offer incentives (gifts, discounts, cash) in exchange for Google reviews — this violates Google's review policies.</li>
                                <li>Use ReviewMate to send unsolicited bulk commercial messages (spam).</li>
                                <li>Impersonate any person or entity or misrepresent your affiliation with a business.</li>
                                <li>Attempt to gain unauthorised access to ReviewMate systems or another user's account.</li>
                                <li>Use ReviewMate for any purpose that is illegal under applicable Australian law.</li>
                            </ul>
                            <p className="mt-3">
                                You are solely responsible for ensuring your use of review request features complies with the <strong>Spam Act 2003 (Cth)</strong>, the <strong>Do Not Call Register Act 2006 (Cth)</strong>, and any other applicable Australian communications laws. This includes obtaining appropriate consent before sending marketing messages.
                            </p>
                        </section>

                        <section>
                            <h2 className="mb-3 text-xl font-bold text-gray-900">6. Customer Data</h2>
                            <p>
                                When you upload customer information (names, email addresses, phone numbers) to ReviewMate, you represent and warrant that you have a lawful basis to share that data with us and to contact those individuals. You retain ownership of your customer data. We process it solely to provide the service as described in our <Link href="/privacy" className="text-teal-600 underline">Privacy Policy</Link>.
                            </p>
                        </section>

                        <section>
                            <h2 className="mb-3 text-xl font-bold text-gray-900">7. Third-Party Services</h2>
                            <p className="mb-3">
                                ReviewMate integrates with the following third-party services to deliver its features. Your use of ReviewMate is also subject to these providers' terms:
                            </p>
                            <ul className="list-disc space-y-1.5 pl-5">
                                <li><strong>Google</strong> — Google Business Profile API and Google OAuth. Subject to Google's Terms of Service and API policies.</li>
                                <li><strong>Stripe</strong> — Payment processing. Subject to Stripe's services agreement.</li>
                                <li><strong>WorkOS</strong> — Authentication. Subject to WorkOS's terms of service.</li>
                                <li><strong>Twilio</strong> — SMS delivery. Subject to Twilio's terms of service and Acceptable Use Policy.</li>
                                <li><strong>Anthropic</strong> — AI-generated reply suggestions via the Claude API. AI suggestions are provided as-is and should be reviewed before use.</li>
                                <li><strong>Mailgun / Resend</strong> — Transactional email delivery.</li>
                            </ul>
                            <p className="mt-3">
                                We are not responsible for the availability, accuracy, or conduct of these third-party services.
                            </p>
                        </section>

                        <section>
                            <h2 className="mb-3 text-xl font-bold text-gray-900">8. AI-Generated Content</h2>
                            <p>
                                ReviewMate uses Anthropic's Claude AI to generate suggested replies to Google reviews. These suggestions are provided as a starting point only. You are responsible for reviewing, editing, and approving any reply before it is posted to Google. We make no warranty as to the accuracy, appropriateness, or completeness of AI-generated content.
                            </p>
                        </section>

                        <section>
                            <h2 className="mb-3 text-xl font-bold text-gray-900">9. Intellectual Property</h2>
                            <p>
                                ReviewMate and its original content, features, and functionality are owned by us and protected by applicable intellectual property laws. You may not copy, modify, distribute, or reverse-engineer any part of the platform. You retain all rights to your own content (customer data, business information, custom templates) uploaded to ReviewMate.
                            </p>
                        </section>

                        <section>
                            <h2 className="mb-3 text-xl font-bold text-gray-900">10. Disclaimer of Warranties</h2>
                            <p>
                                ReviewMate is provided on an "as is" and "as available" basis without any warranty of any kind, express or implied, including warranties of merchantability, fitness for a particular purpose, or non-infringement. We do not warrant that the service will be uninterrupted, error-free, or that results obtained from its use will be accurate or reliable.
                            </p>
                        </section>

                        <section>
                            <h2 className="mb-3 text-xl font-bold text-gray-900">11. Limitation of Liability</h2>
                            <p>
                                To the maximum extent permitted by Australian law, ReviewMate and its officers, directors, employees, and contractors will not be liable for any indirect, incidental, special, consequential, or punitive damages — including loss of profits, data, goodwill, or business — arising from your use of or inability to use the service.
                            </p>
                            <p className="mt-3">
                                Our total liability to you for any claim arising under these Terms will not exceed the amount you paid to us in the 12 months preceding the claim.
                            </p>
                            <p className="mt-3">
                                Nothing in these Terms excludes, restricts, or modifies any right or remedy you may have under the <strong>Australian Consumer Law</strong> (Schedule 2 of the <em>Competition and Consumer Act 2010</em> (Cth)) that cannot be excluded by agreement.
                            </p>
                        </section>

                        <section>
                            <h2 className="mb-3 text-xl font-bold text-gray-900">12. Termination</h2>
                            <p>
                                You may close your account at any time by contacting us at <a href="mailto:hello@reviewmate.com.au" className="text-teal-600 underline">hello@reviewmate.com.au</a>. We may suspend or terminate your account immediately if you breach these Terms or if we reasonably believe you are using ReviewMate in a harmful or illegal manner. Upon termination, your data will be retained for 30 days before permanent deletion, giving you time to export what you need.
                            </p>
                        </section>

                        <section>
                            <h2 className="mb-3 text-xl font-bold text-gray-900">13. Changes to These Terms</h2>
                            <p>
                                We may update these Terms from time to time. We will notify you of material changes by email to your registered address at least 14 days before the changes take effect. Your continued use of ReviewMate after that date constitutes acceptance of the updated Terms.
                            </p>
                        </section>

                        <section>
                            <h2 className="mb-3 text-xl font-bold text-gray-900">14. Governing Law</h2>
                            <p>
                                These Terms are governed by the laws of Queensland, Australia. Any disputes arising under these Terms will be subject to the exclusive jurisdiction of the courts of Queensland, Australia.
                            </p>
                        </section>

                        <section>
                            <h2 className="mb-3 text-xl font-bold text-gray-900">15. Contact</h2>
                            <p>
                                Questions about these Terms? Email us at{' '}
                                <a href="mailto:hello@reviewmate.com.au" className="text-teal-600 underline">
                                    hello@reviewmate.com.au
                                </a>
                                .
                            </p>
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
                            <Link href="/terms" className="hover:text-gray-600 transition-colors font-medium text-gray-600">Terms</Link>
                            <Link href="/privacy" className="hover:text-gray-600 transition-colors">Privacy</Link>
                            <span>&copy; {new Date().getFullYear()} ReviewMate</span>
                        </div>
                    </div>
                </footer>

            </div>
        </>
    );
}
