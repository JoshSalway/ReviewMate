import { Head, router } from '@inertiajs/react';
import { useState } from 'react';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { Textarea } from '@/components/ui/textarea';
import AppLayout from '@/layouts/app-layout';
import { index as templatesIndex, update as templatesUpdate } from '@/routes/templates';
import type { BreadcrumbItem } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Templates',
        href: templatesIndex(),
    },
];

type TemplateType = 'request' | 'follow_up' | 'sms';

interface Template {
    id: number;
    type: TemplateType;
    subject: string;
    body: string;
}

interface Props {
    business: {
        id: number;
        name: string;
        type: string;
    };
    templates: Record<TemplateType, Template>;
}

const variables = [
    { key: '{customer_name}', label: 'Customer Name' },
    { key: '{business_name}', label: 'Business Name' },
    { key: '{review_link}', label: 'Review Link' },
    { key: '{owner_name}', label: 'Owner Name' },
];

const tabConfig: { value: TemplateType; label: string }[] = [
    { value: 'request', label: 'Request Email' },
    { value: 'follow_up', label: 'Follow-up Email' },
    { value: 'sms', label: 'SMS' },
];

function renderPreview(body: string) {
    return body
        .replace(/{customer_name}/g, '<strong>Jane Smith</strong>')
        .replace(/{business_name}/g, '<strong>Your Business</strong>')
        .replace(/{review_link}/g, '<a href="#" style="color:#0d9488;text-decoration:underline;">Leave a Review</a>')
        .replace(/{owner_name}/g, '<strong>John</strong>')
        .replace(/\n/g, '<br/>');
}

function TemplateEditor({
    template,
    business,
    isSms,
}: {
    template: Template;
    business: Props['business'];
    isSms: boolean;
}) {
    const [subject, setSubject] = useState(template.subject);
    const [body, setBody] = useState(template.body);
    const [saving, setSaving] = useState(false);
    const [saved, setSaved] = useState(false);

    const insertVariable = (variable: string) => {
        setBody((prev) => prev + variable);
    };

    const handleSave = () => {
        setSaving(true);
        router.put(
            templatesUpdate(template.id).url,
            { subject, body },
            {
                preserveScroll: true,
                onSuccess: () => {
                    setSaved(true);
                    setTimeout(() => setSaved(false), 2000);
                },
                onFinish: () => setSaving(false),
            },
        );
    };

    return (
        <div className="grid gap-6 lg:grid-cols-2">
            {/* Editor */}
            <div className="space-y-4">
                {!isSms && (
                    <div className="space-y-2">
                        <Label htmlFor="subject">Subject Line</Label>
                        <Input
                            id="subject"
                            value={subject}
                            onChange={(e) => setSubject(e.target.value)}
                            placeholder="Email subject..."
                        />
                    </div>
                )}

                <div className="space-y-2">
                    <Label htmlFor="body">{isSms ? 'SMS Message' : 'Email Body'}</Label>
                    <Textarea
                        id="body"
                        value={body}
                        onChange={(e) => setBody(e.target.value)}
                        rows={isSms ? 6 : 12}
                        placeholder={isSms ? 'SMS message...' : 'Email body...'}
                        className="font-mono text-sm"
                    />
                </div>

                <div className="space-y-2">
                    <Label className="text-xs text-gray-500">Insert Variable</Label>
                    <div className="flex flex-wrap gap-2">
                        {variables.map((v) => (
                            <button
                                key={v.key}
                                type="button"
                                onClick={() => insertVariable(v.key)}
                                className="rounded-full border border-teal-200 bg-teal-50 px-3 py-1 text-xs font-medium text-teal-700 transition hover:bg-teal-100"
                            >
                                {v.key}
                            </button>
                        ))}
                    </div>
                </div>

                <div className="flex items-center gap-3">
                    <Button
                        className="bg-teal-600 hover:bg-teal-700 text-white"
                        onClick={handleSave}
                        disabled={saving}
                    >
                        {saving ? 'Saving...' : 'Save Template'}
                    </Button>
                    {saved && <span className="text-sm text-teal-600">Saved!</span>}
                </div>
            </div>

            {/* Preview */}
            <div>
                <Card className="overflow-hidden">
                    <CardHeader className="border-b bg-gray-50 py-3">
                        <div className="flex items-center gap-2">
                            <div className="flex gap-1">
                                <div className="h-3 w-3 rounded-full bg-red-400" />
                                <div className="h-3 w-3 rounded-full bg-yellow-400" />
                                <div className="h-3 w-3 rounded-full bg-green-400" />
                            </div>
                            <span className="text-xs text-gray-500">Preview</span>
                        </div>
                        {!isSms && subject && (
                            <div className="mt-2 text-sm">
                                <span className="text-gray-500">Subject: </span>
                                <span className="font-medium"
                                    dangerouslySetInnerHTML={{ __html: renderPreview(subject) }}
                                />
                            </div>
                        )}
                    </CardHeader>
                    <CardContent className="p-6">
                        {isSms ? (
                            <div className="flex justify-end">
                                <div className="max-w-xs rounded-2xl rounded-br-sm bg-teal-600 px-4 py-3 text-sm text-white"
                                    dangerouslySetInnerHTML={{ __html: renderPreview(body) }}
                                />
                            </div>
                        ) : (
                            <div className="max-w-lg space-y-3">
                                <div className="text-sm text-gray-500">
                                    From: <span className="font-medium text-gray-800">{business.name}</span>
                                </div>
                                <div className="text-sm text-gray-500">
                                    To: <span className="font-medium text-gray-800">Jane Smith</span>
                                </div>
                                <hr />
                                <div
                                    className="prose prose-sm text-gray-700 leading-relaxed"
                                    dangerouslySetInnerHTML={{ __html: renderPreview(body) }}
                                />
                            </div>
                        )}
                    </CardContent>
                </Card>
            </div>
        </div>
    );
}

export default function TemplatesIndex({ business, templates }: Props) {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Templates" />
            <div className="flex h-full flex-1 flex-col gap-6 p-4 md:p-6">
                <div>
                    <h1 className="text-2xl font-bold text-gray-900">Email Templates</h1>
                    <p className="mt-1 text-sm text-gray-500">Customise the messages sent to your customers</p>
                </div>

                <Tabs defaultValue="request">
                    <TabsList>
                        {tabConfig.map((tab) => (
                            <TabsTrigger key={tab.value} value={tab.value}>
                                {tab.label}
                            </TabsTrigger>
                        ))}
                    </TabsList>

                    {tabConfig.map((tab) => (
                        <TabsContent key={tab.value} value={tab.value} className="mt-6">
                            {templates[tab.value] ? (
                                <TemplateEditor
                                    template={templates[tab.value]}
                                    business={business}
                                    isSms={tab.value === 'sms'}
                                />
                            ) : (
                                <Card>
                                    <CardContent className="py-12 text-center text-gray-500">
                                        No template found for this type.
                                    </CardContent>
                                </Card>
                            )}
                        </TabsContent>
                    ))}
                </Tabs>
            </div>
        </AppLayout>
    );
}
