import { Head, router, usePage } from '@inertiajs/react';
import { useState, useEffect } from 'react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/app-layout';
import { index as quickSendIndex, send as quickSendSend } from '@/routes/quick-send';
import type { BreadcrumbItem } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Quick Send',
        href: quickSendIndex(),
    },
];

type Channel = 'email' | 'sms' | 'both';

interface RecentlySentItem {
    id: number;
    customer_name: string;
    customer_email: string;
    channel: Channel;
    sent_at: string;
}

interface Props {
    business: {
        id: number;
        name: string;
    };
    recentlySent: RecentlySentItem[];
}

const channelOptions: { value: Channel; label: string; description: string }[] = [
    { value: 'email', label: 'Email', description: 'Send via email' },
    { value: 'sms', label: 'SMS', description: 'Send via text message' },
    { value: 'both', label: 'Both', description: 'Email and SMS' },
];

const channelBadgeClass: Record<Channel, string> = {
    email: 'bg-blue-100 text-blue-700 hover:bg-blue-100',
    sms: 'bg-purple-100 text-purple-700 hover:bg-purple-100',
    both: 'bg-teal-100 text-teal-700 hover:bg-teal-100',
};

export default function QuickSend({ recentlySent }: Props) {
    const { flash } = usePage<{ flash: { success?: string } }>().props;
    const [form, setForm] = useState({ name: '', email: '', channel: 'email' as Channel });
    const [processing, setProcessing] = useState(false);
    const [showSuccess, setShowSuccess] = useState(false);

    useEffect(() => {
        if (flash?.success) {
            // eslint-disable-next-line react-hooks/set-state-in-effect
            setShowSuccess(true);
            setForm({ name: '', email: '', channel: 'email' });
            const timer = setTimeout(() => setShowSuccess(false), 4000);
            return () => clearTimeout(timer);
        }
    }, [flash]);

    const handleSend = () => {
        setProcessing(true);
        router.post(
            quickSendSend().url,
            form,
            {
                preserveScroll: true,
                onFinish: () => setProcessing(false),
            },
        );
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Quick Send" />
            <div className="flex h-full flex-1 flex-col gap-6 p-4 md:p-6">
                <div>
                    <h1 className="text-2xl font-bold text-foreground">Quick Send</h1>
                    <p className="mt-1 text-sm text-muted-foreground">Send a review request to a customer instantly</p>
                </div>

                {showSuccess && (
                    <div className="flex items-center gap-3 rounded-lg border border-teal-200 bg-teal-50 p-4">
                        <svg className="h-5 w-5 text-teal-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <span className="text-sm font-medium text-teal-700">Review request sent successfully!</span>
                    </div>
                )}

                <div className="grid gap-6 lg:grid-cols-2">
                    {/* Send Form */}
                    <Card>
                        <CardHeader>
                            <CardTitle className="text-base font-semibold">Send Review Request</CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-5">
                            <div className="space-y-2">
                                <Label htmlFor="name">Customer Name</Label>
                                <Input
                                    id="name"
                                    placeholder="Jane Smith"
                                    value={form.name}
                                    onChange={(e) => setForm({ ...form, name: e.target.value })}
                                />
                            </div>

                            <div className="space-y-2">
                                <Label htmlFor="email">Email Address</Label>
                                <Input
                                    id="email"
                                    type="email"
                                    placeholder="jane@example.com"
                                    value={form.email}
                                    onChange={(e) => setForm({ ...form, email: e.target.value })}
                                />
                            </div>

                            <div className="space-y-2">
                                <Label>Send Via</Label>
                                <div className="grid grid-cols-3 gap-2">
                                    {channelOptions.map((option) => (
                                        <button
                                            key={option.value}
                                            type="button"
                                            onClick={() => setForm({ ...form, channel: option.value })}
                                            className={`rounded-lg border-2 p-3 text-center transition ${
                                                form.channel === option.value
                                                    ? 'border-teal-600 bg-teal-50'
                                                    : 'border-border hover:border-border'
                                            }`}
                                        >
                                            <div className={`text-sm font-medium ${form.channel === option.value ? 'text-teal-700' : 'text-foreground'}`}>
                                                {option.label}
                                            </div>
                                            <div className="mt-0.5 text-xs text-muted-foreground">{option.description}</div>
                                        </button>
                                    ))}
                                </div>
                            </div>

                            <Button
                                className="w-full bg-teal-600 hover:bg-teal-700 text-white"
                                onClick={handleSend}
                                disabled={processing || !form.name || !form.email}
                            >
                                {processing ? 'Sending...' : 'Send Review Request'}
                            </Button>
                        </CardContent>
                    </Card>

                    {/* Recently Sent */}
                    <Card>
                        <CardHeader>
                            <CardTitle className="text-base font-semibold">Recently Sent</CardTitle>
                        </CardHeader>
                        <CardContent>
                            {recentlySent.length === 0 ? (
                                <div className="flex flex-col items-center justify-center py-8 text-center">
                                    <svg className="mb-3 h-10 w-10 text-muted-foreground" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75" />
                                    </svg>
                                    <p className="text-sm text-muted-foreground">No requests sent yet</p>
                                </div>
                            ) : (
                                <div className="space-y-3">
                                    {recentlySent.map((item) => (
                                        <div key={item.id} className="flex items-center justify-between rounded-lg border border-border p-3">
                                            <div className="min-w-0">
                                                <div className="font-medium text-foreground">{item.customer_name}</div>
                                                <div className="text-sm text-muted-foreground">{item.customer_email}</div>
                                            </div>
                                            <div className="ml-3 flex flex-col items-end gap-1">
                                                <Badge className={`text-xs capitalize ${channelBadgeClass[item.channel]}`}>
                                                    {item.channel}
                                                </Badge>
                                                <span className="text-xs text-muted-foreground">
                                                    {new Date(item.sent_at).toLocaleDateString()}
                                                </span>
                                            </div>
                                        </div>
                                    ))}
                                </div>
                            )}
                        </CardContent>
                    </Card>
                </div>
            </div>
        </AppLayout>
    );
}
