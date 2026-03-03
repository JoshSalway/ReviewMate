import { Head, router } from '@inertiajs/react';
import { useState } from 'react';
import AppLayout from '@/layouts/app-layout';
import { replySuggestions as replySuggestionsRoute, reply as replyRoute } from '@/routes/reviews';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Textarea } from '@/components/ui/textarea';
import { Label } from '@/components/ui/label';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import type { BreadcrumbItem } from '@/types';

interface Review {
    id: number;
    rating: number;
    body: string | null;
    reviewer_name: string;
    reviewed_at: string | null;
    via_review_mate: boolean;
    has_google_link: boolean;
    google_reply: string | null;
}

interface ReplyTemplate {
    id: number;
    name: string;
    body: string;
}

interface Props {
    review: Review;
    replyTemplates: ReplyTemplate[];
}

function StarRating({ rating, size = 'md' }: { rating: number; size?: 'sm' | 'md' | 'lg' }) {
    const sizeClass = size === 'sm' ? 'h-4 w-4' : size === 'lg' ? 'h-7 w-7' : 'h-5 w-5';
    return (
        <div className="flex items-center gap-0.5">
            {[1, 2, 3, 4, 5].map((star) => (
                <svg
                    key={star}
                    className={`${sizeClass} ${star <= rating ? 'text-yellow-400' : 'text-gray-200'}`}
                    fill="currentColor"
                    viewBox="0 0 20 20"
                >
                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                </svg>
            ))}
        </div>
    );
}

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Reviews',
        href: '/reviews',
    },
];

export default function ReviewShow({ review, replyTemplates }: Props) {
    const [suggestions, setSuggestions] = useState<string[]>([]);
    const [selectedSuggestion, setSelectedSuggestion] = useState<number | null>(null);
    const [customReply, setCustomReply] = useState('');
    const [loadingSuggestions, setLoadingSuggestions] = useState(false);
    const [copied, setCopied] = useState(false);
    const [postingReply, setPostingReply] = useState(false);
    const [replyPosted, setReplyPosted] = useState(!!review.google_reply);

    const handleGetSuggestions = () => {
        setLoadingSuggestions(true);
        router.post(
            replySuggestionsRoute(review.id).url,
            {},
            {
                preserveScroll: true,
                onSuccess: (page: any) => {
                    const data = page.props.suggestions as string[] | undefined;
                    if (data && Array.isArray(data)) {
                        setSuggestions(data);
                    }
                },
                onFinish: () => setLoadingSuggestions(false),
            },
        );
    };

    const handleSelectSuggestion = (index: number) => {
        setSelectedSuggestion(index);
        setCustomReply(suggestions[index] ?? '');
    };

    const handleCopyReply = () => {
        if (customReply) {
            navigator.clipboard.writeText(customReply);
            setCopied(true);
            setTimeout(() => setCopied(false), 2000);
        }
    };

    const handlePostReply = () => {
        if (!customReply) return;
        setPostingReply(true);
        router.post(
            replyRoute(review.id).url,
            { reply: customReply },
            {
                preserveScroll: true,
                onSuccess: () => setReplyPosted(true),
                onFinish: () => setPostingReply(false),
            },
        );
    };

    const ratingLabels: Record<number, string> = {
        1: 'Poor',
        2: 'Below Average',
        3: 'Average',
        4: 'Good',
        5: 'Excellent',
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Review from ${review.reviewer_name}`} />
            <div className="flex h-full flex-1 flex-col gap-6 p-4 md:p-6">
                <div>
                    <h1 className="text-2xl font-bold text-gray-900">Review Details</h1>
                </div>

                <div className="grid gap-6 lg:grid-cols-2">
                    {/* Review Card */}
                    <Card>
                        <CardHeader className="border-b bg-gray-50">
                            <div className="flex items-start justify-between">
                                <div>
                                    <div className="flex items-center gap-2">
                                        <div className="flex h-10 w-10 items-center justify-center rounded-full bg-teal-600 text-sm font-bold text-white">
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
                                    </div>
                                </div>
                                {review.via_review_mate && (
                                    <Badge className="bg-teal-100 text-teal-700 hover:bg-teal-100">
                                        via ReviewMate
                                    </Badge>
                                )}
                            </div>
                        </CardHeader>
                        <CardContent className="pt-5">
                            <div className="mb-4 flex items-center gap-3">
                                <StarRating rating={review.rating} size="lg" />
                                <span className="text-sm font-medium text-gray-600">
                                    {ratingLabels[review.rating] ?? `${review.rating} stars`}
                                </span>
                            </div>
                            {review.body ? (
                                <blockquote className="border-l-4 border-teal-200 pl-4 text-gray-700 italic leading-relaxed">
                                    "{review.body}"
                                </blockquote>
                            ) : (
                                <p className="text-sm text-gray-400 italic">No written review</p>
                            )}
                        </CardContent>
                    </Card>

                    {/* AI Reply Suggestions */}
                    <Card>
                        <CardHeader>
                            <CardTitle className="text-base font-semibold">Reply to this Review</CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            {suggestions.length === 0 ? (
                                <div className="rounded-lg border border-dashed border-gray-200 p-6 text-center">
                                    <div className="mb-3 flex justify-center">
                                        <svg className="h-10 w-10 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09zM18.259 8.715L18 9.75l-.259-1.035a3.375 3.375 0 00-2.455-2.456L14.25 6l1.036-.259a3.375 3.375 0 002.455-2.456L18 2.25l.259 1.035a3.375 3.375 0 002.456 2.456L21.75 6l-1.035.259a3.375 3.375 0 00-2.456 2.456zM16.894 20.567L16.5 21.75l-.394-1.183a2.25 2.25 0 00-1.423-1.423L13.5 18.75l1.183-.394a2.25 2.25 0 001.423-1.423l.394-1.183.394 1.183a2.25 2.25 0 001.423 1.423l1.183.394-1.183.394a2.25 2.25 0 00-1.423 1.423z" />
                                        </svg>
                                    </div>
                                    <p className="mb-4 text-sm text-gray-500">Generate AI reply suggestions tailored to this review.</p>
                                    <Button
                                        className="bg-teal-600 hover:bg-teal-700 text-white"
                                        onClick={handleGetSuggestions}
                                        disabled={loadingSuggestions}
                                    >
                                        {loadingSuggestions ? 'Generating...' : 'Get AI Suggestions'}
                                    </Button>
                                </div>
                            ) : (
                                <div className="space-y-3">
                                    <Label className="text-sm font-medium">Choose a reply suggestion</Label>
                                    {suggestions.map((suggestion, index) => (
                                        <button
                                            key={index}
                                            type="button"
                                            onClick={() => handleSelectSuggestion(index)}
                                            className={`w-full rounded-lg border-2 p-4 text-left text-sm leading-relaxed transition ${
                                                selectedSuggestion === index
                                                    ? 'border-teal-600 bg-teal-50 text-teal-900'
                                                    : 'border-gray-200 text-gray-700 hover:border-gray-300'
                                            }`}
                                        >
                                            <div className="mb-1 flex items-center gap-2">
                                                <div className={`flex h-5 w-5 shrink-0 items-center justify-center rounded-full border-2 ${selectedSuggestion === index ? 'border-teal-600 bg-teal-600' : 'border-gray-300'}`}>
                                                    {selectedSuggestion === index && (
                                                        <svg className="h-3 w-3 text-white" fill="currentColor" viewBox="0 0 12 12">
                                                            <path d="M3.707 5.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4a1 1 0 00-1.414-1.414L5 6.586 3.707 5.293z" />
                                                        </svg>
                                                    )}
                                                </div>
                                                <span className="text-xs font-semibold text-gray-400">Option {index + 1}</span>
                                            </div>
                                            {suggestion}
                                        </button>
                                    ))}
                                    <div className="pt-1">
                                        <Button
                                            variant="ghost"
                                            size="sm"
                                            className="text-teal-600 hover:text-teal-700"
                                            onClick={handleGetSuggestions}
                                            disabled={loadingSuggestions}
                                        >
                                            {loadingSuggestions ? 'Regenerating...' : 'Regenerate suggestions'}
                                        </Button>
                                    </div>
                                </div>
                            )}

                            {/* Custom Reply Textarea */}
                            <div className="space-y-2">
                                <div className="flex items-center justify-between">
                                    <Label htmlFor="custom-reply">Your Reply</Label>
                                    {replyTemplates.length > 0 && (
                                        <DropdownMenu>
                                            <DropdownMenuTrigger asChild>
                                                <Button variant="ghost" size="sm" className="text-teal-600 hover:text-teal-700 h-7 text-xs">
                                                    Use template ▾
                                                </Button>
                                            </DropdownMenuTrigger>
                                            <DropdownMenuContent align="end" className="max-w-xs">
                                                {replyTemplates.map((t) => (
                                                    <DropdownMenuItem
                                                        key={t.id}
                                                        onClick={() => setCustomReply(t.body)}
                                                    >
                                                        {t.name}
                                                    </DropdownMenuItem>
                                                ))}
                                            </DropdownMenuContent>
                                        </DropdownMenu>
                                    )}
                                </div>
                                <Textarea
                                    id="custom-reply"
                                    placeholder="Write or edit your reply here..."
                                    value={customReply}
                                    onChange={(e) => setCustomReply(e.target.value)}
                                    rows={5}
                                />
                            </div>

                            {customReply && (
                                <div className="flex gap-2">
                                    <Button
                                        variant="outline"
                                        className="flex-1 border-teal-200 text-teal-700 hover:bg-teal-50"
                                        onClick={handleCopyReply}
                                    >
                                        {copied ? 'Copied!' : 'Copy Reply'}
                                    </Button>
                                    {review.has_google_link && !replyPosted && (
                                        <Button
                                            className="flex-1 bg-teal-600 hover:bg-teal-700 text-white"
                                            onClick={handlePostReply}
                                            disabled={postingReply}
                                        >
                                            {postingReply ? 'Posting...' : 'Post to Google'}
                                        </Button>
                                    )}
                                </div>
                            )}

                            {replyPosted && (
                                <div className="flex items-center gap-2 rounded-lg bg-green-50 px-4 py-3 text-sm text-green-700">
                                    <svg className="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M5 13l4 4L19 7" />
                                    </svg>
                                    Reply posted to Google successfully.
                                </div>
                            )}

                            {!review.has_google_link && (
                                <p className="text-xs text-gray-400">
                                    Tip: Connect Google Business Profile in Settings to post replies directly from ReviewMate.
                                </p>
                            )}
                        </CardContent>
                    </Card>
                </div>
            </div>
        </AppLayout>
    );
}
