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

const typeLabels: Record<string, string> = {
    tradie: 'Tradie',
    cafe: 'Cafe / Restaurant',
    salon: 'Salon / Barber',
    healthcare: 'Healthcare',
    real_estate: 'Real Estate',
    retail: 'Retail',
    pet_services: 'Pet Services',
    fitness: 'Fitness',
    other: 'Business',
};

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
            <div className="flex min-h-screen items-center justify-center bg-gray-50 p-4">
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
                        <p className="mb-1 text-sm font-medium text-teal-600">Step 3 of 3</p>
                        <h1 className="text-2xl font-bold text-gray-900">Your template is ready</h1>
                        <p className="mt-2 text-gray-500">
                            We've created a personalised email template for your{' '}
                            <strong>{typeLabels[business.type] ?? business.type}</strong> business.
                            You can customise it at any time.
                        </p>
                    </div>

                    <div className="rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-100">
                        {/* Email Preview */}
                        {requestTemplate ? (
                            <div className="mb-6">
                                <h2 className="mb-3 text-sm font-semibold text-gray-700">Review Request Email Preview</h2>
                                <Card className="overflow-hidden border-gray-100">
                                    <CardHeader className="border-b bg-gray-50 py-3">
                                        <div className="flex items-center gap-2">
                                            <div className="flex gap-1">
                                                <div className="h-3 w-3 rounded-full bg-red-400" />
                                                <div className="h-3 w-3 rounded-full bg-yellow-400" />
                                                <div className="h-3 w-3 rounded-full bg-green-400" />
                                            </div>
                                        </div>
                                        <div className="mt-2 space-y-1 text-sm">
                                            <div>
                                                <span className="text-gray-400">From: </span>
                                                <span className="font-medium text-gray-700">{business.name}</span>
                                            </div>
                                            <div>
                                                <span className="text-gray-400">Subject: </span>
                                                <span className="font-medium text-gray-700"
                                                    dangerouslySetInnerHTML={{
                                                        __html: renderPreview(requestTemplate.subject, business.name),
                                                    }}
                                                />
                                            </div>
                                        </div>
                                    </CardHeader>
                                    <CardContent className="p-5">
                                        <div
                                            className="text-sm leading-relaxed text-gray-700"
                                            dangerouslySetInnerHTML={{
                                                __html: renderPreview(requestTemplate.body, business.name),
                                            }}
                                        />
                                    </CardContent>
                                </Card>
                            </div>
                        ) : (
                            <div className="mb-6 rounded-lg bg-gray-50 p-4 text-center text-sm text-gray-500">
                                Template preview not available. You can customise your templates after setup.
                            </div>
                        )}

                        <div className="space-y-3">
                            <Button
                                className="w-full bg-teal-600 hover:bg-teal-700 text-white"
                                onClick={handleConfirm}
                                disabled={processing}
                            >
                                {processing ? 'Setting up...' : 'Looks great — finish setup'}
                            </Button>
                            <p className="text-center text-xs text-gray-400">
                                You can customise your templates anytime from the Templates page.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </>
    );
}
