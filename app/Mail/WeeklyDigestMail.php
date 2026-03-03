<?php

namespace App\Mail;

use App\Models\Business;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class WeeklyDigestMail extends Mailable
{
    use Queueable, SerializesModels;

    public array $stats;

    public function __construct(
        public readonly User $user,
        public readonly Business $business,
    ) {
        $weekAgo = now()->subWeek();

        $this->stats = [
            'new_reviews' => $business->reviews()->where('reviewed_at', '>=', $weekAgo)->count(),
            'total_reviews' => $business->reviews()->count(),
            'average_rating' => round($business->reviews()->avg('rating') ?? 0, 1),
            'pending_replies' => $business->reviews()
                ->whereNotNull('google_review_name')
                ->whereNull('google_reply')
                ->count(),
            'requests_sent' => $business->reviewRequests()
                ->where('created_at', '>=', $weekAgo)
                ->count(),
        ];
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Your weekly ReviewMate digest — {$this->business->name}",
        );
    }

    public function content(): Content
    {
        return new Content(markdown: 'emails.weekly-digest');
    }
}
