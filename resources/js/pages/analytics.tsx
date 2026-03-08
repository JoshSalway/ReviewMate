import { Head } from '@inertiajs/react';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import AppLayout from '@/layouts/app-layout';
import { analytics as analyticsRoute } from '@/routes';
import type { BreadcrumbItem } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Analytics', href: analyticsRoute() },
];

interface BusinessRow {
    id: number;
    name: string;
    type: string;
    total_reviews: number;
    avg_rating: number;
    requests_sent: number;
    conversion_rate: number;
    pending_replies: number;
    reviews_this_month: number;
}

interface Totals {
    total_reviews: number;
    avg_rating: number;
    requests_sent: number;
    conversion_rate: number;
    pending_replies: number;
    reviews_this_month: number;
}

interface Props {
    businesses: BusinessRow[];
    totals: Totals;
    can_see_all: boolean;
}

function StarRating({ rating }: { rating: number }) {
    const full = Math.round(rating);
    return (
        <span className="flex items-center gap-1">
            <span className="text-yellow-400">{'★'.repeat(full)}{'☆'.repeat(5 - full)}</span>
            <span className="text-muted-foreground text-sm">{rating}</span>
        </span>
    );
}

function StatCard({ label, value, sub }: { label: string; value: string | number; sub?: string }) {
    return (
        <Card>
            <CardContent className="pt-5">
                <p className="text-sm text-muted-foreground">{label}</p>
                <p className="mt-1 text-3xl font-bold text-foreground">{value}</p>
                {sub && <p className="text-xs text-muted-foreground mt-0.5">{sub}</p>}
            </CardContent>
        </Card>
    );
}

export default function Analytics({ businesses, totals, can_see_all }: Props) {
    const showTable = businesses.length > 1;

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Analytics" />
            <div className="flex h-full flex-1 flex-col gap-6 p-4 md:p-6">
                <div className="flex items-start justify-between">
                    <div>
                        <h1 className="text-2xl font-bold text-foreground">Analytics</h1>
                        <p className="mt-1 text-sm text-muted-foreground">
                            {can_see_all && businesses.length > 1
                                ? `Aggregated across ${businesses.length} businesses`
                                : 'Performance overview'}
                        </p>
                    </div>
                    {!can_see_all && (
                        <Badge className="bg-amber-100 text-amber-700 hover:bg-amber-100">
                            Upgrade to Pro for multi-location analytics
                        </Badge>
                    )}
                </div>

                {/* Summary cards */}
                <div className="grid grid-cols-2 gap-4 md:grid-cols-3 lg:grid-cols-6">
                    <StatCard label="Total Reviews" value={totals.total_reviews} />
                    <StatCard label="Avg Rating" value={`${totals.avg_rating}★`} />
                    <StatCard label="Requests Sent" value={totals.requests_sent} />
                    <StatCard label="Conversion Rate" value={`${totals.conversion_rate}%`} />
                    <StatCard
                        label="Pending Replies"
                        value={totals.pending_replies}
                        sub={totals.pending_replies > 0 ? 'Needs attention' : undefined}
                    />
                    <StatCard label="Reviews This Month" value={totals.reviews_this_month} />
                </div>

                {/* Per-business table (Pro/Admin only, more than 1 business) */}
                {showTable && (
                    <Card>
                        <CardHeader>
                            <CardTitle className="text-base font-semibold">By Location</CardTitle>
                        </CardHeader>
                        <CardContent className="p-0">
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>Business</TableHead>
                                        <TableHead className="text-right">Reviews</TableHead>
                                        <TableHead className="text-right">Avg Rating</TableHead>
                                        <TableHead className="text-right">Requests</TableHead>
                                        <TableHead className="text-right">Conversion</TableHead>
                                        <TableHead className="text-right">Pending Reply</TableHead>
                                        <TableHead className="text-right">This Month</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {businesses.map((biz) => (
                                        <TableRow key={biz.id}>
                                            <TableCell>
                                                <div className="font-medium text-foreground">{biz.name}</div>
                                                <div className="text-xs text-muted-foreground capitalize">{biz.type?.replace(/_/g, ' ')}</div>
                                            </TableCell>
                                            <TableCell className="text-right">{biz.total_reviews}</TableCell>
                                            <TableCell className="text-right">
                                                <StarRating rating={biz.avg_rating} />
                                            </TableCell>
                                            <TableCell className="text-right">{biz.requests_sent}</TableCell>
                                            <TableCell className="text-right">{biz.conversion_rate}%</TableCell>
                                            <TableCell className="text-right">
                                                {biz.pending_replies > 0 ? (
                                                    <span className="font-semibold text-orange-600">{biz.pending_replies}</span>
                                                ) : (
                                                    <span className="text-muted-foreground">—</span>
                                                )}
                                            </TableCell>
                                            <TableCell className="text-right">{biz.reviews_this_month}</TableCell>
                                        </TableRow>
                                    ))}
                                    {/* Totals row */}
                                    <TableRow className="bg-muted font-semibold">
                                        <TableCell>Total</TableCell>
                                        <TableCell className="text-right">{totals.total_reviews}</TableCell>
                                        <TableCell className="text-right">
                                            <StarRating rating={totals.avg_rating} />
                                        </TableCell>
                                        <TableCell className="text-right">{totals.requests_sent}</TableCell>
                                        <TableCell className="text-right">{totals.conversion_rate}%</TableCell>
                                        <TableCell className="text-right">
                                            {totals.pending_replies > 0 ? (
                                                <span className="text-orange-600">{totals.pending_replies}</span>
                                            ) : (
                                                <span className="text-muted-foreground">—</span>
                                            )}
                                        </TableCell>
                                        <TableCell className="text-right">{totals.reviews_this_month}</TableCell>
                                    </TableRow>
                                </TableBody>
                            </Table>
                        </CardContent>
                    </Card>
                )}
            </div>
        </AppLayout>
    );
}
