import { Head, router } from '@inertiajs/react';
import { MessageSquare, CheckCircle, AlertCircle } from 'lucide-react';
import { useState } from 'react';
import { motion, AnimatePresence } from 'framer-motion';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Textarea } from '@/components/ui/textarea';
import AppLayout from '@/layouts/app-layout';
import { index as reviewsIndex, reply as replyRoute, replySuggestions as replySuggestionsRoute } from '@/routes/reviews';
import type { BreadcrumbItem } from '@/types';

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
                <svg key={star} className={`h-4 w-4 ${star <= rating ? 'text-yellow-400' : 'text-muted'}`} fill="currentColor" viewBox="0 0 20 20">
                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                </svg>
            ))}
        </div>
    );
}

export default function ReviewsIndex({ needsReply, replied, allReviews, isGoogleConnected }: Props) {
    const [reviewStates, setReviewStates] = useState<Record<number, ReviewState>>({});
    const [expandedReplies, setExpandedReplies] = useState<Record<number, boolean>>({});

    // Bulk reply state
    const [selectedIds, setSelectedIds] = useState<Set<number>>(new Set());
    const [bulkReply, setBulkReply] = useState('');

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
        fetch(replySuggestionsRoute(review.id).url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content ?? '',
                'Accept': 'application/json',
            },
            body: JSON.stringify({}),
        })
            .then((res) => res.json())
            .then((data: { suggestions?: string[] }) => {
                if (data.suggestions && Array.isArray(data.suggestions)) {
                    updateReviewState(review.id, { suggestions: data.suggestions, selected: null });
                }
            })
            .finally(() => updateReviewState(review.id, { loading: false }));
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

    // Bulk selection handlers
    const toggleSelect = (id: number) => {
        setSelectedIds((prev) => {
            const next = new Set(prev);
            if (next.has(id)) {
                next.delete(id);
            } else {
                next.add(id);
            }
            return next;
        });
    };

    const allSelected = needsReply.data.length > 0 && needsReply.data.every((r) => selectedIds.has(r.id));
    const someSelected = needsReply.data.some((r) => selectedIds.has(r.id));

    const toggleSelectAll = () => {
        if (allSelected) {
            setSelectedIds(new Set());
        } else {
            setSelectedIds(new Set(needsReply.data.map((r) => r.id)));
        }
    };

    const handleBulkReply = () => {
        if (!bulkReply.trim() || selectedIds.size === 0) return;
        router.post(
            '/reviews/bulk-reply',
            { review_ids: Array.from(selectedIds), reply: bulkReply },
            {
                onSuccess: () => {
                    setSelectedIds(new Set());
                    setBulkReply('');
                },
            },
        );
    };

    const isEmpty = needsReply.total === 0 && replied.total === 0 && allReviews.total === 0;

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Reviews" />
            <div className="flex h-full flex-1 flex-col gap-6 p-4 md:p-6">
                {/* Header */}
                <div className="flex items-center gap-3">
                    <div>
                        <h1 className="text-2xl font-bold text-foreground">Reviews</h1>
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
                {!isGoogleConnected && needsReply.total === 0 && (
                    <div className="flex items-start gap-3 rounded-lg border border-amber-200 bg-amber-50 p-4">
                        <AlertCircle className="mt-0.5 h-5 w-5 shrink-0 text-amber-600" />
                        <p className="text-sm text-amber-800">
                            <a href="/settings/business" className="font-medium underline hover:text-amber-900">Connect Google Business Profile to start collecting reviews →</a>
                        </p>
                    </div>
                )}

                {/* Empty state */}
                {isEmpty && (
                    <div className="flex flex-col items-center justify-center py-20 text-center">
                        <div className="mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-muted">
                            <MessageSquare className="h-8 w-8 text-muted-foreground" />
                        </div>
                        <h3 className="mb-1 text-base font-semibold text-foreground">No reviews yet — your requests are working</h3>
                        <p className="text-sm text-muted-foreground">
                            Most customers reply within 1–3 days — we'll notify you the moment a review comes in. Check back in 24 hours or so.
                        </p>
                    </div>
                )}

                {/* Average rating summary */}
                {allReviews.total > 0 && (() => {
                    const allReviewsList = allReviews.data;
                    const totalRated = allReviewsList.filter((r) => r.rating > 0);
                    if (totalRated.length === 0) return null;
                    const averageRating = totalRated.reduce((sum, r) => sum + r.rating, 0) / totalRated.length;
                    return (
                        <p className="text-sm text-muted-foreground">
                            {averageRating.toFixed(1)} average across {allReviews.total} review{allReviews.total !== 1 ? 's' : ''}
                        </p>
                    );
                })()}

                {/* Needs Reply Section */}
                {needsReply.total > 0 && (
                    <section className="space-y-4">
                        <div className="flex items-center gap-2">
                            <AlertCircle className="h-5 w-5 text-amber-500" />
                            <h2 className="text-lg font-semibold text-foreground">Needs Reply</h2>
                            <Badge className="bg-amber-100 text-amber-700 hover:bg-amber-100">{needsReply.total}</Badge>

                            {/* Select all checkbox */}
                            <div className="ml-auto flex items-center gap-2">
                                <Checkbox
                                    id="select-all"
                                    checked={allSelected}
                                    data-state={someSelected && !allSelected ? 'indeterminate' : undefined}
                                    onCheckedChange={toggleSelectAll}
                                    aria-label="Select all reviews"
                                />
                                <label htmlFor="select-all" className="text-sm text-muted-foreground cursor-pointer select-none">
                                    Select all
                                </label>
                            </div>
                        </div>

                        <div className="space-y-4">
                            {needsReply.data.map((review) => {
                                const state = getReviewState(review.id);
                                const isSelected = selectedIds.has(review.id);
                                return (
                                    <Card key={review.id} className={isSelected ? 'ring-2 ring-teal-500' : ''}>
                                        <CardHeader className="border-b bg-muted">
                                            <div className="flex items-center gap-3">
                                                {/* Per-review checkbox */}
                                                <Checkbox
                                                    checked={isSelected}
                                                    onCheckedChange={() => toggleSelect(review.id)}
                                                    aria-label={`Select review by ${review.reviewer_name}`}
                                                />
                                                <div className="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-teal-600 text-sm font-bold text-white">
                                                    {review.reviewer_name.charAt(0).toUpperCase()}
                                                </div>
                                                <div>
                                                    <div className="font-semibold text-foreground">{review.reviewer_name}</div>
                                                    <div className="text-xs text-muted-foreground">
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
                                        <CardContent className="space-y-4 pt-5">
                                            <StarRating rating={review.rating} />
                                            {review.body ? (
                                                <blockquote className="border-l-4 border-teal-200 pl-4 text-foreground italic leading-relaxed">
                                                    "{review.body}"
                                                </blockquote>
                                            ) : (
                                                <p className="text-sm text-muted-foreground italic">No written review</p>
                                            )}

                                            {/* Suggestions area */}
                                            {state.suggestions.length === 0 ? (
                                                <Button
                                                    className="bg-teal-600 hover:bg-teal-700 text-white"
                                                    onClick={() => handleGetSuggestions(review)}
                                                    disabled={state.loading}
                                                >
                                                    {state.loading ? 'Generating...' : 'Generate AI reply'}
                                                </Button>
                                            ) : (
                                                <div className="space-y-3">
                                                    <p className="text-sm font-medium text-foreground">Choose a reply suggestion</p>
                                                    {state.suggestions.map((suggestion, index) => (
                                                        <motion.div
                                                            key={index}
                                                            initial={{ opacity: 0, x: -8 }}
                                                            animate={{ opacity: 1, x: 0 }}
                                                            transition={{ delay: index * 0.08, duration: 0.25 }}
                                                        >
                                                        <button
                                                            type="button"
                                                            onClick={() => handleSelectSuggestion(review.id, index, state.suggestions)}
                                                            className={`w-full rounded-lg border-2 p-4 text-left text-sm leading-relaxed transition ${
                                                                state.selected === index
                                                                    ? 'border-teal-600 bg-teal-50 text-teal-900'
                                                                    : 'border-border text-foreground hover:border-border'
                                                            }`}
                                                        >
                                                            <div className="mb-1 flex items-center gap-2">
                                                                <div
                                                                    className={`flex h-5 w-5 shrink-0 items-center justify-center rounded-full border-2 ${
                                                                        state.selected === index
                                                                            ? 'border-teal-600 bg-teal-600'
                                                                            : 'border-border'
                                                                    }`}
                                                                >
                                                                    {state.selected === index && (
                                                                        <svg className="h-3 w-3 text-white" fill="currentColor" viewBox="0 0 12 12">
                                                                            <path d="M3.707 5.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4a1 1 0 00-1.414-1.414L5 6.586 3.707 5.293z" />
                                                                        </svg>
                                                                    )}
                                                                </div>
                                                                <span className="text-xs font-semibold text-muted-foreground">
                                                                    Option {index + 1}
                                                                </span>
                                                            </div>
                                                            {suggestion}
                                                        </button>
                                                        </motion.div>
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
                            <h2 className="text-lg font-semibold text-foreground">Replied</h2>
                            <Badge className="bg-teal-100 text-teal-700 hover:bg-teal-100">{replied.total}</Badge>
                        </div>

                        <div className="space-y-4">
                            {replied.data.map((review) => (
                                <Card key={review.id}>
                                    <CardHeader className="border-b bg-muted">
                                        <div className="flex items-center gap-3">
                                            <div className="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-teal-600 text-sm font-bold text-white">
                                                {review.reviewer_name.charAt(0).toUpperCase()}
                                            </div>
                                            <div>
                                                <div className="font-semibold text-foreground">{review.reviewer_name}</div>
                                                <div className="text-xs text-muted-foreground">
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
                                            <blockquote className="border-l-4 border-border pl-4 text-foreground italic leading-relaxed">
                                                "{review.body}"
                                            </blockquote>
                                        ) : (
                                            <p className="text-sm text-muted-foreground italic">No written review</p>
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
                            <MessageSquare className="h-5 w-5 text-muted-foreground" />
                            <h2 className="text-lg font-semibold text-foreground">All Reviews</h2>
                            <Badge className="bg-muted text-muted-foreground hover:bg-muted">{allReviews.total}</Badge>
                        </div>

                        <AnimatePresence>
                            {allReviews.total === 1 && (
                                <motion.div
                                    initial={{ opacity: 0, y: -10 }}
                                    animate={{ opacity: 1, y: 0 }}
                                    exit={{ opacity: 0, y: -10 }}
                                    transition={{ duration: 0.4, ease: 'easeOut' }}
                                    className="mb-4 rounded-lg bg-teal-50 border border-teal-200 px-4 py-3"
                                >
                                    <p className="text-sm font-medium text-teal-800">🎉 Your first review via ReviewMate — nice work! Reply to it below to show customers you care.</p>
                                </motion.div>
                            )}
                        </AnimatePresence>

                        <div className="space-y-4">
                            {allReviews.data.map((review) => (
                                <Card key={review.id}>
                                    <CardHeader className="border-b bg-muted">
                                        <div className="flex items-center gap-3">
                                            <div className="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-teal-600 text-sm font-bold text-white">
                                                {review.reviewer_name.charAt(0).toUpperCase()}
                                            </div>
                                            <div>
                                                <div className="font-semibold text-foreground">{review.reviewer_name}</div>
                                                <div className="text-xs text-muted-foreground">
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
                                            <blockquote className="border-l-4 border-border pl-4 text-foreground italic leading-relaxed">
                                                "{review.body}"
                                            </blockquote>
                                        ) : (
                                            <p className="text-sm text-muted-foreground italic">No written review</p>
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

            {/* Floating bulk action bar */}
            <AnimatePresence>
                {selectedIds.size > 0 && (
                    <motion.div
                        initial={{ y: 100, opacity: 0 }}
                        animate={{ y: 0, opacity: 1 }}
                        exit={{ y: 100, opacity: 0 }}
                        transition={{ type: 'spring', stiffness: 300, damping: 30 }}
                        className="fixed bottom-0 left-0 right-0 z-50 border-t border-teal-200 bg-white shadow-lg p-4"
                    >
                        <div className="mx-auto max-w-3xl space-y-3">
                            <div className="flex items-center justify-between">
                                <span className="text-sm font-semibold text-teal-700">
                                    {selectedIds.size} review{selectedIds.size !== 1 ? 's' : ''} selected
                                </span>
                                <button
                                    type="button"
                                    className="text-xs text-muted-foreground hover:text-foreground"
                                    onClick={() => setSelectedIds(new Set())}
                                >
                                    Deselect all
                                </button>
                            </div>
                            <Textarea
                                placeholder="Write a reply to post on all selected reviews..."
                                value={bulkReply}
                                onChange={(e) => setBulkReply(e.target.value)}
                                rows={3}
                                className="resize-none"
                            />
                            <Button
                                className="w-full bg-teal-600 hover:bg-teal-700 text-white"
                                disabled={!bulkReply.trim()}
                                onClick={handleBulkReply}
                            >
                                Reply to {selectedIds.size} selected review{selectedIds.size !== 1 ? 's' : ''}
                            </Button>
                        </div>
                    </motion.div>
                )}
            </AnimatePresence>
        </AppLayout>
    );
}
