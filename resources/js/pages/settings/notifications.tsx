import { Head, router } from '@inertiajs/react';
import { useState } from 'react';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Label } from '@/components/ui/label';
import { Switch } from '@/components/ui/switch';
import AppLayout from '@/layouts/app-layout';
import { notifications as notificationsRoute } from '@/routes/settings';
import { update as notificationsUpdate } from '@/routes/settings/notifications';
import type { BreadcrumbItem } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Notification Settings', href: notificationsRoute() },
];

interface Preferences {
    weekly_digest: boolean;
    new_review_alert: boolean;
    negative_review_alert: boolean;
}

interface Props {
    preferences: Preferences;
}

export default function NotificationSettings({ preferences }: Props) {
    const [form, setForm] = useState<Preferences>(preferences);
    const [saving, setSaving] = useState(false);

    const handleSave = () => {
        setSaving(true);
        router.put(notificationsUpdate().url, form as unknown as Record<string, boolean>, {
            preserveScroll: true,
            onFinish: () => setSaving(false),
        });
    };

    const toggle = (key: keyof Preferences) =>
        setForm((prev) => ({ ...prev, [key]: !prev[key] }));

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Notification Settings" />
            <div className="flex h-full flex-1 flex-col gap-6 p-4 md:p-6 max-w-2xl">
                <div>
                    <h1 className="text-2xl font-bold text-foreground">Notification Settings</h1>
                    <p className="mt-1 text-sm text-muted-foreground">Choose which emails ReviewMate sends you.</p>
                </div>

                <Card>
                    <CardHeader>
                        <CardTitle className="text-base">Email Notifications</CardTitle>
                        <CardDescription>Manage the emails you receive from ReviewMate.</CardDescription>
                    </CardHeader>
                    <CardContent className="space-y-6">
                        <div className="flex items-center justify-between gap-4">
                            <div>
                                <Label htmlFor="weekly-digest" className="text-sm font-medium">
                                    Weekly digest
                                </Label>
                                <p className="text-sm text-muted-foreground mt-0.5">
                                    A summary of your reviews, ratings, and pending replies every Monday.
                                </p>
                            </div>
                            <Switch
                                id="weekly-digest"
                                checked={form.weekly_digest}
                                onCheckedChange={() => toggle('weekly_digest')}
                            />
                        </div>

                        <div className="border-t pt-5 flex items-center justify-between gap-4">
                            <div>
                                <Label htmlFor="new-review-alert" className="text-sm font-medium">
                                    New review alerts
                                </Label>
                                <p className="text-sm text-muted-foreground mt-0.5">
                                    Get notified by email when a new Google review is synced for your business.
                                </p>
                            </div>
                            <Switch
                                id="new-review-alert"
                                checked={form.new_review_alert}
                                onCheckedChange={() => toggle('new_review_alert')}
                            />
                        </div>

                        <div className="border-t pt-5 flex items-center justify-between gap-4">
                            <div>
                                <Label htmlFor="negative-review-alert" className="text-sm font-medium">
                                    Negative review alerts
                                </Label>
                                <p className="text-sm text-muted-foreground mt-0.5">
                                    Get an immediate alert when a 1 or 2-star review arrives so you can respond quickly.
                                </p>
                            </div>
                            <Switch
                                id="negative-review-alert"
                                checked={form.negative_review_alert}
                                onCheckedChange={() => toggle('negative_review_alert')}
                            />
                        </div>
                    </CardContent>
                </Card>

                <div className="flex justify-end">
                    <Button
                        className="bg-teal-600 hover:bg-teal-700 text-white"
                        onClick={handleSave}
                        disabled={saving}
                    >
                        {saving ? 'Saving...' : 'Save preferences'}
                    </Button>
                </div>
            </div>
        </AppLayout>
    );
}
