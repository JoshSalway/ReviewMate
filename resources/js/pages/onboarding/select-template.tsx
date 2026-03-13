import { Head, router } from '@inertiajs/react';
import { useState } from 'react';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader } from '@/components/ui/card';
import { selectTemplate as selectTemplateRoute } from '@/routes/onboarding';

interface DefaultTemplate {
    type: string;
    subject: string;
    body: string;
}

interface Props {
    business: {
        id: number;
        name: string;
        type: string;
    };
    defaultTemplates: DefaultTemplate[];
}


function renderPreview(body: string, businessName: string) {
    return body
        .replace(/{customer_name}/g, 'Jane')
        .replace(/{business_name}/g, businessName)
        .replace(/{review_link}/g, 'your review link')
        .replace(/{owner_name}/g, 'the team')
        .replace(/\n/g, '<br/>');
}

export default function SelectTemplate({ business, defaultTemplates }: Props) {
    const [processing, setProcessing] = useState(false);

    const requestTemplate = defaultTemplates.find((t) => t.type === 'request');

    const handleConfirm = () => {
        setProcessing(true);
        router.post(
            selectTemplateRoute().url,
            {},
            {
                onFinish: () => setProcessing(false),
            },
        );
    };

    return (
        <>
            <Head title="Select Template - Setup" />
            <div className="flex min-h-screen items-center justify-center bg-background p-4">
                <div className="w-full max-w-xl">
                    {/* Header */}
                    <div className="mb-8 text-center">
                        <div className="mb-4 flex items-center justify-center gap-2">
                            <div className="flex h-8 w-8 items-center justify-center rounded-full bg-teal-100 text-sm font-bold text-teal-600">✓</div>
                            <div className="h-px w-12 bg-teal-200" />
                            <div className="flex h-8 w-8 items-center justify-center rounded-full bg-teal-100 text-sm font-bold text-teal-600">✓</div>
                            <div className="h-px w-12 bg-teal-200" />
                            <div className="flex h-8 w-8 items-center justify-center rounded-full bg-teal-600 text-sm font-bold text-white">3</div>
                        </div>
                        <p className="mb-1 text-sm font-medium text-teal-600">Step 3 of 3 — almost done!</p>
                        <h1 className="text-2xl font-bold text-foreground">Your personalised review request is ready 🎉</h1>
                        <p className="mt-2 text-muted-foreground">
                            Here's exactly what your customers will receive. We've written it to feel personal, not spammy. You can edit it any time.
                        </p>
                    </div>

                    <div className="rounded-xl bg-card p-6 shadow-sm ring-1 ring-border">
                        {/* Email Preview */}
                        {requestTemplate ? (
                            <div className="mb-6">
                                <h2 className="mb-3 text-sm font-semibold text-foreground">This is what your customers will see</h2>
                                <Card className="overflow-hidden border-border">
                                    <CardHeader className="border-b bg-muted py-3">
                                        <div className="flex items-center gap-2">
                                            <div className="flex gap-1">
                                                <div className="h-3 w-3 rounded-full bg-red-400" />
                                                <div className="h-3 w-3 rounded-full bg-yellow-400" />
                                                <div className="h-3 w-3 rounded-full bg-green-400" />
                                            </div>
                                        </div>
                                        <div className="mt-2 space-y-1 text-sm">
                                            <div>
                                                <span className="text-muted-foreground">From: </span>
                                                <span className="font-medium text-foreground">{business.name}</span>
                                            </div>
                                            <div>
                                                <span className="text-muted-foreground">Subject: </span>
                                                <span className="font-medium text-foreground"
                                                    dangerouslySetInnerHTML={{
                                                        __html: renderPreview(requestTemplate.subject, business.name),
                                                    }}
                                                />
                                            </div>
                                        </div>
                                    </CardHeader>
                                    <CardContent className="p-5">
                                        <div
                                            className="text-sm leading-relaxed text-foreground"
                                            dangerouslySetInnerHTML={{
                                                __html: renderPreview(requestTemplate.body, business.name),
                                            }}
                                        />
                                    </CardContent>
                                </Card>
                            </div>
                        ) : (
                            <div className="mb-6 rounded-lg bg-muted p-4 text-center text-sm text-muted-foreground">
                                Template preview not available. You can customise your templates after setup.
                            </div>
                        )}

                        <div className="space-y-3">
                            <Button
                                className="w-full bg-teal-600 hover:bg-teal-700 text-white"
                                onClick={handleConfirm}
                                disabled={processing}
                            >
                                {processing ? 'Setting up...' : "Looks good — let's go! →"}
                            </Button>
                            <p className="text-center text-xs text-muted-foreground">
                                This is your starting point — most businesses tweak it once they see their first few reviews come in.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </>
    );
}
