import { Head, router } from '@inertiajs/react';
import { useState } from 'react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Switch } from '@/components/ui/switch';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Integrations', href: '/settings/integrations' },
];

interface Props {
    servicem8Connected: boolean;
    servicem8AutoSend: boolean;
    webhookUrl: string | null;
    xeroConnected: boolean;
    xeroAutoSend: boolean;
    xeroWebhookUrl: string | null;
    clinikoConnected: boolean;
    clinikoAutoSend: boolean;
    timelyConnected: boolean;
    timelyAutoSend: boolean;
    timelyWebhookUrl: string | null;
    simproConnected: boolean;
    simproAutoSend: boolean;
    simproWebhookUrl: string | null;
    halaxyConnected: boolean;
    halaxyAutoSend: boolean;
    jobberConnected: boolean;
    jobberAutoSend: boolean;
    jobberWebhookUrl: string | null;
    housecallProConnected: boolean;
    housecallProAutoSend: boolean;
    housecallProWebhookUrl: string | null;
    incomingWebhookToken: string | null;
    incomingWebhookUrl: string | null;
}

function ConnectedBadge({ connected }: { connected: boolean }) {
    return connected ? (
        <Badge className="bg-green-100 text-green-700 hover:bg-green-100">Connected</Badge>
    ) : (
        <Badge className="bg-gray-100 text-gray-500 hover:bg-gray-100">Not connected</Badge>
    );
}

function WebhookUrlBox({ url, instructions }: { url: string; instructions: React.ReactNode }) {
    return (
        <div className="rounded-lg border border-blue-200 bg-blue-50 p-4">
            <p className="text-xs font-medium text-blue-800">Your webhook URL</p>
            <p className="mt-1 text-xs text-blue-700">{instructions}</p>
            <div className="mt-2 flex items-center gap-2">
                <code className="flex-1 truncate rounded bg-blue-100 px-2 py-1 font-mono text-xs text-blue-900">
                    {url}
                </code>
                <button
                    type="button"
                    className="shrink-0 text-xs text-blue-600 hover:text-blue-800 underline"
                    onClick={() => navigator.clipboard.writeText(url)}
                >
                    Copy
                </button>
            </div>
        </div>
    );
}

function AutoSendToggle({
    id,
    label,
    description,
    checked,
    onToggle,
}: {
    id: string;
    label: string;
    description: string;
    checked: boolean;
    onToggle: () => void;
}) {
    return (
        <div className="flex items-center justify-between rounded-lg border border-gray-200 bg-gray-50 p-4">
            <div>
                <Label htmlFor={id} className="text-sm font-medium">
                    {label}
                </Label>
                <p className="mt-0.5 text-xs text-gray-500">{description}</p>
            </div>
            <Switch id={id} checked={checked} onCheckedChange={onToggle} />
        </div>
    );
}

export default function Integrations({
    servicem8Connected,
    servicem8AutoSend,
    webhookUrl,
    xeroConnected,
    xeroAutoSend,
    xeroWebhookUrl,
    clinikoConnected,
    clinikoAutoSend,
    timelyConnected,
    timelyAutoSend,
    timelyWebhookUrl,
    simproConnected,
    simproAutoSend,
    simproWebhookUrl,
    halaxyConnected,
    halaxyAutoSend,
    jobberConnected,
    jobberAutoSend,
    jobberWebhookUrl,
    housecallProConnected,
    housecallProAutoSend,
    housecallProWebhookUrl,
    incomingWebhookUrl,
}: Props) {
    const [clinikoApiKey, setClinikoApiKey] = useState('');
    const [clinikoError, setClinikoError] = useState<string | null>(null);
    const [clinikoSubmitting, setClinikoSubmitting] = useState(false);

    const [simproCompanyUrl, setSimproCompanyUrl] = useState('');
    const [simproSubmitting, setSimproSubmitting] = useState(false);

    const [halaxyApiKey, setHalaxyApiKey] = useState('');
    const [halaxyError, setHalaxyError] = useState<string | null>(null);
    const [halaxySubmitting, setHalaxySubmitting] = useState(false);

    // ServiceM8
    const handleServiceM8Connect = () => {
        window.location.href = '/integrations/servicem8/connect';
    };
    const handleServiceM8Disconnect = () => {
        router.post('/integrations/servicem8/disconnect');
    };
    const handleServiceM8ToggleAutoSend = () => {
        router.post('/integrations/servicem8/toggle-auto-send', {}, { preserveScroll: true });
    };

    // Xero
    const handleXeroConnect = () => {
        window.location.href = '/integrations/xero/connect';
    };
    const handleXeroDisconnect = () => {
        router.post('/integrations/xero/disconnect');
    };
    const handleXeroToggleAutoSend = () => {
        router.post('/integrations/xero/toggle-auto-send', {}, { preserveScroll: true });
    };

    // Cliniko
    const handleClinikoConnect = (e: React.FormEvent) => {
        e.preventDefault();
        setClinikoError(null);
        setClinikoSubmitting(true);
        router.post(
            '/integrations/cliniko/connect',
            { api_key: clinikoApiKey },
            {
                preserveScroll: true,
                onError: (errors) => {
                    setClinikoError(errors.api_key ?? 'Connection failed. Please check your API key.');
                    setClinikoSubmitting(false);
                },
                onSuccess: () => {
                    setClinikoApiKey('');
                    setClinikoSubmitting(false);
                },
                onFinish: () => setClinikoSubmitting(false),
            },
        );
    };
    const handleClinikoDisconnect = () => {
        router.post('/integrations/cliniko/disconnect');
    };
    const handleClinikoToggleAutoSend = () => {
        router.post('/integrations/cliniko/toggle-auto-send', {}, { preserveScroll: true });
    };

    // Timely
    const handleTimelyConnect = () => {
        window.location.href = '/integrations/timely/connect';
    };
    const handleTimelyDisconnect = () => {
        router.post('/integrations/timely/disconnect');
    };
    const handleTimelyToggleAutoSend = () => {
        router.post('/integrations/timely/toggle-auto-send', {}, { preserveScroll: true });
    };

    // Simpro
    const handleSimproConnect = (e: React.FormEvent) => {
        e.preventDefault();
        setSimproSubmitting(true);
        router.post(
            '/integrations/simpro/connect',
            { company_url: simproCompanyUrl },
            {
                onFinish: () => setSimproSubmitting(false),
            },
        );
    };
    const handleSimproDisconnect = () => {
        router.post('/integrations/simpro/disconnect');
    };
    const handleSimproToggleAutoSend = () => {
        router.post('/integrations/simpro/toggle-auto-send', {}, { preserveScroll: true });
    };

    // Jobber
    const handleJobberConnect = () => {
        window.location.href = '/integrations/jobber/connect';
    };
    const handleJobberDisconnect = () => {
        router.post('/integrations/jobber/disconnect');
    };
    const handleJobberToggleAutoSend = () => {
        router.post('/integrations/jobber/toggle-auto-send', {}, { preserveScroll: true });
    };

    // Housecall Pro
    const handleHousecallProConnect = () => {
        window.location.href = '/integrations/housecallpro/connect';
    };
    const handleHousecallProDisconnect = () => {
        router.post('/integrations/housecallpro/disconnect');
    };
    const handleHousecallProToggleAutoSend = () => {
        router.post('/integrations/housecallpro/toggle-auto-send', {}, { preserveScroll: true });
    };

    // Halaxy
    const handleHalaxyConnect = (e: React.FormEvent) => {
        e.preventDefault();
        setHalaxyError(null);
        setHalaxySubmitting(true);
        router.post(
            '/integrations/halaxy/connect',
            { api_key: halaxyApiKey },
            {
                preserveScroll: true,
                onError: (errors) => {
                    setHalaxyError(errors.api_key ?? 'Connection failed. Please check your API key.');
                    setHalaxySubmitting(false);
                },
                onSuccess: () => {
                    setHalaxyApiKey('');
                    setHalaxySubmitting(false);
                },
                onFinish: () => setHalaxySubmitting(false),
            },
        );
    };
    const handleHalaxyDisconnect = () => {
        router.post('/integrations/halaxy/disconnect');
    };
    const handleHalaxyToggleAutoSend = () => {
        router.post('/integrations/halaxy/toggle-auto-send', {}, { preserveScroll: true });
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Integrations" />
            <div className="flex h-full flex-1 flex-col gap-6 p-4 md:p-6 max-w-2xl">
                <div>
                    <h1 className="text-2xl font-bold text-gray-900">Integrations</h1>
                    <p className="mt-1 text-sm text-gray-500">
                        Connect ReviewMate to your other business tools to automatically send review requests.
                    </p>
                </div>

                {/* ServiceM8 Card */}
                <Card>
                    <CardHeader>
                        <div className="flex items-center justify-between">
                            <div className="flex items-center gap-3">
                                <div className="flex h-10 w-10 items-center justify-center rounded-lg bg-orange-100">
                                    <span className="text-sm font-bold text-orange-700">S8</span>
                                </div>
                                <div>
                                    <CardTitle className="text-base">ServiceM8</CardTitle>
                                    <CardDescription className="text-xs">
                                        For tradies. Auto-sends review requests when a job is completed.
                                    </CardDescription>
                                </div>
                            </div>
                            <ConnectedBadge connected={servicem8Connected} />
                        </div>
                    </CardHeader>
                    <CardContent className="space-y-4">
                        <p className="text-sm text-gray-600">
                            Automatically send Google review requests to customers when a job is marked as complete in ServiceM8.
                            No manual effort required — ReviewMate handles it for you.
                        </p>

                        {servicem8Connected ? (
                            <div className="space-y-4">
                                <AutoSendToggle
                                    id="servicem8-auto-send"
                                    label="Auto-send review requests"
                                    description="Send a review request automatically when a ServiceM8 job is completed."
                                    checked={servicem8AutoSend}
                                    onToggle={handleServiceM8ToggleAutoSend}
                                />

                                {webhookUrl && (
                                    <WebhookUrlBox
                                        url={webhookUrl}
                                        instructions={
                                            <>
                                                Configure this URL in your ServiceM8 account under{' '}
                                                <strong>Settings &rarr; Webhooks</strong> for the{' '}
                                                <strong>Job Completion</strong> event.
                                            </>
                                        }
                                    />
                                )}

                                <Button
                                    variant="outline"
                                    className="border-red-200 text-red-600 hover:bg-red-50"
                                    onClick={handleServiceM8Disconnect}
                                >
                                    Disconnect ServiceM8
                                </Button>
                            </div>
                        ) : (
                            <Button
                                className="bg-teal-600 hover:bg-teal-700 text-white"
                                onClick={handleServiceM8Connect}
                            >
                                Connect ServiceM8
                            </Button>
                        )}
                    </CardContent>
                </Card>

                {/* Xero Card */}
                <Card>
                    <CardHeader>
                        <div className="flex items-center justify-between">
                            <div className="flex items-center gap-3">
                                <div className="flex h-10 w-10 items-center justify-center rounded-lg bg-blue-100">
                                    <span className="text-sm font-bold text-blue-700">XR</span>
                                </div>
                                <div>
                                    <CardTitle className="text-base">Xero</CardTitle>
                                    <CardDescription className="text-xs">
                                        For any business using Xero. Triggers when an invoice is marked paid.
                                    </CardDescription>
                                </div>
                            </div>
                            <ConnectedBadge connected={xeroConnected} />
                        </div>
                    </CardHeader>
                    <CardContent className="space-y-4">
                        <p className="text-sm text-gray-600">
                            Automatically send a review request when a Xero invoice is marked as paid. Works for any
                            business that invoices customers through Xero.
                        </p>

                        {xeroConnected ? (
                            <div className="space-y-4">
                                <AutoSendToggle
                                    id="xero-auto-send"
                                    label="Auto-send review requests"
                                    description="Send a review request automatically when a Xero invoice is marked paid."
                                    checked={xeroAutoSend}
                                    onToggle={handleXeroToggleAutoSend}
                                />

                                {xeroWebhookUrl && (
                                    <WebhookUrlBox
                                        url={xeroWebhookUrl}
                                        instructions={
                                            <>
                                                Configure this URL in your Xero account under{' '}
                                                <strong>Settings &rarr; Webhooks</strong>.
                                            </>
                                        }
                                    />
                                )}

                                <Button
                                    variant="outline"
                                    className="border-red-200 text-red-600 hover:bg-red-50"
                                    onClick={handleXeroDisconnect}
                                >
                                    Disconnect Xero
                                </Button>
                            </div>
                        ) : (
                            <Button
                                className="bg-teal-600 hover:bg-teal-700 text-white"
                                onClick={handleXeroConnect}
                            >
                                Connect Xero
                            </Button>
                        )}
                    </CardContent>
                </Card>

                {/* Cliniko Card */}
                <Card>
                    <CardHeader>
                        <div className="flex items-center justify-between">
                            <div className="flex items-center gap-3">
                                <div className="flex h-10 w-10 items-center justify-center rounded-lg bg-teal-100">
                                    <span className="text-sm font-bold text-teal-700">CL</span>
                                </div>
                                <div>
                                    <CardTitle className="text-base">Cliniko</CardTitle>
                                    <CardDescription className="text-xs">
                                        For allied health practitioners. Sends review requests after completed appointments.
                                    </CardDescription>
                                </div>
                            </div>
                            <ConnectedBadge connected={clinikoConnected} />
                        </div>
                    </CardHeader>
                    <CardContent className="space-y-4">
                        <p className="text-sm text-gray-600">
                            ReviewMate polls Cliniko daily for completed appointments (physios, chiros, psychologists, etc.)
                            and automatically sends review requests to patients. Uses your personal Cliniko API key.
                        </p>

                        {clinikoConnected ? (
                            <div className="space-y-4">
                                <AutoSendToggle
                                    id="cliniko-auto-send"
                                    label="Auto-send review requests"
                                    description="Send a review request automatically when a Cliniko appointment is completed."
                                    checked={clinikoAutoSend}
                                    onToggle={handleClinikoToggleAutoSend}
                                />

                                <Button
                                    variant="outline"
                                    className="border-red-200 text-red-600 hover:bg-red-50"
                                    onClick={handleClinikoDisconnect}
                                >
                                    Disconnect Cliniko
                                </Button>
                            </div>
                        ) : (
                            <form onSubmit={handleClinikoConnect} className="space-y-3">
                                <div className="space-y-1">
                                    <Label htmlFor="cliniko-api-key" className="text-sm font-medium">
                                        Cliniko API key
                                    </Label>
                                    <p className="text-xs text-gray-500">
                                        Find your API key in Cliniko under <strong>My Info &rarr; Cliniko API</strong>.
                                    </p>
                                    <Input
                                        id="cliniko-api-key"
                                        type="password"
                                        placeholder="Paste your Cliniko API key"
                                        value={clinikoApiKey}
                                        onChange={(e) => setClinikoApiKey(e.target.value)}
                                        required
                                    />
                                    {clinikoError && (
                                        <p className="text-xs text-red-600">{clinikoError}</p>
                                    )}
                                </div>
                                <Button
                                    type="submit"
                                    className="bg-teal-600 hover:bg-teal-700 text-white"
                                    disabled={clinikoSubmitting || !clinikoApiKey.trim()}
                                >
                                    {clinikoSubmitting ? 'Connecting...' : 'Connect Cliniko'}
                                </Button>
                            </form>
                        )}
                    </CardContent>
                </Card>

                {/* Timely Card */}
                <Card>
                    <CardHeader>
                        <div className="flex items-center justify-between">
                            <div className="flex items-center gap-3">
                                <div className="flex h-10 w-10 items-center justify-center rounded-lg bg-purple-100">
                                    <span className="text-sm font-bold text-purple-700">TM</span>
                                </div>
                                <div>
                                    <CardTitle className="text-base">Timely</CardTitle>
                                    <CardDescription className="text-xs">
                                        For salons and beauty businesses. Sends review requests after completed appointments.
                                    </CardDescription>
                                </div>
                            </div>
                            <ConnectedBadge connected={timelyConnected} />
                        </div>
                    </CardHeader>
                    <CardContent className="space-y-4">
                        <p className="text-sm text-gray-600">
                            Automatically send a review request when an appointment is completed in Timely. Uses native
                            webhooks for instant delivery — no polling required.
                        </p>

                        {timelyConnected ? (
                            <div className="space-y-4">
                                <AutoSendToggle
                                    id="timely-auto-send"
                                    label="Auto-send review requests"
                                    description="Send a review request automatically when a Timely appointment is completed."
                                    checked={timelyAutoSend}
                                    onToggle={handleTimelyToggleAutoSend}
                                />

                                {timelyWebhookUrl && (
                                    <WebhookUrlBox
                                        url={timelyWebhookUrl}
                                        instructions={
                                            <>
                                                Configure this URL in your Timely account under{' '}
                                                <strong>Settings &rarr; Integrations &rarr; Webhooks</strong> for the{' '}
                                                <strong>Appointment Completed</strong> event.
                                            </>
                                        }
                                    />
                                )}

                                <Button
                                    variant="outline"
                                    className="border-red-200 text-red-600 hover:bg-red-50"
                                    onClick={handleTimelyDisconnect}
                                >
                                    Disconnect Timely
                                </Button>
                            </div>
                        ) : (
                            <Button
                                className="bg-teal-600 hover:bg-teal-700 text-white"
                                onClick={handleTimelyConnect}
                            >
                                Connect Timely
                            </Button>
                        )}
                    </CardContent>
                </Card>
                {/* Simpro Card */}
                <Card>
                    <CardHeader>
                        <div className="flex items-center justify-between">
                            <div className="flex items-center gap-3">
                                <div className="flex h-10 w-10 items-center justify-center rounded-lg bg-blue-900 bg-opacity-10">
                                    <span className="text-sm font-bold text-blue-900">SP</span>
                                </div>
                                <div>
                                    <CardTitle className="text-base">Simpro</CardTitle>
                                    <CardDescription className="text-xs">
                                        For larger trade businesses. Sends review requests when a job is marked complete.
                                    </CardDescription>
                                </div>
                            </div>
                            <ConnectedBadge connected={simproConnected} />
                        </div>
                    </CardHeader>
                    <CardContent className="space-y-4">
                        <p className="text-sm text-gray-600">
                            Automatically send Google review requests to customers when a job is marked complete in Simpro.
                            Ideal for larger trade businesses with 5–50 staff. Uses OAuth for a secure connection.
                        </p>

                        {simproConnected ? (
                            <div className="space-y-4">
                                <AutoSendToggle
                                    id="simpro-auto-send"
                                    label="Auto-send review requests"
                                    description="Send a review request automatically when a Simpro job is completed."
                                    checked={simproAutoSend}
                                    onToggle={handleSimproToggleAutoSend}
                                />

                                {simproWebhookUrl && (
                                    <WebhookUrlBox
                                        url={simproWebhookUrl}
                                        instructions={
                                            <>
                                                Configure this URL in your Simpro account under{' '}
                                                <strong>Setup &rarr; Webhooks</strong> for the{' '}
                                                <strong>Job Status Changed</strong> event.
                                            </>
                                        }
                                    />
                                )}

                                <Button
                                    variant="outline"
                                    className="border-red-200 text-red-600 hover:bg-red-50"
                                    onClick={handleSimproDisconnect}
                                >
                                    Disconnect Simpro
                                </Button>
                            </div>
                        ) : (
                            <form onSubmit={handleSimproConnect} className="space-y-3">
                                <div className="space-y-1">
                                    <Label htmlFor="simpro-company-url" className="text-sm font-medium">
                                        Simpro company URL
                                    </Label>
                                    <p className="text-xs text-gray-500">
                                        Enter your Simpro subdomain, e.g. <strong>mycompany.simprocloud.com</strong>
                                    </p>
                                    <Input
                                        id="simpro-company-url"
                                        type="text"
                                        placeholder="mycompany.simprocloud.com"
                                        value={simproCompanyUrl}
                                        onChange={(e) => setSimproCompanyUrl(e.target.value)}
                                        required
                                    />
                                </div>
                                <Button
                                    type="submit"
                                    className="bg-teal-600 hover:bg-teal-700 text-white"
                                    disabled={simproSubmitting || !simproCompanyUrl.trim()}
                                >
                                    {simproSubmitting ? 'Connecting...' : 'Connect Simpro'}
                                </Button>
                            </form>
                        )}
                    </CardContent>
                </Card>

                {/* Halaxy Card */}
                <Card>
                    <CardHeader>
                        <div className="flex items-center justify-between">
                            <div className="flex items-center gap-3">
                                <div className="flex h-10 w-10 items-center justify-center rounded-lg bg-green-100">
                                    <span className="text-sm font-bold text-green-700">HL</span>
                                </div>
                                <div>
                                    <CardTitle className="text-base">Halaxy</CardTitle>
                                    <CardDescription className="text-xs">
                                        For GPs and allied health. Australian-built practice management.
                                    </CardDescription>
                                </div>
                            </div>
                            <ConnectedBadge connected={halaxyConnected} />
                        </div>
                    </CardHeader>
                    <CardContent className="space-y-4">
                        <p className="text-sm text-gray-600">
                            ReviewMate polls Halaxy daily for completed appointments (GPs, psychiatrists, psychologists,
                            physiotherapists, and more) and automatically sends review requests to patients.
                            Uses your personal Halaxy API key.
                        </p>

                        {halaxyConnected ? (
                            <div className="space-y-4">
                                <AutoSendToggle
                                    id="halaxy-auto-send"
                                    label="Auto-send review requests"
                                    description="Send a review request automatically when a Halaxy appointment is completed."
                                    checked={halaxyAutoSend}
                                    onToggle={handleHalaxyToggleAutoSend}
                                />

                                <Button
                                    variant="outline"
                                    className="border-red-200 text-red-600 hover:bg-red-50"
                                    onClick={handleHalaxyDisconnect}
                                >
                                    Disconnect Halaxy
                                </Button>
                            </div>
                        ) : (
                            <form onSubmit={handleHalaxyConnect} className="space-y-3">
                                <div className="space-y-1">
                                    <Label htmlFor="halaxy-api-key" className="text-sm font-medium">
                                        Halaxy API key
                                    </Label>
                                    <p className="text-xs text-gray-500">
                                        Find your API key in Halaxy under <strong>Settings &rarr; Integrations &rarr; API</strong>.
                                    </p>
                                    <Input
                                        id="halaxy-api-key"
                                        type="password"
                                        placeholder="Paste your Halaxy API key"
                                        value={halaxyApiKey}
                                        onChange={(e) => setHalaxyApiKey(e.target.value)}
                                        required
                                    />
                                    {halaxyError && (
                                        <p className="text-xs text-red-600">{halaxyError}</p>
                                    )}
                                </div>
                                <Button
                                    type="submit"
                                    className="bg-teal-600 hover:bg-teal-700 text-white"
                                    disabled={halaxySubmitting || !halaxyApiKey.trim()}
                                >
                                    {halaxySubmitting ? 'Connecting...' : 'Connect Halaxy'}
                                </Button>
                            </form>
                        )}
                    </CardContent>
                </Card>

                {/* Custom Webhook Card */}
                <Card>
                    <CardHeader>
                        <div className="flex items-center justify-between">
                            <div className="flex items-center gap-3">
                                <div className="flex h-10 w-10 items-center justify-center rounded-lg bg-gray-100">
                                    <span className="text-sm font-bold text-gray-700">{'{}'}</span>
                                </div>
                                <div>
                                    <CardTitle className="text-base">Custom Webhook</CardTitle>
                                    <CardDescription className="text-xs">
                                        Connect any tool via Zapier, Make, or direct HTTP POST.
                                    </CardDescription>
                                </div>
                            </div>
                        </div>
                    </CardHeader>
                    <CardContent className="space-y-4">
                        <p className="text-sm text-gray-600">
                            Works with Fergus, Tradify, Mindbody, and 5,000+ other apps. Any tool that can make an HTTP
                            POST request can trigger a review request — no native integration required.
                        </p>

                        {incomingWebhookUrl ? (
                            <div className="space-y-4">
                                <div className="rounded-lg border border-blue-200 bg-blue-50 p-4">
                                    <p className="text-xs font-medium text-blue-800">Your webhook URL</p>
                                    <p className="mt-1 text-xs text-blue-700">
                                        POST to this URL with <code className="bg-blue-100 px-1 rounded">name</code>,{' '}
                                        <code className="bg-blue-100 px-1 rounded">email</code>, and/or{' '}
                                        <code className="bg-blue-100 px-1 rounded">phone</code> fields.
                                    </p>
                                    <div className="mt-2 flex items-center gap-2">
                                        <code className="flex-1 truncate rounded bg-blue-100 px-2 py-1 font-mono text-xs text-blue-900">
                                            {incomingWebhookUrl}
                                        </code>
                                        <button
                                            type="button"
                                            className="shrink-0 text-xs text-blue-600 hover:text-blue-800 underline"
                                            onClick={() => navigator.clipboard.writeText(incomingWebhookUrl)}
                                        >
                                            Copy
                                        </button>
                                    </div>
                                </div>

                                <div className="rounded-lg border border-gray-200 bg-gray-50 p-4">
                                    <p className="mb-2 text-xs font-medium text-gray-700">Example request (Zapier / Make / n8n / direct):</p>
                                    <pre className="overflow-x-auto rounded bg-gray-900 px-3 py-2 font-mono text-xs text-gray-100">{`POST ${incomingWebhookUrl}
Content-Type: application/json

{
  "name": "{{customer_name}}",
  "email": "{{customer_email}}",
  "phone": "{{customer_phone}}",
  "trigger": "job_completed"
}`}</pre>
                                </div>

                                <Button
                                    variant="outline"
                                    className="border-gray-200 text-gray-600 hover:bg-gray-50"
                                    onClick={() =>
                                        router.post(
                                            '/settings/integrations/webhook/regenerate',
                                            {},
                                            { preserveScroll: true },
                                        )
                                    }
                                >
                                    Regenerate Token
                                </Button>
                            </div>
                        ) : (
                            <p className="text-sm text-gray-500">
                                No webhook token generated yet. Complete your business setup to activate this feature.
                            </p>
                        )}
                    </CardContent>
                </Card>

                {/* Jobber Card */}
                <Card>
                    <CardHeader>
                        <div className="flex items-center justify-between">
                            <div className="flex items-center gap-3">
                                <div className="flex h-10 w-10 items-center justify-center rounded-lg bg-green-100">
                                    <span className="text-sm font-bold text-green-700">JB</span>
                                </div>
                                <div>
                                    <CardTitle className="text-base">Jobber</CardTitle>
                                    <CardDescription className="text-xs">
                                        For field service businesses. Auto-sends review requests when a job is completed.
                                    </CardDescription>
                                </div>
                            </div>
                            <ConnectedBadge connected={jobberConnected} />
                        </div>
                    </CardHeader>
                    <CardContent className="space-y-4">
                        <p className="text-sm text-gray-600">
                            Automatically send a Google review request when a job is marked complete in Jobber.
                            Works for landscapers, cleaners, HVAC, plumbers, and any field service business using Jobber.
                        </p>

                        {jobberConnected ? (
                            <div className="space-y-4">
                                <AutoSendToggle
                                    id="jobber-auto-send"
                                    label="Auto-send review requests"
                                    description="Send a review request automatically when a Jobber job is completed."
                                    checked={jobberAutoSend}
                                    onToggle={handleJobberToggleAutoSend}
                                />

                                {jobberWebhookUrl && (
                                    <WebhookUrlBox
                                        url={jobberWebhookUrl}
                                        instructions={
                                            <>
                                                In Jobber, go to <strong>Settings &rarr; Integrations &rarr; Webhooks</strong> and
                                                add this URL for the <strong>Job Updated</strong> event.
                                            </>
                                        }
                                    />
                                )}

                                <Button
                                    variant="outline"
                                    className="border-red-200 text-red-600 hover:bg-red-50"
                                    onClick={handleJobberDisconnect}
                                >
                                    Disconnect Jobber
                                </Button>
                            </div>
                        ) : (
                            <Button
                                className="bg-teal-600 hover:bg-teal-700 text-white"
                                onClick={handleJobberConnect}
                            >
                                Connect Jobber
                            </Button>
                        )}
                    </CardContent>
                </Card>

                {/* Housecall Pro Card */}
                <Card>
                    <CardHeader>
                        <div className="flex items-center justify-between">
                            <div className="flex items-center gap-3">
                                <div className="flex h-10 w-10 items-center justify-center rounded-lg bg-blue-100">
                                    <span className="text-sm font-bold text-blue-700">HC</span>
                                </div>
                                <div>
                                    <CardTitle className="text-base">Housecall Pro</CardTitle>
                                    <CardDescription className="text-xs">
                                        For home service pros. Auto-sends review requests when a job is completed.
                                    </CardDescription>
                                </div>
                            </div>
                            <ConnectedBadge connected={housecallProConnected} />
                        </div>
                    </CardHeader>
                    <CardContent className="space-y-4">
                        <p className="text-sm text-gray-600">
                            Automatically send a Google review request when a job is marked complete in Housecall Pro.
                            Works for plumbers, electricians, HVAC techs, carpet cleaners, and any home service business.
                        </p>

                        {housecallProConnected ? (
                            <div className="space-y-4">
                                <AutoSendToggle
                                    id="housecallpro-auto-send"
                                    label="Auto-send review requests"
                                    description="Send a review request automatically when a Housecall Pro job is completed."
                                    checked={housecallProAutoSend}
                                    onToggle={handleHousecallProToggleAutoSend}
                                />

                                {housecallProWebhookUrl && (
                                    <WebhookUrlBox
                                        url={housecallProWebhookUrl}
                                        instructions={
                                            <>
                                                In Housecall Pro, go to <strong>Settings &rarr; Integrations &rarr; Webhooks</strong> and
                                                add this URL for the <strong>Job Completed</strong> event.
                                            </>
                                        }
                                    />
                                )}

                                <Button
                                    variant="outline"
                                    className="border-red-200 text-red-600 hover:bg-red-50"
                                    onClick={handleHousecallProDisconnect}
                                >
                                    Disconnect Housecall Pro
                                </Button>
                            </div>
                        ) : (
                            <Button
                                className="bg-teal-600 hover:bg-teal-700 text-white"
                                onClick={handleHousecallProConnect}
                            >
                                Connect Housecall Pro
                            </Button>
                        )}
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
