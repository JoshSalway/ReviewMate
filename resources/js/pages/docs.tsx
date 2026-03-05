import { Head, Link } from '@inertiajs/react';
import { useState } from 'react';

const sections = [
    {
        id: 'getting-started',
        title: 'Getting started',
        articles: [
            {
                title: 'Setting up your account',
                content: `After signing up, ReviewMate will walk you through a 3-step onboarding wizard:

1. **Choose your business type** — this selects the right email template for your industry (tradie, cafe, salon, allied health, or general).

2. **Connect Google Business Profile** (optional but recommended) — click "Connect Google" and authorise ReviewMate to sync your reviews. You will need a verified Google Business Profile.

3. **Select your email template** — pick from the pre-built templates or customise your own. You can edit it further from Settings → Templates.

After onboarding, you are ready to start sending review requests.`,
            },
            {
                title: 'Adding your first customers',
                content: `You have three ways to add customers:

**CSV Import (fastest for bulk)**
Go to Customers → Import CSV. Upload a spreadsheet with at minimum a Name and Email column. ReviewMate will try to auto-detect the column names. Required: name and either email or phone.

**Manual entry**
Go to Customers → Add Customer. Fill in name, email, phone (optional), and notes (optional).

**Quick Send (no customer record needed)**
Go to Quick Send → enter a name and email address → send. Good for one-off requests where you do not want to save the customer.`,
            },
            {
                title: 'Sending review requests',
                content: `**Bulk send**
Go to Customers, select multiple customers using the checkboxes, and click "Send review request". Choose email, SMS, or both channels.

**Quick Send**
Go to Quick Send. Enter a customer's name and email or phone. Sends immediately.

**Automated (via integrations)**
If you have connected ServiceM8, Xero, Cliniko, or another integration, review requests will be sent automatically when a job is completed or invoice is paid.

ReviewMate includes a 30-day duplicate guard — if you have already sent a request to a customer in the last 30 days, it will not send again.`,
            },
        ],
    },
    {
        id: 'google',
        title: 'Google Business Profile',
        articles: [
            {
                title: 'Connecting your Google Business Profile',
                content: `To sync reviews and reply via ReviewMate, you need to connect your Google Business Profile:

1. Go to Settings → Business (or click "Connect Google" in the dashboard)
2. Click "Connect Google Business Profile"
3. Sign in with the Google account that manages your Business Profile
4. Grant the requested permissions
5. ReviewMate will start syncing your reviews immediately

After connecting, reviews will sync every 2 hours automatically. You will see your existing review history in the Reviews tab.`,
            },
            {
                title: 'Replying to reviews with AI',
                content: `When you have a Google review that needs a reply:

1. Go to Reviews → select the review
2. Click "Suggest a reply" — ReviewMate will generate 3 AI-written options using Claude (Anthropic)
3. Click any option to use it as your reply, or edit it first
4. Click "Post reply" — ReviewMate posts it directly to Google

You can also write your own reply without using the AI suggestions. Saved templates from Settings → Reply Templates will also appear for quick selection.`,
            },
        ],
    },
    {
        id: 'integrations',
        title: 'Integrations',
        articles: [
            {
                title: 'ServiceM8 (tradies)',
                content: `ReviewMate connects with ServiceM8 via OAuth. When a job is completed in ServiceM8, ReviewMate automatically sends a review request to the customer on the job.

**Setup:**
1. Go to Settings → Integrations
2. Click "Connect ServiceM8"
3. Authorise the connection in ServiceM8
4. Toggle "Auto-send on job completion" on

ReviewMate receives webhook events from ServiceM8 when jobs are completed.`,
            },
            {
                title: 'Xero (invoice paid)',
                content: `ReviewMate can trigger review requests when an invoice is paid in Xero.

**Setup:**
1. Go to Settings → Integrations
2. Click "Connect Xero"
3. Authorise the connection in Xero
4. Copy the Xero webhook URL and paste it into Xero Settings → Webhooks
5. Toggle "Auto-send on invoice paid" on`,
            },
            {
                title: 'Cliniko (allied health)',
                content: `ReviewMate polls Cliniko for recently completed appointments and sends review requests automatically.

**Setup:**
1. Go to Settings → Integrations → Cliniko
2. Enter your Cliniko API key (found in Cliniko Settings → API)
3. Enter your Cliniko shard (the prefix of your Cliniko URL, e.g. "au2")
4. Toggle "Auto-send on appointment completion" on

ReviewMate polls your Cliniko account every 15 minutes for new completed appointments.`,
            },
            {
                title: 'Generic webhook (Zapier / Make / Fergus)',
                content: `ReviewMate provides a generic incoming webhook URL that you can call from any automation tool.

**Setup:**
1. Go to Settings → Integrations → scroll to "Generic webhook"
2. Copy your unique webhook URL
3. Configure Zapier, Make, or Fergus to POST to this URL when a trigger event happens

**Payload format:**
\`\`\`json
{
  "name": "Customer name",
  "email": "customer@example.com",
  "phone": "+61400000000",
  "channel": "email"
}
\`\`\`

The \`channel\` field is optional (defaults to "email"). Set to "sms" or "both" as needed.`,
            },
        ],
    },
    {
        id: 'billing',
        title: 'Billing',
        articles: [
            {
                title: 'Plans and pricing',
                content: `ReviewMate has three plans:

**Free** — $0/month
- 1 location, 50 customers, 10 requests/month
- Good for trying ReviewMate before committing

**Starter** — $49/month AUD
- 1 location, unlimited customers, unlimited requests
- All features including integrations and SMS

**Pro** — $99/month AUD
- Up to 5 locations, unlimited customers, unlimited requests
- Multi-location analytics`,
            },
            {
                title: 'Upgrading or cancelling',
                content: `Go to Settings → Billing to upgrade, downgrade, or cancel.

Upgrades take effect immediately. Downgrades take effect at the end of your current billing period. Cancellations take effect at the end of your current billing period — you keep access until then.

To manage your payment method or download invoices, click "Manage billing" which opens the Stripe customer portal.`,
            },
        ],
    },
];

export default function Docs() {
    const [activeSection, setActiveSection] = useState(sections[0].id);
    const [activeArticle, setActiveArticle] = useState(0);

    const currentSection = sections.find((s) => s.id === activeSection) ?? sections[0];
    const currentArticle = currentSection.articles[activeArticle];

    function renderContent(content: string) {
        const lines = content.split('\n');
        return lines.map((line, i) => {
            if (line.startsWith('**') && line.endsWith('**')) {
                return <p key={i} className="mt-4 font-semibold text-gray-900">{line.slice(2, -2)}</p>;
            }
            if (line.startsWith('1. ') || line.startsWith('2. ') || line.startsWith('3. ') || line.startsWith('4. ') || line.startsWith('5. ')) {
                return <p key={i} className="ml-4 text-sm text-gray-600">{line}</p>;
            }
            if (line.startsWith('```')) {
                return null;
            }
            if (line.startsWith('{')) {
                return <pre key={i} className="mt-2 rounded-lg bg-gray-100 p-4 text-xs text-gray-700 overflow-x-auto">{line}</pre>;
            }
            if (line === '') {
                return <div key={i} className="h-2" />;
            }
            // Handle inline bold
            const parts = line.split(/\*\*([^*]+)\*\*/g);
            return (
                <p key={i} className="text-sm text-gray-600 leading-relaxed">
                    {parts.map((part, j) => j % 2 === 1 ? <strong key={j}>{part}</strong> : part)}
                </p>
            );
        });
    }

    return (
        <>
            <Head title="Documentation — ReviewMate" />

            <div className="min-h-screen bg-white text-gray-900 antialiased">

                {/* Nav */}
                <nav className="border-b border-gray-100 bg-white">
                    <div className="mx-auto flex max-w-7xl items-center justify-between px-6 py-4">
                        <Link href="/" className="flex items-center gap-2">
                            <div className="flex h-8 w-8 items-center justify-center rounded-lg bg-teal-600">
                                <svg className="h-4 w-4 text-white" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                                </svg>
                            </div>
                            <span className="text-lg font-bold tracking-tight">ReviewMate</span>
                        </Link>
                        <div className="flex items-center gap-3">
                            <span className="text-sm text-gray-400">Documentation</span>
                            <Link href="/login" className="rounded-lg border border-gray-200 px-3 py-1.5 text-sm font-medium text-gray-600 hover:border-gray-300 hover:text-gray-900 transition-colors">
                                Sign in
                            </Link>
                        </div>
                    </div>
                </nav>

                <div className="mx-auto max-w-7xl px-6 py-10">
                    <div className="flex gap-10">

                        {/* Sidebar */}
                        <aside className="hidden w-56 shrink-0 md:block">
                            <nav className="sticky top-6 space-y-6">
                                {sections.map((section) => (
                                    <div key={section.id}>
                                        <p className="mb-2 text-xs font-semibold uppercase tracking-widest text-gray-400">{section.title}</p>
                                        <ul className="space-y-1">
                                            {section.articles.map((article, idx) => (
                                                <li key={article.title}>
                                                    <button
                                                        onClick={() => { setActiveSection(section.id); setActiveArticle(idx); }}
                                                        className={`w-full rounded-lg px-3 py-1.5 text-left text-sm transition-colors ${activeSection === section.id && activeArticle === idx
                                                            ? 'bg-teal-50 font-medium text-teal-700'
                                                            : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900'
                                                        }`}
                                                    >
                                                        {article.title}
                                                    </button>
                                                </li>
                                            ))}
                                        </ul>
                                    </div>
                                ))}
                            </nav>
                        </aside>

                        {/* Content */}
                        <main className="min-w-0 flex-1">
                            <h1 className="mb-6 text-2xl font-bold text-gray-900">{currentArticle.title}</h1>
                            <div className="prose-sm space-y-1 max-w-2xl">
                                {renderContent(currentArticle.content)}
                            </div>

                            {/* Article nav */}
                            <div className="mt-12 flex justify-between border-t border-gray-100 pt-6">
                                {activeArticle > 0 || sections.indexOf(currentSection) > 0 ? (
                                    <button
                                        onClick={() => {
                                            if (activeArticle > 0) {
                                                setActiveArticle(activeArticle - 1);
                                            } else {
                                                const prevSection = sections[sections.indexOf(currentSection) - 1];
                                                setActiveSection(prevSection.id);
                                                setActiveArticle(prevSection.articles.length - 1);
                                            }
                                        }}
                                        className="text-sm text-teal-600 hover:text-teal-700"
                                    >
                                        Previous
                                    </button>
                                ) : <span />}
                                {activeArticle < currentSection.articles.length - 1 || sections.indexOf(currentSection) < sections.length - 1 ? (
                                    <button
                                        onClick={() => {
                                            if (activeArticle < currentSection.articles.length - 1) {
                                                setActiveArticle(activeArticle + 1);
                                            } else {
                                                const nextSection = sections[sections.indexOf(currentSection) + 1];
                                                setActiveSection(nextSection.id);
                                                setActiveArticle(0);
                                            }
                                        }}
                                        className="text-sm text-teal-600 hover:text-teal-700"
                                    >
                                        Next
                                    </button>
                                ) : <span />}
                            </div>
                        </main>

                    </div>
                </div>

            </div>
        </>
    );
}
