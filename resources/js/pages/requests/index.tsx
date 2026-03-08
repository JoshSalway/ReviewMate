import { Head, router } from '@inertiajs/react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';
import { index as requestsIndex } from '@/routes/requests';
import type { BreadcrumbItem } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Requests',
        href: requestsIndex(),
    },
];

type RequestStatus = 'sent' | 'opened' | 'reviewed' | 'no_response';

interface ReviewRequest {
    id: number;
    customer_name: string;
    customer_email: string;
    channel: string;
    status: RequestStatus;
    sent_at: string;
    opened_at: string | null;
    reviewed_at: string | null;
}

interface PaginatedRequests {
    data: ReviewRequest[];
    links: {
        url: string | null;
        label: string;
        active: boolean;
    }[];
}

interface Props {
    stats: {
        sent: number;
        opened: number;
        reviewed: number;
        no_response: number;
    };
    requests: PaginatedRequests;
}

const statusConfig: Record<RequestStatus, { label: string; className: string }> = {
    sent: { label: 'Sent', className: 'bg-blue-100 text-blue-700 hover:bg-blue-100' },
    opened: { label: 'Opened', className: 'bg-yellow-100 text-yellow-700 hover:bg-yellow-100' },
    reviewed: { label: 'Reviewed', className: 'bg-green-100 text-green-700 hover:bg-green-100' },
    no_response: { label: 'No Response', className: 'bg-red-100 text-red-700 hover:bg-red-100' },
};

function TimelineStep({
    label,
    date,
    active,
    last,
}: {
    label: string;
    date: string | null;
    active: boolean;
    last?: boolean;
}) {
    return (
        <div className="flex items-center gap-2">
            <div className={`flex h-6 w-6 shrink-0 items-center justify-center rounded-full border-2 text-xs font-bold ${active ? 'border-teal-600 bg-teal-600 text-white' : 'border-border bg-card text-muted-foreground'}`}>
                {active ? '✓' : ''}
            </div>
            <div className="min-w-0">
                <div className={`text-xs font-medium ${active ? 'text-foreground' : 'text-muted-foreground'}`}>{label}</div>
                {date && <div className="text-xs text-muted-foreground">{new Date(date).toLocaleDateString()}</div>}
            </div>
            {!last && <div className={`mx-1 h-px w-8 ${active ? 'bg-teal-200' : 'bg-border'}`} />}
        </div>
    );
}

export default function RequestsIndex({ stats, requests }: Props) {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Review Requests" />
            <div className="flex h-full flex-1 flex-col gap-6 p-4 md:p-6">
                <div>
                    <h1 className="text-2xl font-bold text-foreground">Review Requests</h1>
                    <p className="mt-1 text-sm text-muted-foreground">Track the status of your review requests</p>
                </div>

                {/* Stats Cards */}
                <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    <Card>
                        <CardHeader className="pb-2">
                            <CardTitle className="text-sm font-medium text-muted-foreground">Total Sent</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="text-3xl font-bold text-foreground">{stats.sent}</div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader className="pb-2">
                            <CardTitle className="text-sm font-medium text-muted-foreground">Opened</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="text-3xl font-bold text-blue-600">{stats.opened}</div>
                            {stats.sent > 0 && (
                                <p className="mt-1 text-sm text-muted-foreground">{Math.round((stats.opened / stats.sent) * 100)}% open rate</p>
                            )}
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader className="pb-2">
                            <CardTitle className="text-sm font-medium text-muted-foreground">Reviewed</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="text-3xl font-bold text-teal-600">{stats.reviewed}</div>
                            {stats.sent > 0 && (
                                <p className="mt-1 text-sm text-muted-foreground">{Math.round((stats.reviewed / stats.sent) * 100)}% conversion</p>
                            )}
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader className="pb-2">
                            <CardTitle className="text-sm font-medium text-muted-foreground">No Response</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="text-3xl font-bold text-red-500">{stats.no_response}</div>
                        </CardContent>
                    </Card>
                </div>

                {/* Requests List */}
                <Card>
                    <CardHeader>
                        <CardTitle className="text-base font-semibold">All Requests</CardTitle>
                    </CardHeader>
                    <CardContent className="p-0">
                        {requests.data.length === 0 ? (
                            <div className="flex flex-col items-center justify-center py-16 text-center">
                                <div className="mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-muted">
                                    <svg className="h-8 w-8 text-muted-foreground" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75" />
                                    </svg>
                                </div>
                                <h3 className="mb-1 text-base font-semibold text-foreground">No requests sent yet</h3>
                                <p className="mb-4 text-sm text-muted-foreground">Use Quick Send to send your first review request.</p>
                            </div>
                        ) : (
                            <div className="divide-y">
                                {requests.data.map((request) => (
                                    <div key={request.id} className="flex items-center justify-between p-4">
                                        <div className="min-w-0 flex-1">
                                            <div className="flex items-center gap-2">
                                                <span className="font-medium text-foreground">{request.customer_name}</span>
                                                <Badge className={`text-xs ${statusConfig[request.status]?.className ?? ''}`}>
                                                    {statusConfig[request.status]?.label ?? request.status}
                                                </Badge>
                                                <Badge variant="outline" className="text-xs capitalize">{request.channel}</Badge>
                                            </div>
                                            <div className="mt-0.5 text-sm text-gray-500">{request.customer_email}</div>
                                        </div>
                                        <div className="ml-4 hidden items-center md:flex">
                                            <TimelineStep
                                                label="Sent"
                                                date={request.sent_at}
                                                active={true}
                                            />
                                            <TimelineStep
                                                label="Opened"
                                                date={request.opened_at}
                                                active={!!request.opened_at}
                                            />
                                            <TimelineStep
                                                label="Reviewed"
                                                date={request.reviewed_at}
                                                active={!!request.reviewed_at}
                                                last
                                            />
                                        </div>
                                        <div className="ml-4 text-right">
                                            <div className="text-xs text-gray-400">{new Date(request.sent_at).toLocaleDateString()}</div>
                                        </div>
                                    </div>
                                ))}
                            </div>
                        )}
                    </CardContent>
                </Card>

                {/* Pagination */}
                {requests.links.length > 3 && (
                    <div className="flex items-center justify-center gap-1">
                        {requests.links.map((link, i) => (
                            <Button
                                key={i}
                                variant={link.active ? 'default' : 'outline'}
                                size="sm"
                                disabled={!link.url}
                                className={link.active ? 'bg-teal-600 hover:bg-teal-700 text-white' : ''}
                                onClick={() => link.url && router.visit(link.url)}
                                dangerouslySetInnerHTML={{ __html: link.label }}
                            />
                        ))}
                    </div>
                )}
            </div>
        </AppLayout>
    );
}
