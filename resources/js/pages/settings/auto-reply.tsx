import { Head, router } from '@inertiajs/react';
import type { RequestPayload } from '@inertiajs/core';
import { useState } from 'react';
import { AlertCircle, Clock, Sparkles, Zap } from 'lucide-react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Auto-Reply', href: '/settings/auto-reply' },
];

type Tone = 'professional' | 'friendly' | 'casual' | 'enthusiastic';
type Length = 'short' | 'medium' | 'long';

interface Settings {
    auto_reply_enabled: boolean;
    auto_reply_min_rating: number;
    auto_reply_tone: Tone;
    auto_reply_length: Length;
    auto_reply_signature: string;
    auto_reply_custom_instructions: string;
}

interface Schedule {
    timezone: string;
    timezone_abbr: string;
    next_run_at: string;
    last_run_at: string | null;
    last_reply_count: number;
    follow_up_time: string;
    auto_reply_time: string;
}

interface Props {
    settings: Settings;
    schedule: Schedule;
    businessType: string;
    businessName: string;
    isGoogleConnected: boolean;
    isProPlan: boolean;
}

const toneOptions: { value: Tone; label: string; description: string }[] = [
    { value: 'professional', label: 'Professional', description: 'Formal, polished, business-like' },
    { value: 'friendly', label: 'Friendly', description: 'Warm and approachable' },
    { value: 'casual', label: 'Casual', description: 'Relaxed, conversational' },
    { value: 'enthusiastic', label: 'Enthusiastic', description: 'Energetic and upbeat' },
];

const lengthOptions: { value: Length; label: string; description: string }[] = [
    { value: 'short', label: 'Short', description: '1–2 sentences' },
    { value: 'medium', label: 'Medium', description: '2–3 sentences' },
    { value: 'long', label: 'Long', description: '4–5 sentences' },
];

const businessTypeTips: Record<string, { tip: string; exampleInstructions: string; sampleReview: string }> = {
    tradie: {
        tip: 'Tradies get more repeat bookings when replies mention the specific job and offer to help again.',
        exampleInstructions: 'Mention we offer free quotes for future jobs. Reference the suburb if it appears in the review.',
        sampleReview: 'Dave and his team did an amazing job fixing our burst pipe. Fast, professional and cleaned up everything. Will definitely use again!',
    },
    cafe: {
        tip: 'Hospitality replies work best when they\'re warm and invite customers back for a specific occasion.',
        exampleInstructions: 'Mention our weekend specials if relevant. Invite them back for brunch.',
        sampleReview: 'Best flat white in the area! Staff are always so friendly and the eggs benedict is incredible.',
    },
    salon: {
        tip: 'Salon replies should feel personal — mention the stylist and encourage the next booking.',
        exampleInstructions: 'Encourage them to book their next appointment online. Mention we\'re always taking new clients.',
        sampleReview: 'Sarah did an amazing job with my highlights. Best colour I\'ve ever had, exactly what I wanted!',
    },
    healthcare: {
        tip: 'Healthcare replies must stay privacy-safe. Focus on the patient experience, never mention specific treatments.',
        exampleInstructions: 'Keep replies HIPAA/privacy compliant. Never mention health conditions. Focus on comfort and care.',
        sampleReview: 'The whole team made me feel so comfortable. Really professional and thorough.',
    },
    real_estate: {
        tip: 'Real estate replies build trust. Acknowledge the emotion of the buying or selling journey.',
        exampleInstructions: 'Reference the local market knowledge and personalised service we provide.',
        sampleReview: 'James guided us through the whole selling process with such patience and expertise. Couldn\'t be happier with the result.',
    },
    retail: {
        tip: 'Retail replies should feel warm and invite the customer back to discover new products.',
        exampleInstructions: 'Invite them to check out our new arrivals. Mention our loyalty program if appropriate.',
        sampleReview: 'Such a lovely store! The staff helped me find the perfect gift and wrapped it beautifully.',
    },
    pet_services: {
        tip: 'Pet business replies should be warm and mention the pet by name if it appears in the review.',
        exampleInstructions: 'Mention the pet by name if the reviewer included it. Show genuine love for animals.',
        sampleReview: 'Bella came home looking absolutely beautiful! She seemed so relaxed and happy. Best groomer we\'ve found.',
    },
    fitness: {
        tip: 'Fitness replies should be motivating and celebrate the customer\'s commitment to their goals.',
        exampleInstructions: 'Be energetic and motivating. Celebrate their fitness journey.',
        sampleReview: 'Six months in and I\'m seeing real results. The coaches here genuinely care about your progress.',
    },
    other: {
        tip: 'Keep replies specific to what the reviewer mentioned — personalisation beats generic every time.',
        exampleInstructions: 'Always acknowledge the specific thing they praised.',
        sampleReview: 'Really impressed with the quality and attention to detail. Will be recommending to everyone I know.',
    },
};

function formatLocalDate(isoString: string): string {
    return new Date(isoString).toLocaleString('en-AU', {
        weekday: 'short',
        day: 'numeric',
        month: 'short',
        hour: 'numeric',
        minute: '2-digit',
        hour12: true,
    });
}

export default function AutoReplySettings({ settings, schedule, businessType, businessName, isGoogleConnected, isProPlan }: Props) {
    const [form, setForm] = useState<Settings>(settings);
    const [processing, setProcessing] = useState(false);
    const [preview, setPreview] = useState('');
    const [previewLoading, setPreviewLoading] = useState(false);
    const [sampleReview, setSampleReview] = useState(businessTypeTips[businessType]?.sampleReview ?? '');

    const tips = businessTypeTips[businessType] ?? businessTypeTips.other;
    const isLocked = !isProPlan;

    const handleSave = () => {
        setProcessing(true);
        router.put('/settings/auto-reply', form as unknown as RequestPayload, {
            preserveScroll: true,
            onFinish: () => setProcessing(false),
        });
    };

    const handlePreview = () => {
        setPreviewLoading(true);
        setPreview('');
        fetch('/settings/auto-reply/preview', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content ?? '',
                'Accept': 'application/json',
            },
            body: JSON.stringify({
                tone: form.auto_reply_tone,
                length: form.auto_reply_length,
                signature: form.auto_reply_signature || null,
                custom_instructions: form.auto_reply_custom_instructions || null,
                sample_review: sampleReview,
                sample_rating: 5,
            }),
        })
            .then((r) => r.json())
            .then((data: { preview?: string }) => setPreview(data.preview ?? ''))
            .finally(() => setPreviewLoading(false));
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Auto-Reply Settings" />
            <div className="flex h-full flex-1 flex-col gap-6 p-4 md:p-6 max-w-3xl">

                <div className="flex items-start justify-between">
                    <div>
                        <h1 className="text-2xl font-bold text-foreground">Auto-Reply to Reviews</h1>
                        <p className="mt-1 text-sm text-muted-foreground">
                            ReviewMate replies to your Google reviews every night — in your voice, automatically. Set it up once and forget about it.
                        </p>
                    </div>
                    {isProPlan ? (
                        <Badge className="bg-teal-100 text-teal-700 hover:bg-teal-100">Pro</Badge>
                    ) : (
                        <Badge className="bg-muted text-muted-foreground hover:bg-muted">Pro feature</Badge>
                    )}
                </div>

                {/* Pro gate */}
                {isLocked && (
                    <div className="flex items-start gap-3 rounded-lg border border-amber-200 bg-amber-50 p-4">
                        <AlertCircle className="mt-0.5 h-5 w-5 shrink-0 text-amber-600" />
                        <div>
                            <p className="text-sm font-medium text-amber-800">Pro plan required</p>
                            <p className="mt-0.5 text-sm text-amber-700">
                                Upgrade to Pro to enable auto-replies. You can configure settings now and activate when ready.
                            </p>
                        </div>
                    </div>
                )}

                {/* Google not connected */}
                {!isGoogleConnected && (
                    <div className="flex items-start gap-3 rounded-lg border border-amber-200 bg-amber-50 p-4">
                        <AlertCircle className="mt-0.5 h-5 w-5 shrink-0 text-amber-600" />
                        <p className="text-sm text-amber-800">
                            Connect Google Business Profile in{' '}
                            <a href="/settings/integrations" className="underline font-medium">Integrations</a>{' '}
                            before enabling auto-reply.
                        </p>
                    </div>
                )}

                {/* Enable toggle */}
                <Card>
                    <CardContent className="pt-6">
                        <div className="flex items-center justify-between">
                            <div>
                                <p className="font-semibold text-foreground">Enable auto-reply</p>
                                <p className="mt-0.5 text-sm text-muted-foreground">
                                    Runs nightly at {schedule.auto_reply_time} {schedule.timezone_abbr}. Replies to reviews that match your settings.
                                </p>
                                {!form.auto_reply_enabled && (
                                    <p className="mt-1.5 text-xs text-teal-700 font-medium">
                                        Most businesses that turn this on reply to 3× more reviews — without lifting a finger.
                                    </p>
                                )}
                            </div>
                            <button
                                type="button"
                                role="switch"
                                aria-checked={form.auto_reply_enabled}
                                disabled={isLocked || !isGoogleConnected}
                                onClick={() => setForm({ ...form, auto_reply_enabled: !form.auto_reply_enabled })}
                                className={`relative inline-flex h-6 w-11 shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 focus:outline-none disabled:opacity-40 ${
                                    form.auto_reply_enabled ? 'bg-teal-600' : 'bg-muted'
                                }`}
                            >
                                <span
                                    className={`inline-block h-5 w-5 rounded-full bg-white shadow ring-0 transition-transform duration-200 ${
                                        form.auto_reply_enabled ? 'translate-x-5' : 'translate-x-0'
                                    }`}
                                />
                            </button>
                        </div>
                    </CardContent>
                </Card>

                {/* Schedule info */}
                <Card>
                    <CardHeader>
                        <CardTitle className="text-base font-semibold flex items-center gap-2">
                            <Clock className="h-4 w-4 text-teal-600" />
                            Schedule
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div className="grid gap-4 sm:grid-cols-2">
                            <div className="rounded-lg border border-border bg-muted p-3">
                                <p className="text-xs font-medium text-muted-foreground uppercase tracking-wide">Auto-replies</p>
                                <p className="mt-1 text-sm font-semibold text-foreground">
                                    Nightly at {schedule.auto_reply_time} {schedule.timezone_abbr}
                                </p>
                                <p className="mt-0.5 text-xs text-muted-foreground">
                                    Next run: {formatLocalDate(schedule.next_run_at)}
                                </p>
                                <p className="mt-1 text-xs text-teal-700">ReviewMate will reply to any new reviews tonight.</p>
                            </div>
                            <div className="rounded-lg border border-border bg-muted p-3">
                                <p className="text-xs font-medium text-muted-foreground uppercase tracking-wide">Follow-up emails</p>
                                <p className="mt-1 text-sm font-semibold text-foreground">
                                    Daily at {schedule.follow_up_time} {schedule.timezone_abbr}
                                </p>
                                <p className="mt-0.5 text-xs text-muted-foreground">
                                    Sent to customers who haven't reviewed yet
                                </p>
                            </div>
                        </div>
                        {schedule.last_run_at ? (
                            <div className="mt-3 flex items-center gap-2 text-xs text-muted-foreground">
                                <span className="inline-block h-2 w-2 rounded-full bg-teal-400" />
                                Last run: {formatLocalDate(schedule.last_run_at)} —{' '}
                                <span className="font-medium text-foreground">
                                    {schedule.last_reply_count === 0
                                        ? 'No new reviews to reply to'
                                        : `Replied to ${schedule.last_reply_count} review${schedule.last_reply_count === 1 ? '' : 's'}`}
                                </span>
                            </div>
                        ) : (
                            <p className="mt-3 text-xs text-muted-foreground">Auto-reply hasn't run yet.</p>
                        )}
                        <p className="mt-2 text-xs text-muted-foreground">
                            Times shown in your business timezone ({schedule.timezone}).{' '}
                            <a href="/settings/business" className="text-teal-600 underline hover:text-teal-700">Change timezone</a>
                        </p>
                    </CardContent>
                </Card>

                {/* Minimum star rating */}
                <Card>
                    <CardHeader>
                        <CardTitle className="text-base font-semibold">Minimum star rating</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <p className="mb-4 text-sm text-muted-foreground">
                            Only auto-reply to reviews at or above this rating. Replies to lower-rated reviews are better handled personally.
                        </p>
                        <div className="flex gap-2">
                            {[1, 2, 3, 4, 5].map((star) => (
                                <button
                                    key={star}
                                    type="button"
                                    onClick={() => setForm({ ...form, auto_reply_min_rating: star })}
                                    className={`flex h-11 w-11 items-center justify-center rounded-lg border-2 text-sm font-semibold transition ${
                                        form.auto_reply_min_rating === star
                                            ? 'border-teal-600 bg-teal-50 text-teal-700'
                                            : 'border-border text-muted-foreground hover:border-border'
                                    }`}
                                >
                                    {star}★
                                </button>
                            ))}
                        </div>
                        <p className="mt-2 text-xs text-muted-foreground">
                            Currently: auto-reply to <strong>{form.auto_reply_min_rating}★ and above</strong>
                        </p>
                    </CardContent>
                </Card>

                {/* Tone */}
                <Card>
                    <CardHeader>
                        <CardTitle className="text-base font-semibold">Reply tone</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div className="grid grid-cols-2 gap-3 sm:grid-cols-4">
                            {toneOptions.map((opt) => (
                                <button
                                    key={opt.value}
                                    type="button"
                                    onClick={() => setForm({ ...form, auto_reply_tone: opt.value })}
                                    className={`rounded-lg border-2 p-3 text-left transition ${
                                        form.auto_reply_tone === opt.value
                                            ? 'border-teal-600 bg-teal-50'
                                            : 'border-border hover:border-border'
                                    }`}
                                >
                                    <div className={`text-sm font-semibold ${form.auto_reply_tone === opt.value ? 'text-teal-700' : 'text-foreground'}`}>
                                        {opt.label}
                                    </div>
                                    <div className="mt-0.5 text-xs text-muted-foreground">{opt.description}</div>
                                </button>
                            ))}
                        </div>
                        <p className="mt-3 text-xs text-muted-foreground">Not sure? Friendly works best for most local businesses.</p>
                    </CardContent>
                </Card>

                {/* Length */}
                <Card>
                    <CardHeader>
                        <CardTitle className="text-base font-semibold">Reply length</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div className="grid grid-cols-3 gap-3">
                            {lengthOptions.map((opt) => (
                                <button
                                    key={opt.value}
                                    type="button"
                                    onClick={() => setForm({ ...form, auto_reply_length: opt.value })}
                                    className={`rounded-lg border-2 p-3 text-left transition ${
                                        form.auto_reply_length === opt.value
                                            ? 'border-teal-600 bg-teal-50'
                                            : 'border-border hover:border-border'
                                    }`}
                                >
                                    <div className={`text-sm font-semibold ${form.auto_reply_length === opt.value ? 'text-teal-700' : 'text-foreground'}`}>
                                        {opt.label}
                                    </div>
                                    <div className="mt-0.5 text-xs text-muted-foreground">{opt.description}</div>
                                </button>
                            ))}
                        </div>
                    </CardContent>
                </Card>

                {/* Signature */}
                <Card>
                    <CardHeader>
                        <CardTitle className="text-base font-semibold">Sign-off / Signature</CardTitle>
                    </CardHeader>
                    <CardContent className="space-y-2">
                        <p className="text-sm text-muted-foreground">
                            How every reply ends. Leave blank to let the AI sign off naturally.
                        </p>
                        <Input
                            placeholder={`e.g. Thanks, John from ${businessName}`}
                            value={form.auto_reply_signature}
                            onChange={(e) => setForm({ ...form, auto_reply_signature: e.target.value })}
                            maxLength={200}
                        />
                    </CardContent>
                </Card>

                {/* Custom instructions — with business-type tip */}
                <Card>
                    <CardHeader>
                        <CardTitle className="text-base font-semibold">Custom instructions</CardTitle>
                    </CardHeader>
                    <CardContent className="space-y-3">
                        {/* Contextual tip */}
                        <div className="flex items-start gap-2 rounded-lg bg-teal-50 p-3">
                            <Sparkles className="mt-0.5 h-4 w-4 shrink-0 text-teal-600" />
                            <div>
                                <p className="text-xs font-medium text-teal-700">Tip for {businessType} businesses</p>
                                <p className="mt-0.5 text-xs text-teal-600">{tips.tip}</p>
                                {form.auto_reply_custom_instructions === '' && (
                                    <button
                                        type="button"
                                        onClick={() => setForm({ ...form, auto_reply_custom_instructions: tips.exampleInstructions })}
                                        className="mt-1.5 text-xs font-medium text-teal-700 underline hover:text-teal-800"
                                    >
                                        Use example instructions →
                                    </button>
                                )}
                            </div>
                        </div>

                        <Textarea
                            placeholder="e.g. Always mention we offer free quotes. Never mention competitor names. If they mention a staff member's name, acknowledge it."
                            value={form.auto_reply_custom_instructions}
                            onChange={(e) => setForm({ ...form, auto_reply_custom_instructions: e.target.value })}
                            rows={4}
                            maxLength={1000}
                        />
                        <p className="text-xs text-muted-foreground">
                            {form.auto_reply_custom_instructions.length}/1000 characters
                        </p>
                    </CardContent>
                </Card>

                {/* Live preview */}
                <Card>
                    <CardHeader>
                        <CardTitle className="text-base font-semibold flex items-center gap-2">
                            <Zap className="h-4 w-4 text-teal-600" />
                            Preview a reply
                        </CardTitle>
                    </CardHeader>
                    <CardContent className="space-y-3">
                        <p className="text-sm text-muted-foreground">
                            See exactly how the AI will reply to a review with your current settings.
                        </p>
                        <div className="space-y-2">
                            <Label htmlFor="sample-review">Sample review</Label>
                            <Textarea
                                id="sample-review"
                                value={sampleReview}
                                onChange={(e) => setSampleReview(e.target.value)}
                                rows={3}
                                placeholder="Paste a real or example review here..."
                            />
                        </div>
                        <Button
                            type="button"
                            variant="outline"
                            onClick={handlePreview}
                            disabled={previewLoading || !sampleReview.trim() || isLocked}
                            className="border-teal-600 text-teal-700 hover:bg-teal-50"
                        >
                            {previewLoading ? 'Generating...' : 'Generate preview'}
                        </Button>

                        {preview && (
                            <div className="mt-3 rounded-lg border border-teal-100 bg-teal-50 p-4">
                                <p className="mb-1 text-xs font-semibold uppercase tracking-wide text-teal-600">AI-generated reply</p>
                                <p className="text-sm text-foreground leading-relaxed whitespace-pre-wrap">{preview}</p>
                            </div>
                        )}
                    </CardContent>
                </Card>

                {/* Save */}
                <div className="flex items-center gap-4">
                    <Button
                        className="bg-teal-600 hover:bg-teal-700 text-white"
                        onClick={handleSave}
                        disabled={processing || isLocked}
                    >
                        {processing ? 'Saving...' : 'Save settings'}
                    </Button>
                    {isLocked && (
                        <a href="/settings/billing" className="text-sm text-teal-600 underline hover:text-teal-700">
                            Upgrade to Pro to activate →
                        </a>
                    )}
                </div>
            </div>
        </AppLayout>
    );
}
