import { Head, router } from '@inertiajs/react';
import { useState } from 'react';
import AppLayout from '@/layouts/app-layout';
import { business as businessSettingsRoute } from '@/routes/settings';
import { update as businessUpdate } from '@/routes/settings/business';
import { connect as googleConnect, disconnect as googleDisconnect } from '@/routes/google';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
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
    };
}

export default function BusinessSettings({ business }: Props) {
    const [form, setForm] = useState({
        name: business.name,
        type: business.type,
        google_place_id: business.google_place_id ?? '',
        owner_name: business.owner_name ?? '',
        phone: business.phone ?? '',
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
                    <h1 className="text-2xl font-bold text-gray-900">Business Settings</h1>
                    <p className="mt-1 text-sm text-gray-500">Manage your business details and Google Business connection</p>
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
                                    <Badge className="bg-gray-100 text-gray-500 hover:bg-gray-100">Not connected</Badge>
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
                                    <p className="text-sm text-gray-500">
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
                                        <p className="text-xs text-gray-500">
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
