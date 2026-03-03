import { Head, router } from '@inertiajs/react';
import { useState } from 'react';
import AppLayout from '@/layouts/app-layout';
import { index as reviewsIndex, reply as replyRoute, replySuggestions as replySuggestionsRoute } from '@/routes/reviews';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Textarea } from '@/components/ui/textarea';
import type { BreadcrumbItem } from '@/types';
import { MessageSquare, CheckCircle, AlertCircle } from 'lucide-react';

interface Review {
    id: number;
    rating: number;
    body: string | null;
    reviewer_name: string;
    reviewed_at: string | null;
    via_review_mate: boolean;
    google_reply: string | null;
    google_reply_posted_at: string | null;
    has_google_link: boolean;
}

interface PaginationLink {
    url: string | null;
    label: string;
    active: boolean;
}

interface Paginated<T> {
    data: T[];
    links: PaginationLink[];
    total: number;
    from: number | null;
    to: number | null;
}

interface Props {
    needsReply: Paginated<Review>;
    replied: Paginated<Review>;
    allReviews: Paginated<Review>;
    isGoogleConnected: boolean;
}

interface ReviewState {
    suggestions: string[];
    selected: number | null;
    reply: string;
    loading: boolean;
}

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Reviews',
        href: reviewsIndex(),
    },
];

function StarRating({ rating }: { rating: number }) {
    return (
        <div className="flex items-center gap-0.5">
            {[1, 2, 3, 4, 5].map((star) => (
                <svg key={star} className={`h-4 w-4 ${star <= rating ? 'text-yellow-400' : 'text-gray-200'}`} fill="currentColor" viewBox="0 0 20 20">
                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                </svg>
            ))}
        </div>
    );
}

export default function ReviewsIndex({ needsReply, replied, allReviews, isGoogleConnected }: Props) {
    const [reviewStates, setReviewStates] = useState<Record<number, ReviewState>>({});
    const [expandedReplies, setExpandedReplies] = useState<Record<number, boolean>>({});

    const getReviewState = (id: number): ReviewState =>
        reviewStates[id] ?? { suggestions: [], selected: null, reply: '', loading: false };

    const updateReviewState = (id: number, patch: Partial<ReviewState>) => {
        setReviewStates((prev) => ({
            ...prev,
            [id]: { ...getReviewState(id), ...patch },
        }));
    };

    const handleGetSuggestions = (review: Review) => {
        updateReviewState(review.id, { loading: true });
        router.post(
            replySuggestionsRoute(review.id).url,
            {},
            {
                preserveScroll: true,
                onSuccess: (page: any) => {
                    const data = page.props.suggestions as string[] | undefined;
                    if (data && Array.isArray(data)) {
                        updateReviewState(review.id, { suggestions: data, selected: null });
                    }
                },
                onFinish: () => updateReviewState(review.id, { loading: false }),
            },
        );
    };

    const handleSelectSuggestion = (reviewId: number, index: number, suggestions: string[]) => {
        updateReviewState(reviewId, { selected: index, reply: suggestions[index] ?? '' });
    };

    const handlePostReply = (review: Review) => {
        const state = getReviewState(review.id);
        router.post(
            replyRoute(review.id).url,
            { reply: state.reply },
            { preserveScroll: true },
        );
    };

    const toggleReply = (id: number) => {
        setExpandedReplies((prev) => ({ ...prev, [id]: !prev[id] }));
    };

    const isEmpty = needsReply.total === 0 && replied.total === 0 && allReviews.total === 0;

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Reviews" />
            <div className="flex h-full flex-1 flex-col gap-6 p-4 md:p-6">
                {/* Header */}
                <div className="flex items-center gap-3">
                    <div>
                        <h1 className="text-2xl font-bold text-gray-900">Reviews</h1>
                        {needsReply.total > 0 && (
                            <div className="mt-1 flex items-center gap-2">
                                <Badge className="bg-amber-100 text-amber-700 hover:bg-amber-100">
                                    {needsReply.total} needing reply
                                </Badge>
                            </div>
                        )}
                    </div>
                </div>

                {/* Google not connected banner */}
                {!isGoogleConnected && needsReply.length === 0 && (
                    <div className="flex items-start gap-3 rounded-lg border border-amber-200 bg-amber-50 p-4">
                        <AlertCircle className="mt-0.5 h-5 w-5 shrink-0 text-amber-600" />
                        <p className="text-sm text-amber-800">
                            Connect Google Business Profile in Settings to sync your reviews and reply directly from ReviewMate.
                        </p>
                    </div>
                )}

                {/* Empty state */}
                {isEmpty && (
                    <div className="flex flex-col items-center justify-center py-20 text-center">
                        <div className="mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-gray-100">
                            <MessageSquare className="h-8 w-8 text-gray-400" />
                        </div>
                        <h3 className="mb-1 text-base font-semibold text-gray-900">No reviews yet</h3>
                        <p className="text-sm text-gray-500">
                            Reviews you receive will appear here. Connect Google Business Profile to start syncing.
                        </p>
                    </div>
                )}

                {/* Needs Reply Section */}
                {needsReply.total > 0 && (
                    <section className="space-y-4">
                        <div className="flex items-center gap-2">
                            <AlertCircle className="h-5 w-5 text-amber-500" />
                            <h2 className="text-lg font-semibold text-gray-900">Needs Reply</h2>
                            <Badge className="bg-amber-100 text-amber-700 hover:bg-amber-100">{needsReply.total}</Badge>
                        </div>

                        <div className="space-y-4">
                            {needsReply.data.map((review) => {
                                const state = getReviewState(review.id);
                                return (
                                    <Card key={review.id}>
                                        <CardHeader className="border-b bg-gray-50">
                                            <div className="flex items-center gap-3">
                                                <div className="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-teal-600 text-sm font-bold text-white">
                                                    {review.reviewer_name.charAt(0).toUpperCase()}
                                                </div>
                                                <div>
                                                    <div className="font-semibold text-gray-900">{review.reviewer_name}</div>
                                                    <div className="text-xs text-gray-500">
                                                        {new Date(review.reviewed_at).toLocaleDateString('en-AU', {
                                                            day: 'numeric',
                                                            month: 'long',
                                                            year: 'numeric',
                                                        })}
                                                    </div>
                                                </div>
                                                {review.via_review_mate && (
                                                    <Badge className="ml-auto bg-teal-100 text-teal-700 hover:bg-teal-100">
                                                        via ReviewMate
                                                    </Badge>
                                                )}
                                            </div>
                                        </CardHeader>
                                        <CardContent className="space-y-4 pt-5">
                                            <StarRating rating={review.rating} />
                                            {review.body ? (
                                                <blockquote className="border-l-4 border-teal-200 pl-4 text-gray-700 italic leading-relaxed">
                                                    "{review.body}"
                                                </blockquote>
                                            ) : (
                                                <p className="text-sm text-gray-400 italic">No written review</p>
                                            )}

                                            {/* Suggestions area */}
                                            {state.suggestions.length === 0 ? (
                                                <Button
                                                    className="bg-teal-600 hover:bg-teal-700 text-white"
                                                    onClick={() => handleGetSuggestions(review)}
                                                    disabled={state.loading}
                                                >
                                                    {state.loading ? 'Generating...' : 'Suggest Reply'}
                                                </Button>
                                            ) : (
                                                <div className="space-y-3">
                                                    <p className="text-sm font-medium text-gray-700">Choose a reply suggestion</p>
                                                    {state.suggestions.map((suggestion, index) => (
                                                        <button
                                                            key={index}
                                                            type="button"
                                                            onClick={() => handleSelectSuggestion(review.id, index, state.suggestions)}
                                                            className={`w-full rounded-lg border-2 p-4 text-left text-sm leading-relaxed transition ${
                                                                state.selected === index
                                                                    ? 'border-teal-600 bg-teal-50 text-teal-900'
                                                                    : 'border-gray-200 text-gray-700 hover:border-gray-300'
                                                            }`}
                                                        >
                                                            <div className="mb-1 flex items-center gap-2">
                                                                <div
                                                                    className={`flex h-5 w-5 shrink-0 items-center justify-center rounded-full border-2 ${
                                                                        state.selected === index
                                                                            ? 'border-teal-600 bg-teal-600'
                                                                            : 'border-gray-300'
                                                                    }`}
                                                                >
                                                                    {state.selected === index && (
                                                                        <svg className="h-3 w-3 text-white" fill="currentColor" viewBox="0 0 12 12">
                                                                            <path d="M3.707 5.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4a1 1 0 00-1.414-1.414L5 6.586 3.707 5.293z" />
                                                                        </svg>
                                                                    )}
                                                                </div>
                                                                <span className="text-xs font-semibold text-gray-400">
                                                                    Option {index + 1}
                                                                </span>
                                                            </div>
                                                            {suggestion}
                                                        </button>
                                                    ))}
                                                    <Button
                                                        variant="ghost"
                                                        size="sm"
                                                        className="text-teal-600 hover:text-teal-700"
                                                        onClick={() => handleGetSuggestions(review)}
                                                        disabled={state.loading}
                                                    >
                                                        {state.loading ? 'Regenerating...' : 'Regenerate suggestions'}
                                                    </Button>
                                                </div>
                                            )}

                                            {/* Reply textarea */}
                                            <div className="space-y-2">
                                                <Textarea
                                                    placeholder="Write or edit your reply here..."
                                                    value={state.reply}
                                                    onChange={(e) => updateReviewState(review.id, { reply: e.target.value })}
                                                    rows={4}
                                                />
                                            </div>

                                            {/* Post reply button */}
                                            <Button
                                                className="bg-teal-600 hover:bg-teal-700 text-white"
                                                disabled={!state.reply.trim()}
                                                onClick={() => handlePostReply(review)}
                                            >
                                                Post Reply
                                            </Button>
                                        </CardContent>
                                    </Card>
                                );
                            })}
                        </div>

                        {needsReply.links.length > 3 && (
                            <div className="flex items-center justify-center gap-1">
                                {needsReply.links.map((link, i) => (
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
                    </section>
                )}

                {/* Replied Section */}
                {replied.total > 0 && (
                    <section className="space-y-4">
                        <div className="flex items-center gap-2">
                            <CheckCircle className="h-5 w-5 text-teal-600" />
                            <h2 className="text-lg font-semibold text-gray-900">Replied</h2>
                            <Badge className="bg-teal-100 text-teal-700 hover:bg-teal-100">{replied.total}</Badge>
                        </div>

                        <div className="space-y-4">
                            {replied.data.map((review) => (
                                <Card key={review.id}>
                                    <CardHeader className="border-b bg-gray-50">
                                        <div className="flex items-center gap-3">
                                            <div className="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-teal-600 text-sm font-bold text-white">
                                                {review.reviewer_name.charAt(0).toUpperCase()}
                                            </div>
                                            <div>
                                                <div className="font-semibold text-gray-900">{review.reviewer_name}</div>
                                                <div className="text-xs text-gray-500">
                                                    {review.reviewed_at
                                                        ? new Date(review.reviewed_at).toLocaleDateString('en-AU', {
                                                            day: 'numeric',
                                                            month: 'long',
                                                            year: 'numeric',
                                                        })
                                                        : 'Unknown date'}
                                                </div>
                                            </div>
                                            {review.via_review_mate && (
                                                <Badge className="ml-auto bg-teal-100 text-teal-700 hover:bg-teal-100">
                                                    via ReviewMate
                                                </Badge>
                                            )}
                                        </div>
                                    </CardHeader>
                                    <CardContent className="space-y-3 pt-5">
                                        <StarRating rating={review.rating} />
                                        {review.body ? (
                                            <blockquote className="border-l-4 border-gray-200 pl-4 text-gray-700 italic leading-relaxed">
                                                "{review.body}"
                                            </blockquote>
                                        ) : (
                                            <p className="text-sm text-gray-400 italic">No written review</p>
                                        )}

                                        {/* Collapsible reply */}
                                        {review.google_reply && (
                                            <div className="rounded-lg border border-teal-100 bg-teal-50">
                                                <button
                                                    type="button"
                                                    className="flex w-full items-center justify-between px-4 py-3 text-sm font-medium text-teal-700"
                                                    onClick={() => toggleReply(review.id)}
                                                >
                                                    <span>View Reply</span>
                                                    <svg
                                                        className={`h-4 w-4 transition-transform ${expandedReplies[review.id] ? 'rotate-180' : ''}`}
                                                        fill="none"
                                                        viewBox="0 0 24 24"
                                                        stroke="currentColor"
                                                    >
                                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M19 9l-7 7-7-7" />
                                                    </svg>
                                                </button>
                                                {expandedReplies[review.id] && (
                                                    <div className="border-t border-teal-100 px-4 py-3">
                                                        <p className="text-sm text-teal-900 leading-relaxed">{review.google_reply}</p>
                                                        {review.google_reply_posted_at && (
                                                            <p className="mt-2 text-xs text-teal-600">
                                                                Posted{' '}
                                                                {new Date(review.google_reply_posted_at!).toLocaleDateString('en-AU', {
                                                                    day: 'numeric',
                                                                    month: 'long',
                                                                    year: 'numeric',
                                                                })}
                                                            </p>
                                                        )}
                                                    </div>
                                                )}
                                            </div>
                                        )}
                                    </CardContent>
                                </Card>
                            ))}
                        </div>

                        {replied.links.length > 3 && (
                            <div className="flex items-center justify-center gap-1">
                                {replied.links.map((link, i) => (
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
                    </section>
                )}

                {/* All Reviews Section (non-Google reviews) */}
                {allReviews.total > 0 && (
                    <section className="space-y-4">
                        <div className="flex items-center gap-2">
                            <MessageSquare className="h-5 w-5 text-gray-500" />
                            <h2 className="text-lg font-semibold text-gray-900">All Reviews</h2>
                            <Badge className="bg-gray-100 text-gray-600 hover:bg-gray-100">{allReviews.total}</Badge>
                        </div>

                        <div className="space-y-4">
                            {allReviews.data.map((review) => (
                                <Card key={review.id}>
                                    <CardHeader className="border-b bg-gray-50">
                                        <div className="flex items-center gap-3">
                                            <div className="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-teal-600 text-sm font-bold text-white">
                                                {review.reviewer_name.charAt(0).toUpperCase()}
                                            </div>
                                            <div>
                                                <div className="font-semibold text-gray-900">{review.reviewer_name}</div>
                                                <div className="text-xs text-gray-500">
                                                    {review.reviewed_at
                                                        ? new Date(review.reviewed_at).toLocaleDateString('en-AU', {
                                                            day: 'numeric',
                                                            month: 'long',
                                                            year: 'numeric',
                                                        })
                                                        : 'Unknown date'}
                                                </div>
                                            </div>
                                            {review.via_review_mate && (
                                                <Badge className="ml-auto bg-teal-100 text-teal-700 hover:bg-teal-100">
                                                    via ReviewMate
                                                </Badge>
                                            )}
                                        </div>
                                    </CardHeader>
                                    <CardContent className="space-y-3 pt-5">
                                        <StarRating rating={review.rating} />
                                        {review.body ? (
                                            <blockquote className="border-l-4 border-gray-200 pl-4 text-gray-700 italic leading-relaxed">
                                                "{review.body}"
                                            </blockquote>
                                        ) : (
                                            <p className="text-sm text-gray-400 italic">No written review</p>
                                        )}
                                    </CardContent>
                                </Card>
                            ))}
                        </div>

                        {allReviews.links.length > 3 && (
                            <div className="flex items-center justify-center gap-1">
                                {allReviews.links.map((link, i) => (
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
                    </section>
                )}
            </div>
        </AppLayout>
    );
}
