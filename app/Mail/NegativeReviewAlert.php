<?php

namespace App\Mail;

use App\Models\Business;
use App\Models\Review;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class NegativeReviewAlert extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly Review $review,
        public readonly Business $business,
    ) {}

    public function envelope(): Envelope
    {
        $rating = $this->review->rating;
        $stars = str_repeat('★', $rating).str_repeat('☆', 5 - $rating);
        $reviewer = $this->review->reviewer_name ?? 'Anonymous';

        return new Envelope(
            subject: "New {$rating}{$stars} review needs your attention — {$reviewer}",
        );
    }

    public function content(): Content
    {
        return new Content(markdown: 'emails.negative-review-alert');
    }
}
