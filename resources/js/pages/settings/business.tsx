import { Head, router } from '@inertiajs/react';
import { useState } from 'react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Switch } from '@/components/ui/switch';
import AppLayout from '@/layouts/app-layout';
import { connect as googleConnect, disconnect as googleDisconnect } from '@/routes/google';
import { business as businessSettingsRoute } from '@/routes/settings';
import { update as businessUpdate } from '@/routes/settings/business';
import type { BreadcrumbItem } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Business Settings',
        href: businessSettingsRoute(),
    },
];

const businessTypes = [
    { value: 'tradie', label: 'Tradie' },
    { value: 'cafe', label: 'Cafe / Restaurant' },
    { value: 'salon', label: 'Salon / Barber' },
    { value: 'healthcare', label: 'Healthcare' },
    { value: 'real_estate', label: 'Real Estate' },
    { value: 'retail', label: 'Retail' },
    { value: 'pet_services', label: 'Pet Services' },
    { value: 'fitness', label: 'Fitness' },
    { value: 'other', label: 'Other' },
];

interface Props {
    business: {
        id: number;
        name: string;
        type: string;
        google_place_id: string | null;
        owner_name: string | null;
        phone: string | null;
        is_google_connected: boolean;
        google_account_id: string | null;
        google_location_id: string | null;
        facebook_page_url: string | null;
        follow_up_enabled: boolean;
        follow_up_days: number;
        follow_up_channel: string;
        timezone: string;
    };
    isProPlan: boolean;
}

export default function BusinessSettings({ business, isProPlan }: Props) {
    const [form, setForm] = useState({
        name: business.name,
        type: business.type,
        google_place_id: business.google_place_id ?? '',
        owner_name: business.owner_name ?? '',
        phone: business.phone ?? '',
        facebook_page_url: business.facebook_page_url ?? '',
        follow_up_enabled: business.follow_up_enabled,
        follow_up_days: business.follow_up_days,
        follow_up_channel: business.follow_up_channel,
        timezone: business.timezone ?? 'Australia/Sydney',
    });
    const [processing, setProcessing] = useState(false);
    const [saved, setSaved] = useState(false);

    const handleSave = () => {
        setProcessing(true);
        router.put(
            businessUpdate().url,
            form,
            {
                preserveScroll: true,
                onSuccess: () => {
                    setSaved(true);
                    setTimeout(() => setSaved(false), 2000);
                },
                onFinish: () => setProcessing(false),
            },
        );
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Business Settings" />
            <div className="flex h-full flex-1 flex-col gap-6 p-4 md:p-6">
                <div>
                    <h1 className="text-2xl font-bold text-foreground">Business Settings</h1>
                    <p className="mt-1 text-sm text-muted-foreground">Manage your business details and Google Business connection</p>
                </div>

                <div className="max-w-2xl space-y-6">
                    {/* Business Details */}
                    <Card>
                        <CardHeader>
                            <CardTitle className="text-base font-semibold">Business Details</CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-5">
                            <div className="grid gap-4 sm:grid-cols-2">
                                <div className="space-y-2">
                                    <Label htmlFor="business-name">Business Name</Label>
                                    <Input
                                        id="business-name"
                                        value={form.name}
                                        onChange={(e) => setForm({ ...form, name: e.target.value })}
                                        placeholder="e.g. Smith's Plumbing"
                                    />
                                </div>

                                <div className="space-y-2">
                                    <Label htmlFor="owner-name">Owner Name</Label>
                                    <Input
                                        id="owner-name"
                                        value={form.owner_name}
                                        onChange={(e) => setForm({ ...form, owner_name: e.target.value })}
                                        placeholder="e.g. John Smith"
                                    />
                                </div>
                            </div>

                            <div className="space-y-2">
                                <Label htmlFor="timezone">Business Timezone</Label>
                                <Select
                                    value={form.timezone}
                                    onValueChange={(value) => setForm({ ...form, timezone: value })}
                                >
                                    <SelectTrigger id="timezone">
                                        <SelectValue placeholder="Select timezone" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="Australia/Sydney">Sydney / Melbourne (AEST/AEDT)</SelectItem>
                                        <SelectItem value="Australia/Brisbane">Brisbane (AEST, no DST)</SelectItem>
                                        <SelectItem value="Australia/Adelaide">Adelaide (ACST/ACDT)</SelectItem>
                                        <SelectItem value="Australia/Perth">Perth (AWST)</SelectItem>
                                        <SelectItem value="Australia/Darwin">Darwin (ACST, no DST)</SelectItem>
                                        <SelectItem value="Australia/Hobart">Hobart (AEST/AEDT)</SelectItem>
                                        <SelectItem value="Pacific/Auckland">Auckland (NZST/NZDT)</SelectItem>
                                        <SelectItem value="Asia/Singapore">Singapore (SGT)</SelectItem>
                                        <SelectItem value="America/New_York">New York (EST/EDT)</SelectItem>
                                        <SelectItem value="America/Chicago">Chicago (CST/CDT)</SelectItem>
                                        <SelectItem value="America/Denver">Denver (MST/MDT)</SelectItem>
                                        <SelectItem value="America/Los_Angeles">Los Angeles (PST/PDT)</SelectItem>
                                        <SelectItem value="Europe/London">London (GMT/BST)</SelectItem>
                                        <SelectItem value="UTC">UTC</SelectItem>
                                    </SelectContent>
                                </Select>
                                <p className="text-xs text-muted-foreground">Used to schedule follow-ups and auto-replies at the right local time.</p>
                            </div>

                            <div className="grid gap-4 sm:grid-cols-2">
                                <div className="space-y-2">
                                    <Label htmlFor="business-type">Business Type</Label>
                                    <Select
                                        value={form.type}
                                        onValueChange={(value) => setForm({ ...form, type: value })}
                                    >
                                        <SelectTrigger id="business-type">
                                            <SelectValue placeholder="Select type" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {businessTypes.map((type) => (
                                                <SelectItem key={type.value} value={type.value}>
                                                    {type.label}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                </div>

                                <div className="space-y-2">
                                    <Label htmlFor="phone">Phone Number</Label>
                                    <Input
                                        id="phone"
                                        type="tel"
                                        value={form.phone}
                                        onChange={(e) => setForm({ ...form, phone: e.target.value })}
                                        placeholder="e.g. 0400 000 000"
                                    />
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    {/* Google Business */}
                    <Card>
                        <CardHeader>
                            <div className="flex items-center justify-between">
                                <CardTitle className="text-base font-semibold">Google Business Profile</CardTitle>
                                {business.is_google_connected ? (
                                    <Badge className="bg-green-100 text-green-700 hover:bg-green-100">Connected</Badge>
                                ) : (
                                    <Badge className="bg-muted text-muted-foreground hover:bg-muted">Not connected</Badge>
                                )}
                            </div>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            {business.is_google_connected ? (
                                <div className="space-y-3">
                                    <div className="rounded-lg border border-green-200 bg-green-50 p-4">
                                        <p className="text-sm font-medium text-green-800">Google Business Profile is connected.</p>
                                        {business.google_location_id && (
                                            <p className="mt-1 font-mono text-xs text-green-700">{business.google_location_id}</p>
                                        )}
                                        <p className="mt-1 text-xs text-green-600">Reviews will sync automatically every 2 hours.</p>
                                    </div>
                                    <Button
                                        variant="outline"
                                        className="border-red-200 text-red-600 hover:bg-red-50"
                                        onClick={() => router.delete(googleDisconnect().url)}
                                    >
                                        Disconnect Google
                                    </Button>
                                </div>
                            ) : (
                                <div className="space-y-3">
                                    <p className="text-sm text-muted-foreground">
                                        Connect your Google Business Profile to automatically sync reviews and post replies directly from ReviewMate.
                                    </p>
                                    <a href={googleConnect().url}>
                                        <Button className="bg-teal-600 hover:bg-teal-700 text-white">
                                            Connect Google Business Profile
                                        </Button>
                                    </a>
                                </div>
                            )}

                            <div className="border-t pt-4">
                                <div className="flex items-start gap-3 rounded-lg bg-blue-50 p-4">
                                    <svg className="mt-0.5 h-5 w-5 shrink-0 text-blue-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    <p className="text-sm text-blue-700">
                                        Your Google Place ID is used to generate direct review request links.
                                        Find yours at <a href="https://developers.google.com/maps/documentation/places/web-service/place-id" target="_blank" rel="noopener noreferrer" className="font-medium underline">Google's Place ID Finder</a>.
                                    </p>
                                </div>

                                <div className="mt-4 space-y-2">
                                    <Label htmlFor="place-id">Google Place ID</Label>
                                    <Input
                                        id="place-id"
                                        value={form.google_place_id}
                                        onChange={(e) => setForm({ ...form, google_place_id: e.target.value })}
                                        placeholder="e.g. ChIJN1t_tDeuEmsRUsoyG83frY4"
                                        className="font-mono text-sm"
                                    />
                                    {form.google_place_id && (
                                        <p className="text-xs text-muted-foreground">
                                            Review link:{' '}
                                            <a
                                                href={`https://search.google.com/local/writereview?placeid=${form.google_place_id}`}
                                                target="_blank"
                                                rel="noopener noreferrer"
                                                className="text-teal-600 underline"
                                            >
                                                Preview link
                                            </a>
                                        </p>
                                    )}
                                </div>
                            </div>

                            <div className="border-t pt-4">
                                <div className="space-y-2">
                                    <Label htmlFor="facebook-page-url">Facebook Page URL</Label>
                                    <p className="text-xs text-muted-foreground">
                                        Optional. When set, review requests will include a link to your Facebook Reviews page
                                        alongside Google.
                                    </p>
                                    <Input
                                        id="facebook-page-url"
                                        type="url"
                                        value={form.facebook_page_url}
                                        onChange={(e) => setForm({ ...form, facebook_page_url: e.target.value })}
                                        placeholder="e.g. https://www.facebook.com/yourbusiness"
                                    />
                                    {form.facebook_page_url && (
                                        <p className="text-xs text-muted-foreground">
                                            Facebook review link:{' '}
                                            <a
                                                href={`${form.facebook_page_url.replace(/\/$/, '')}/reviews`}
                                                target="_blank"
                                                rel="noopener noreferrer"
                                                className="text-teal-600 underline"
                                            >
                                                Preview link
                                            </a>
                                        </p>
                                    )}
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    {/* Follow-up Reminder Settings */}
                    <Card>
                        <CardHeader>
                            <div className="flex items-center justify-between">
                                <CardTitle className="text-base font-semibold">Follow-up Reminder</CardTitle>
                                {!isProPlan && (
                                    <Badge className="bg-amber-100 text-amber-700 hover:bg-amber-100">Pro Plan</Badge>
                                )}
                            </div>
                        </CardHeader>
                        <CardContent className="space-y-5">
                            {!isProPlan ? (
                                <p className="text-sm text-muted-foreground">
                                    Upgrade to Pro to automatically send a follow-up reminder to customers who haven't reviewed yet.
                                </p>
                            ) : (
                                <>
                                    <div className="flex items-center justify-between">
                                        <div>
                                            <Label htmlFor="follow-up-enabled" className="font-medium">Send follow-up reminder</Label>
                                            <p className="text-xs text-muted-foreground mt-0.5">Automatically remind customers who haven't reviewed yet</p>
                                        </div>
                                        <Switch
                                            id="follow-up-enabled"
                                            checked={form.follow_up_enabled}
                                            onCheckedChange={(checked) => setForm({ ...form, follow_up_enabled: checked })}
                                        />
                                    </div>

                                    {form.follow_up_enabled && (
                                        <div className="grid gap-4 sm:grid-cols-2">
                                            <div className="space-y-2">
                                                <Label htmlFor="follow-up-days">Days after initial send</Label>
                                                <Select
                                                    value={String(form.follow_up_days)}
                                                    onValueChange={(value) => setForm({ ...form, follow_up_days: Number(value) })}
                                                >
                                                    <SelectTrigger id="follow-up-days">
                                                        <SelectValue />
                                                    </SelectTrigger>
                                                    <SelectContent>
                                                        <SelectItem value="2">2 days</SelectItem>
                                                        <SelectItem value="3">3 days</SelectItem>
                                                        <SelectItem value="5">5 days</SelectItem>
                                                        <SelectItem value="7">7 days</SelectItem>
                                                    </SelectContent>
                                                </Select>
                                            </div>

                                            <div className="space-y-2">
                                                <Label htmlFor="follow-up-channel">Follow-up channel</Label>
                                                <Select
                                                    value={form.follow_up_channel}
                                                    onValueChange={(value) => setForm({ ...form, follow_up_channel: value })}
                                                >
                                                    <SelectTrigger id="follow-up-channel">
                                                        <SelectValue />
                                                    </SelectTrigger>
                                                    <SelectContent>
                                                        <SelectItem value="same">Same as initial</SelectItem>
                                                        <SelectItem value="sms">SMS only</SelectItem>
                                                        <SelectItem value="email">Email only</SelectItem>
                                                    </SelectContent>
                                                </Select>
                                            </div>
                                        </div>
                                    )}
                                </>
                            )}
                        </CardContent>
                    </Card>

                    {/* Save */}
                    <div className="flex items-center gap-3">
                        <Button
                            className="bg-teal-600 hover:bg-teal-700 text-white"
                            onClick={handleSave}
                            disabled={processing}
                        >
                            {processing ? 'Saving...' : 'Save Changes'}
                        </Button>
                        {saved && (
                            <span className="text-sm font-medium text-teal-600">Changes saved!</span>
                        )}
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}
