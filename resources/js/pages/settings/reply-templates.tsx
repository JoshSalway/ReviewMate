import { Head, router } from '@inertiajs/react';
import { useState } from 'react';
import AppLayout from '@/layouts/app-layout';
import { replyTemplates as replyTemplatesRoute } from '@/routes/settings';
import {
    store as storeTemplate,
    update as updateTemplate,
    destroy as destroyTemplate,
} from '@/routes/settings/reply-templates';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
    DialogFooter,
} from '@/components/ui/dialog';
import type { BreadcrumbItem } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Reply Templates', href: replyTemplatesRoute() },
];

interface ReplyTemplate {
    id: number;
    name: string;
    body: string;
}

interface Props {
    templates: ReplyTemplate[];
}

const emptyForm = { name: '', body: '' };

export default function ReplyTemplates({ templates }: Props) {
    const [showDialog, setShowDialog] = useState(false);
    const [editing, setEditing] = useState<ReplyTemplate | null>(null);
    const [form, setForm] = useState(emptyForm);
    const [deleteConfirm, setDeleteConfirm] = useState<number | null>(null);
    const [processing, setProcessing] = useState(false);

    const openCreate = () => {
        setEditing(null);
        setForm(emptyForm);
        setShowDialog(true);
    };

    const openEdit = (t: ReplyTemplate) => {
        setEditing(t);
        setForm({ name: t.name, body: t.body });
        setShowDialog(true);
    };

    const handleSave = () => {
        setProcessing(true);
        if (editing) {
            router.put(updateTemplate(editing.id).url, form, {
                preserveScroll: true,
                onSuccess: () => setShowDialog(false),
                onFinish: () => setProcessing(false),
            });
        } else {
            router.post(storeTemplate().url, form, {
                preserveScroll: true,
                onSuccess: () => { setShowDialog(false); setForm(emptyForm); },
                onFinish: () => setProcessing(false),
            });
        }
    };

    const handleDelete = (id: number) => {
        router.delete(destroyTemplate(id).url, {
            preserveScroll: true,
            onSuccess: () => setDeleteConfirm(null),
        });
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Reply Templates" />
            <div className="flex h-full flex-1 flex-col gap-6 p-4 md:p-6 max-w-3xl">
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-2xl font-bold text-gray-900">Reply Templates</h1>
                        <p className="mt-1 text-sm text-gray-500">
                            Save reusable reply snippets. Use them on any review to pre-fill your response.
                        </p>
                    </div>
                    <Button className="bg-teal-600 hover:bg-teal-700 text-white" onClick={openCreate}>
                        + New Template
                    </Button>
                </div>

                {templates.length === 0 ? (
                    <Card>
                        <CardContent className="flex flex-col items-center justify-center py-16 text-center">
                            <div className="mb-4 flex h-14 w-14 items-center justify-center rounded-full bg-gray-100">
                                <svg className="h-7 w-7 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M7.5 8.25h9m-9 3H12m-9.75 1.51c0 1.6 1.123 2.994 2.707 3.227 1.129.166 2.27.293 3.423.379.35.026.67.21.865.501L12 21l2.755-4.133a1.14 1.14 0 01.865-.501 48.172 48.172 0 003.423-.379c1.584-.233 2.707-1.626 2.707-3.228V6.741c0-1.602-1.123-2.995-2.707-3.228A48.394 48.394 0 0012 3c-2.392 0-4.744.175-7.043.513C3.373 3.746 2.25 5.14 2.25 6.741v6.018z" />
                                </svg>
                            </div>
                            <h3 className="mb-1 text-base font-semibold text-gray-900">No templates yet</h3>
                            <p className="mb-4 text-sm text-gray-500">Create your first reply template to save time responding to reviews.</p>
                            <Button className="bg-teal-600 hover:bg-teal-700 text-white" onClick={openCreate}>
                                Create Template
                            </Button>
                        </CardContent>
                    </Card>
                ) : (
                    <div className="space-y-3">
                        {templates.map((t) => (
                            <Card key={t.id}>
                                <CardHeader className="pb-2">
                                    <div className="flex items-center justify-between">
                                        <CardTitle className="text-sm font-semibold text-gray-900">{t.name}</CardTitle>
                                        <div className="flex items-center gap-2">
                                            <Button
                                                size="sm"
                                                variant="ghost"
                                                className="text-teal-600 hover:text-teal-700"
                                                onClick={() => openEdit(t)}
                                            >
                                                Edit
                                            </Button>
                                            {deleteConfirm === t.id ? (
                                                <div className="flex items-center gap-1">
                                                    <Button
                                                        size="sm"
                                                        variant="destructive"
                                                        onClick={() => handleDelete(t.id)}
                                                    >
                                                        Delete
                                                    </Button>
                                                    <Button
                                                        size="sm"
                                                        variant="ghost"
                                                        onClick={() => setDeleteConfirm(null)}
                                                    >
                                                        Cancel
                                                    </Button>
                                                </div>
                                            ) : (
                                                <Button
                                                    size="sm"
                                                    variant="ghost"
                                                    className="text-red-500 hover:text-red-700"
                                                    onClick={() => setDeleteConfirm(t.id)}
                                                >
                                                    Delete
                                                </Button>
                                            )}
                                        </div>
                                    </div>
                                </CardHeader>
                                <CardContent>
                                    <p className="text-sm text-gray-600 leading-relaxed whitespace-pre-wrap">{t.body}</p>
                                </CardContent>
                            </Card>
                        ))}
                    </div>
                )}
            </div>

            <Dialog open={showDialog} onOpenChange={setShowDialog}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>{editing ? 'Edit Template' : 'New Reply Template'}</DialogTitle>
                    </DialogHeader>
                    <div className="space-y-4 py-2">
                        <div className="space-y-2">
                            <Label htmlFor="tpl-name">Template name</Label>
                            <Input
                                id="tpl-name"
                                placeholder="e.g. Positive review thanks"
                                value={form.name}
                                onChange={(e) => setForm({ ...form, name: e.target.value })}
                            />
                        </div>
                        <div className="space-y-2">
                            <Label htmlFor="tpl-body">Reply text</Label>
                            <Textarea
                                id="tpl-body"
                                placeholder="Thank you so much for the kind words..."
                                value={form.body}
                                onChange={(e) => setForm({ ...form, body: e.target.value })}
                                rows={6}
                            />
                        </div>
                    </div>
                    <DialogFooter>
                        <Button variant="outline" onClick={() => setShowDialog(false)}>Cancel</Button>
                        <Button
                            className="bg-teal-600 hover:bg-teal-700 text-white"
                            onClick={handleSave}
                            disabled={processing || !form.name || !form.body}
                        >
                            {processing ? 'Saving...' : editing ? 'Save changes' : 'Create template'}
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </AppLayout>
    );
}
