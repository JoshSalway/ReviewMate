import { Head, Link } from '@inertiajs/react';
import { useState } from 'react';
import { LineChart, Line, XAxis, YAxis, CartesianGrid, Tooltip, ResponsiveContainer, Legend } from 'recharts';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';
import { dashboard } from '@/routes';
import type { BreadcrumbItem } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: dashboard(),
    },
];

interface RecentReview {
    id: number;
    reviewer_name: string;
    rating: number;
    body: string;
    reviewed_at: string;
    via_review_mate: boolean;
}

interface UnverifiedClaim {
    id: number;
    customer_name: string | null;
    reviewed_at: string | null;
}

interface Props {
    business: {
        id: number;
        name: string;
        type: string;
        google_review_url: string | null;
    };
    stats: {
        average_rating: number;
        total_reviews: number;
        requests_sent: number;
        conversion_rate: number;
        pending_replies: number;
        reviews_this_month: number;
        requests_this_month: number;
    };
    requestStats: {
        sent: number;
        opened: number;
        reviewed: number;
    };
    recentReviews: RecentReview[];
    chartData: { month: string; reviews: number; requests: number }[];
    hasData: boolean;
    googleRating: number | null;
    googleReviewCount: number | null;
    googleStatsUpdatedAt: string | null;
    unverifiedClaims: UnverifiedClaim[];
}

function StarRating({ rating }: { rating: number }) {
    return (
        <div className="flex items-center gap-0.5">
            {[1, 2, 3, 4, 5].map((star) => (
                <svg
                    key={star}
                    className={`h-4 w-4 ${star <= rating ? 'text-yellow-400' : 'text-muted'}`}
                    fill="currentColor"
                    viewBox="0 0 20 20"
                >
                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                </svg>
            ))}
        </div>
    );
}

function EmptyState({ businessName }: { businessName: string }) {
    return (
        <div className="flex flex-col items-center justify-center py-16 text-center">
            <div className="mb-6 flex h-20 w-20 items-center justify-center rounded-full bg-teal-50">
                <svg className="h-10 w-10 text-teal-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M11.48 3.499a.562.562 0 011.04 0l2.125 5.111a.563.563 0 00.475.345l5.518.442c.499.04.701.663.321.988l-4.204 3.602a.563.563 0 00-.182.557l1.285 5.385a.562.562 0 01-.84.61l-4.725-2.885a.563.563 0 00-.586 0L6.982 20.54a.562.562 0 01-.84-.61l1.285-5.386a.562.562 0 00-.182-.557l-4.204-3.602a.562.562 0 01.321-.988l5.518-.442a.563.563 0 00.475-.345L11.48 3.5z" />
                </svg>
            </div>
            <h2 className="mb-2 text-xl font-semibold text-foreground">Welcome to ReviewMate, {businessName}!</h2>
            <p className="mb-8 max-w-md text-muted-foreground">Get started by sending your first review request. Here's what to do next:</p>
            <div className="mb-8 w-full max-w-md space-y-3 text-left">
                <div className="flex items-start gap-3 rounded-lg border border-teal-100 bg-teal-50 p-4">
                    <div className="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-teal-600 text-xs font-bold text-white">1</div>
                    <div>
                        <p className="font-medium text-foreground">Add your customers</p>
                        <p className="text-sm text-muted-foreground">Import or add customers who can leave reviews</p>
                    </div>
                </div>
                <div className="flex items-start gap-3 rounded-lg border border-border bg-muted p-4">
                    <div className="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-muted-foreground/40 text-xs font-bold text-white">2</div>
                    <div>
                        <p className="font-medium text-foreground">Customise your template</p>
                        <p className="text-sm text-muted-foreground">Personalise the email your customers receive</p>
                    </div>
                </div>
                <div className="flex items-start gap-3 rounded-lg border border-border bg-muted p-4">
                    <div className="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-muted-foreground/40 text-xs font-bold text-white">3</div>
                    <div>
                        <p className="font-medium text-foreground">Send your first request</p>
                        <p className="text-sm text-muted-foreground">Use Quick Send to get a review from your best customers</p>
                    </div>
                </div>
            </div>
            <div className="flex gap-3">
                <Link href="/customers">
                    <Button className="bg-teal-600 hover:bg-teal-700 text-white">Add Customers</Button>
                </Link>
                <Link href="/quick-send">
                    <Button variant="outline">Quick Send</Button>
                </Link>
            </div>
        </div>
    );
}

export default function Dashboard({ business, stats, requestStats, recentReviews, chartData, hasData, googleRating, googleReviewCount, googleStatsUpdatedAt, unverifiedClaims }: Props) {
    const [copied, setCopied] = useState(false);
    const [showUnverifiedDetails, setShowUnverifiedDetails] = useState(false);

    const copyReviewLink = () => {
        if (business.google_review_url) {
            navigator.clipboard.writeText(business.google_review_url);
            setCopied(true);
            setTimeout(() => setCopied(false), 2000);
        }
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Dashboard" />
            <div className="flex h-full flex-1 flex-col gap-6 overflow-x-auto p-4 md:p-6">
                {/* Unverified Claims Banner */}
                {unverifiedClaims.length > 0 && (
                    <div className="rounded-lg border border-yellow-200 bg-yellow-50 p-4">
                        <div className="flex items-start gap-3">
                            <span className="text-yellow-500 text-lg">⚠️</span>
                            <div className="flex-1">
                                <p className="font-medium text-yellow-800">
                                    {unverifiedClaims.length} customer{unverifiedClaims.length > 1 ? 's' : ''} said{' '}
                                    {unverifiedClaims.length > 1 ? 'they reviewed' : 'they reviewed'} but we couldn't find{' '}
                                    {unverifiedClaims.length > 1 ? 'their reviews' : 'their review'} on Google.
                                </p>
                                <button
                                    onClick={() => setShowUnverifiedDetails(!showUnverifiedDetails)}
                                    className="mt-1 text-sm text-yellow-700 underline hover:text-yellow-900"
                                >
                                    {showUnverifiedDetails ? 'Hide details' : 'View details'}
                                </button>
                                {showUnverifiedDetails && (
                                    <ul className="mt-3 space-y-1">
                                        {unverifiedClaims.map((claim) => (
                                            <li key={claim.id} className="text-sm text-yellow-700">
                                                {claim.customer_name ?? 'Unknown'}{claim.reviewed_at ? ` — confirmed ${claim.reviewed_at}` : ''}
                                            </li>
                                        ))}
                                    </ul>
                                )}
                            </div>
                        </div>
                    </div>
                )}

                {!hasData ? (
                    <EmptyState businessName={business.name} />
                ) : (
                    <>
                        {/* Stats Cards */}
                        <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                            <Card>
                                <CardHeader className="pb-2">
                                    <CardTitle className="text-sm font-medium text-muted-foreground">Overall Rating</CardTitle>
                                </CardHeader>
                                <CardContent>
                                    <div className="flex items-end gap-2">
                                        <span className="text-3xl font-bold text-foreground">{stats.average_rating.toFixed(1)}</span>
                                        <span className="mb-1 text-sm text-muted-foreground">/ 5.0</span>
                                    </div>
                                    <StarRating rating={Math.round(stats.average_rating)} />
                                </CardContent>
                            </Card>

                            <Card>
                                <CardHeader className="pb-2">
                                    <CardTitle className="text-sm font-medium text-muted-foreground">Total Reviews</CardTitle>
                                </CardHeader>
                                <CardContent>
                                    <div className="text-3xl font-bold text-foreground">{stats.total_reviews}</div>
                                    <p className="mt-1 text-sm text-muted-foreground">{stats.reviews_this_month} this month</p>
                                </CardContent>
                            </Card>

                            <Card>
                                <CardHeader className="pb-2">
                                    <CardTitle className="text-sm font-medium text-muted-foreground">Requests Sent</CardTitle>
                                </CardHeader>
                                <CardContent>
                                    <div className="text-3xl font-bold text-foreground">{stats.requests_sent}</div>
                                    <p className="mt-1 text-sm text-muted-foreground">{stats.requests_this_month} this month</p>
                                </CardContent>
                            </Card>

                            <Card className={stats.pending_replies > 0 ? 'border-orange-200' : ''}>
                                <CardHeader className="pb-2">
                                    <CardTitle className="text-sm font-medium text-muted-foreground">Pending Replies</CardTitle>
                                </CardHeader>
                                <CardContent>
                                    <div className={`text-3xl font-bold ${stats.pending_replies > 0 ? 'text-orange-500' : 'text-foreground'}`}>
                                        {stats.pending_replies}
                                    </div>
                                    <p className="mt-1 text-sm text-muted-foreground">
                                        {stats.pending_replies > 0 ? 'reviews need a reply' : 'all replied'}
                                    </p>
                                </CardContent>
                            </Card>
                        </div>

                        {/* Quick Stats Row */}
                        <Card>
                            <CardContent className="py-4">
                                <div className="grid grid-cols-3 divide-x">
                                    <div className="px-4 text-center first:pl-0 last:pr-0">
                                        <div className="text-2xl font-bold text-foreground">{requestStats.sent}</div>
                                        <div className="text-sm text-muted-foreground">Sent</div>
                                    </div>
                                    <div className="px-4 text-center">
                                        <div className="text-2xl font-bold text-blue-600">{requestStats.opened}</div>
                                        <div className="text-sm text-muted-foreground">Opened</div>
                                    </div>
                                    <div className="px-4 text-center">
                                        <div className="text-2xl font-bold text-teal-600">{requestStats.reviewed}</div>
                                        <div className="text-sm text-muted-foreground">Reviewed</div>
                                    </div>
                                </div>
                            </CardContent>
                        </Card>

                        {/* Activity Chart */}
                        <Card>
                            <CardHeader>
                                <CardTitle className="text-base font-semibold">6-Month Activity</CardTitle>
                            </CardHeader>
                            <CardContent>
                                <ResponsiveContainer width="100%" height={220}>
                                    <LineChart data={chartData} margin={{ top: 4, right: 8, left: -20, bottom: 0 }}>
                                        <CartesianGrid strokeDasharray="3 3" stroke="#f0f0f0" />
                                        <XAxis dataKey="month" tick={{ fontSize: 12, fill: '#6b7280' }} />
                                        <YAxis allowDecimals={false} tick={{ fontSize: 12, fill: '#6b7280' }} />
                                        <Tooltip
                                            contentStyle={{ borderRadius: '8px', border: '1px solid #e5e7eb', fontSize: '12px' }}
                                        />
                                        <Legend iconType="circle" iconSize={8} wrapperStyle={{ fontSize: '12px' }} />
                                        <Line
                                            type="monotone"
                                            dataKey="reviews"
                                            name="Reviews"
                                            stroke="#0d9488"
                                            strokeWidth={2}
                                            dot={{ r: 3, fill: '#0d9488' }}
                                            activeDot={{ r: 5 }}
                                        />
                                        <Line
                                            type="monotone"
                                            dataKey="requests"
                                            name="Requests"
                                            stroke="#93c5fd"
                                            strokeWidth={2}
                                            dot={{ r: 3, fill: '#93c5fd' }}
                                            activeDot={{ r: 5 }}
                                        />
                                    </LineChart>
                                </ResponsiveContainer>
                            </CardContent>
                        </Card>

                        <div className="grid gap-6 lg:grid-cols-3">
                            {/* Recent Reviews */}
                            <div className="lg:col-span-2">
                                <Card className="h-full">
                                    <CardHeader>
                                        <CardTitle className="text-base font-semibold">Recent Reviews</CardTitle>
                                    </CardHeader>
                                    <CardContent>
                                        {recentReviews.length === 0 ? (
                                            <p className="py-8 text-center text-sm text-muted-foreground">No reviews yet. Send your first request to get started.</p>
                                        ) : (
                                            <div className="space-y-4">
                                                {recentReviews.map((review) => (
                                                    <div key={review.id} className="border-b pb-4 last:border-0 last:pb-0">
                                                        <div className="mb-1 flex items-center justify-between">
                                                            <div className="flex items-center gap-2">
                                                                <span className="font-medium text-foreground">{review.reviewer_name}</span>
                                                                {review.via_review_mate && (
                                                                    <Badge className="bg-teal-100 text-teal-700 hover:bg-teal-100 text-xs">via ReviewMate</Badge>
                                                                )}
                                                            </div>
                                                            <span className="text-xs text-muted-foreground">{new Date(review.reviewed_at).toLocaleDateString()}</span>
                                                        </div>
                                                        <StarRating rating={review.rating} />
                                                        {review.body && (
                                                            <p className="mt-2 text-sm text-muted-foreground line-clamp-2">{review.body}</p>
                                                        )}
                                                    </div>
                                                ))}
                                            </div>
                                        )}
                                    </CardContent>
                                </Card>
                            </div>

                            {/* Right column: Google Reviews stats + Review Link */}
                            <div className="flex flex-col gap-4">
                                {/* Google Reviews stat card */}
                                <Card>
                                    <CardHeader className="pb-2">
                                        <CardTitle className="text-base font-semibold">Google Reviews</CardTitle>
                                    </CardHeader>
                                    <CardContent>
                                        {googleRating !== null ? (
                                            <div className="space-y-3">
                                                <div className="flex items-center gap-3">
                                                    <span className="text-3xl font-bold text-foreground">{googleRating.toFixed(1)}</span>
                                                    <div>
                                                        <StarRating rating={Math.round(googleRating)} />
                                                        <p className="mt-0.5 text-sm text-muted-foreground">
                                                            {googleReviewCount !== null ? `${googleReviewCount} reviews` : ''}
                                                        </p>
                                                    </div>
                                                </div>
                                                <div className="flex items-center justify-between">
                                                    {googleStatsUpdatedAt && (
                                                        <p className="text-xs text-muted-foreground">Updated {googleStatsUpdatedAt}</p>
                                                    )}
                                                    {business.google_review_url && business.google_review_url !== '#' && (
                                                        <a
                                                            href={business.google_review_url}
                                                            target="_blank"
                                                            rel="noopener noreferrer"
                                                            className="text-xs font-medium text-teal-600 hover:text-teal-700"
                                                        >
                                                            View on Google
                                                        </a>
                                                    )}
                                                </div>
                                            </div>
                                        ) : (
                                            <div className="space-y-2">
                                                <p className="text-sm text-muted-foreground">
                                                    Google review stats will appear here once your Place ID is set and stats are fetched.
                                                </p>
                                                {business.google_review_url && business.google_review_url !== '#' && (
                                                    <a
                                                        href={business.google_review_url}
                                                        target="_blank"
                                                        rel="noopener noreferrer"
                                                        className="text-xs font-medium text-teal-600 hover:text-teal-700"
                                                    >
                                                        View on Google
                                                    </a>
                                                )}
                                            </div>
                                        )}
                                    </CardContent>
                                </Card>

                                {/* Google Review Link */}
                                <Card>
                                    <CardHeader>
                                        <CardTitle className="text-base font-semibold">Your Review Link</CardTitle>
                                    </CardHeader>
                                    <CardContent>
                                        {business.google_review_url && business.google_review_url !== '#' ? (
                                            <div className="space-y-3">
                                                <p className="text-sm text-muted-foreground">Share this link with customers to collect reviews directly on Google.</p>
                                                <div className="rounded-lg bg-muted p-3">
                                                    <p className="break-all text-xs text-muted-foreground">{business.google_review_url}</p>
                                                </div>
                                                <Button
                                                    onClick={copyReviewLink}
                                                    className="w-full bg-teal-600 hover:bg-teal-700 text-white"
                                                >
                                                    {copied ? 'Copied!' : 'Copy Link'}
                                                </Button>
                                            </div>
                                        ) : (
                                            <div className="space-y-3">
                                                <p className="text-sm text-muted-foreground">Connect your Google Business account to get your review link.</p>
                                                <Link href="/settings/business">
                                                    <Button variant="outline" className="w-full">Connect Google</Button>
                                                </Link>
                                            </div>
                                        )}
                                    </CardContent>
                                </Card>
                            </div>
                        </div>
                    </>
                )}
            </div>
        </AppLayout>
    );
}
