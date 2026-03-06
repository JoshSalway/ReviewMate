import { Head, router } from '@inertiajs/react';
import { useState } from 'react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
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
import type { BreadcrumbItem } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Widget Settings', href: '/settings/widget' },
];

interface Props {
    business: {
        slug: string | null;
        widget_enabled: boolean;
        widget_min_rating: number;
        widget_max_reviews: number;
        widget_theme: string;
    };
    embedCode: string | null;
    previewUrl: string | null;
}

export default function WidgetSettings({ business, embedCode, previewUrl }: Props) {
    const [form, setForm] = useState({
        widget_enabled: business.widget_enabled,
        widget_min_rating: business.widget_min_rating,
        widget_max_reviews: business.widget_max_reviews,
        widget_theme: business.widget_theme,
    });
    const [processing, setProcessing] = useState(false);
    const [saved, setSaved] = useState(false);
    const [copied, setCopied] = useState(false);

    const handleSave = () => {
        setProcessing(true);
        router.put('/settings/widget', form, {
            preserveScroll: true,
            onSuccess: () => {
                setSaved(true);
                setTimeout(() => setSaved(false), 2000);
            },
            onFinish: () => setProcessing(false),
        });
    };

    const handleCopy = () => {
        if (embedCode) {
            navigator.clipboard.writeText(embedCode).then(() => {
                setCopied(true);
                setTimeout(() => setCopied(false), 2000);
            });
        }
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Widget Settings" />
            <div className="flex h-full flex-1 flex-col gap-6 p-4 md:p-6">
                <div>
                    <h1 className="text-2xl font-bold text-gray-900">Review Widget</h1>
                    <p className="mt-1 text-sm text-gray-500">
                        Embed your Google reviews on your website with one line of code.
                    </p>
                </div>

                <div className="max-w-2xl space-y-6">
                    {/* Embed Code */}
                    <Card>
                        <CardHeader>
                            <CardTitle className="text-base font-semibold">Embed Code</CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            <p className="text-sm text-gray-600">
                                Paste this single line of code anywhere on your website where you want the review widget to appear.
                            </p>
                            {embedCode ? (
                                <div className="space-y-2">
                                    <div className="flex items-center gap-2">
                                        <code className="flex-1 rounded-md border border-gray-200 bg-gray-50 p-3 font-mono text-xs text-gray-800 break-all">
                                            {embedCode}
                                        </code>
                                        <Button
                                            variant="outline"
                                            size="sm"
                                            onClick={handleCopy}
                                            className="shrink-0"
                                        >
                                            {copied ? 'Copied!' : 'Copy'}
                                        </Button>
                                    </div>
                                    {previewUrl && (
                                        <p className="text-xs text-gray-500">
                                            API preview:{' '}
                                            <a
                                                href={previewUrl}
                                                target="_blank"
                                                rel="noopener noreferrer"
                                                className="text-teal-600 underline"
                                            >
                                                {previewUrl}
                                            </a>
                                        </p>
                                    )}
                                </div>
                            ) : (
                                <p className="text-sm text-gray-500 italic">
                                    Complete business setup to generate your embed code.
                                </p>
                            )}
                        </CardContent>
                    </Card>

                    {/* Widget Settings */}
                    <Card>
                        <CardHeader>
                            <CardTitle className="text-base font-semibold">Widget Settings</CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-5">
                            <div className="flex items-center justify-between">
                                <div>
                                    <Label htmlFor="widget-enabled" className="font-medium">Enable widget</Label>
                                    <p className="text-xs text-gray-500 mt-0.5">Allow the public API to serve your reviews</p>
                                </div>
                                <Switch
                                    id="widget-enabled"
                                    checked={form.widget_enabled}
                                    onCheckedChange={(checked) => setForm({ ...form, widget_enabled: checked })}
                                />
                            </div>

                            <div className="grid gap-4 sm:grid-cols-3">
                                <div className="space-y-2">
                                    <Label htmlFor="min-rating">Min rating to show</Label>
                                    <Select
                                        value={String(form.widget_min_rating)}
                                        onValueChange={(v) => setForm({ ...form, widget_min_rating: Number(v) })}
                                    >
                                        <SelectTrigger id="min-rating">
                                            <SelectValue />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="3">3★ and above</SelectItem>
                                            <SelectItem value="4">4★ and above</SelectItem>
                                            <SelectItem value="5">5★ only</SelectItem>
                                        </SelectContent>
                                    </Select>
                                </div>

                                <div className="space-y-2">
                                    <Label htmlFor="max-reviews">Number of reviews</Label>
                                    <Select
                                        value={String(form.widget_max_reviews)}
                                        onValueChange={(v) => setForm({ ...form, widget_max_reviews: Number(v) })}
                                    >
                                        <SelectTrigger id="max-reviews">
                                            <SelectValue />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="3">3 reviews</SelectItem>
                                            <SelectItem value="6">6 reviews</SelectItem>
                                            <SelectItem value="9">9 reviews</SelectItem>
                                        </SelectContent>
                                    </Select>
                                </div>

                                <div className="space-y-2">
                                    <Label htmlFor="widget-theme">Theme</Label>
                                    <Select
                                        value={form.widget_theme}
                                        onValueChange={(v) => setForm({ ...form, widget_theme: v })}
                                    >
                                        <SelectTrigger id="widget-theme">
                                            <SelectValue />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="light">Light</SelectItem>
                                            <SelectItem value="dark">Dark</SelectItem>
                                        </SelectContent>
                                    </Select>
                                </div>
                            </div>

                            <div className="flex items-center gap-3 pt-2">
                                <Button
                                    className="bg-teal-600 hover:bg-teal-700 text-white"
                                    onClick={handleSave}
                                    disabled={processing}
                                >
                                    {processing ? 'Saving...' : 'Save Settings'}
                                </Button>
                                {saved && (
                                    <span className="text-sm font-medium text-teal-600">Settings saved!</span>
                                )}
                            </div>
                        </CardContent>
                    </Card>

                    {/* How it works */}
                    <Card>
                        <CardHeader>
                            <CardTitle className="text-base font-semibold">How it works</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <ol className="space-y-2 text-sm text-gray-600 list-decimal list-inside">
                                <li>Paste the embed code into your website's HTML</li>
                                <li>The widget automatically loads your latest Google reviews</li>
                                <li>It updates in real-time as new reviews come in — no maintenance needed</li>
                                <li>Works with any website: WordPress, Squarespace, Wix, custom HTML</li>
                            </ol>
                            <div className="mt-4 flex items-center gap-2">
                                <Badge className="bg-teal-50 text-teal-700 hover:bg-teal-50 text-xs">Free on all plans</Badge>
                                <span className="text-xs text-gray-500">The widget is available to all ReviewMate users</span>
                            </div>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </AppLayout>
    );
}
