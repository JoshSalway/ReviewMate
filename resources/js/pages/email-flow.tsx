import { Head } from '@inertiajs/react';
import { Mail, Clock, CheckCircle, XCircle, RefreshCw, ArrowDown, Zap } from 'lucide-react';
import AppLayout from '@/layouts/app-layout';
import { emailFlow } from '@/routes';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import type { BreadcrumbItem } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Email Flow',
        href: emailFlow(),
    },
];

interface Props {
    business: {
        name: string;
        type: string;
    };
}

function FlowArrow() {
    return (
        <div className="flex justify-center py-2">
            <ArrowDown className="h-6 w-6 text-gray-300" />
        </div>
    );
}

interface FlowStepProps {
    icon: React.ReactNode;
    title: string;
    description: string;
    badge?: string;
    badgeVariant?: 'default' | 'secondary' | 'outline';
    color: 'teal' | 'blue' | 'green' | 'orange' | 'gray';
}

const colorMap = {
    teal: {
        bg: 'bg-teal-50',
        border: 'border-teal-200',
        icon: 'bg-teal-600',
        badge: 'bg-teal-100 text-teal-700',
    },
    blue: {
        bg: 'bg-blue-50',
        border: 'border-blue-200',
        icon: 'bg-blue-600',
        badge: 'bg-blue-100 text-blue-700',
    },
    green: {
        bg: 'bg-green-50',
        border: 'border-green-200',
        icon: 'bg-green-600',
        badge: 'bg-green-100 text-green-700',
    },
    orange: {
        bg: 'bg-orange-50',
        border: 'border-orange-200',
        icon: 'bg-orange-500',
        badge: 'bg-orange-100 text-orange-700',
    },
    gray: {
        bg: 'bg-gray-50',
        border: 'border-gray-200',
        icon: 'bg-gray-500',
        badge: 'bg-gray-100 text-gray-700',
    },
};

function FlowStep({ icon, title, description, badge, color }: FlowStepProps) {
    const colors = colorMap[color];
    return (
        <div className={`flex items-start gap-4 rounded-xl border p-4 ${colors.bg} ${colors.border}`}>
            <div className={`flex h-10 w-10 shrink-0 items-center justify-center rounded-lg ${colors.icon} text-white`}>
                {icon}
            </div>
            <div className="flex-1">
                <div className="flex items-center gap-2">
                    <h3 className="font-semibold text-gray-900">{title}</h3>
                    {badge && (
                        <span className={`rounded-full px-2 py-0.5 text-xs font-medium ${colors.badge}`}>{badge}</span>
                    )}
                </div>
                <p className="mt-0.5 text-sm text-gray-600">{description}</p>
            </div>
        </div>
    );
}

function DecisionBranch() {
    return (
        <div className="relative my-2">
            <div className="flex items-center justify-center gap-6">
                <div className="flex flex-col items-center gap-2 rounded-xl border border-green-200 bg-green-50 px-6 py-4">
                    <CheckCircle className="h-6 w-6 text-green-600" />
                    <span className="text-sm font-medium text-green-700">Yes — reviewed</span>
                    <p className="text-center text-xs text-gray-500">Request marked as reviewed. No further emails sent.</p>
                </div>
                <div className="flex flex-col items-center justify-center">
                    <div className="h-px w-16 border-t-2 border-dashed border-gray-300" />
                    <div className="my-2 rounded-full border border-gray-300 bg-white px-3 py-1 text-xs font-semibold text-gray-500">
                        Did they review?
                    </div>
                    <div className="h-px w-16 border-t-2 border-dashed border-gray-300" />
                </div>
                <div className="flex flex-col items-center gap-2 rounded-xl border border-orange-200 bg-orange-50 px-6 py-4">
                    <XCircle className="h-6 w-6 text-orange-500" />
                    <span className="text-sm font-medium text-orange-700">No — follow up</span>
                    <p className="text-center text-xs text-gray-500">A follow-up email is sent after 3 days.</p>
                </div>
            </div>
        </div>
    );
}

export default function EmailFlow({ business }: Props) {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Email Flow" />
            <div className="flex h-full flex-1 flex-col gap-6 overflow-x-auto p-4 md:p-6">
                <div className="flex items-start justify-between">
                    <div>
                        <h1 className="text-xl font-semibold text-gray-900">Email Automation Flow</h1>
                        <p className="mt-1 text-sm text-gray-500">
                            How ReviewMate automatically follows up with your customers to collect reviews.
                        </p>
                    </div>
                    <Badge className="bg-teal-100 text-teal-700 hover:bg-teal-100">
                        {business.type}
                    </Badge>
                </div>

                <div className="mx-auto w-full max-w-2xl">
                    {/* Step 1 */}
                    <FlowStep
                        icon={<Zap className="h-5 w-5" />}
                        title="Customer Added"
                        description="A customer is added manually, imported via CSV, or created during a Quick Send."
                        badge="Trigger"
                        color="teal"
                    />

                    <FlowArrow />

                    {/* Step 2 */}
                    <FlowStep
                        icon={<Mail className="h-5 w-5" />}
                        title="Review Request Email Sent"
                        description="An email is sent using your customised template. The email includes a direct link to your Google review page."
                        badge="Automatic"
                        color="blue"
                    />

                    <FlowArrow />

                    {/* Step 3 */}
                    <FlowStep
                        icon={<Clock className="h-5 w-5" />}
                        title="Wait 3 Days"
                        description="ReviewMate monitors the request status. If the customer opens the email or leaves a review, that is tracked."
                        badge="3 days"
                        color="gray"
                    />

                    <FlowArrow />

                    {/* Decision */}
                    <DecisionBranch />

                    <FlowArrow />

                    {/* Follow-up path */}
                    <FlowStep
                        icon={<RefreshCw className="h-5 w-5" />}
                        title="Follow-Up Email Sent"
                        description="A gentle follow-up email is sent using your follow-up template. The request is then marked as no_response — no further emails are sent."
                        badge="Automatic"
                        color="orange"
                    />

                    <FlowArrow />

                    {/* Done */}
                    <FlowStep
                        icon={<CheckCircle className="h-5 w-5" />}
                        title="Sequence Complete"
                        description="The automation ends here. You can always send a manual request at any time from Quick Send."
                        color="green"
                    />
                </div>

                {/* Info cards */}
                <div className="mx-auto w-full max-w-2xl">
                    <div className="grid gap-4 sm:grid-cols-3">
                        <Card>
                            <CardHeader className="pb-2">
                                <CardTitle className="text-sm font-medium text-gray-500">Emails per customer</CardTitle>
                            </CardHeader>
                            <CardContent>
                                <div className="text-2xl font-bold text-gray-900">2 max</div>
                                <p className="mt-1 text-xs text-gray-500">Request + 1 follow-up</p>
                            </CardContent>
                        </Card>
                        <Card>
                            <CardHeader className="pb-2">
                                <CardTitle className="text-sm font-medium text-gray-500">Follow-up delay</CardTitle>
                            </CardHeader>
                            <CardContent>
                                <div className="text-2xl font-bold text-gray-900">3 days</div>
                                <p className="mt-1 text-xs text-gray-500">After initial request</p>
                            </CardContent>
                        </Card>
                        <Card>
                            <CardHeader className="pb-2">
                                <CardTitle className="text-sm font-medium text-gray-500">Template</CardTitle>
                            </CardHeader>
                            <CardContent>
                                <div className="text-2xl font-bold text-teal-600">Custom</div>
                                <p className="mt-1 text-xs text-gray-500">Edit in Templates</p>
                            </CardContent>
                        </Card>
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}
