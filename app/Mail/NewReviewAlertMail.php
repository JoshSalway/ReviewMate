<?php

namespace App\Mail;

use App\Models\Business;
use App\Models\Review;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class NewReviewAlertMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly User $user,
        public readonly Business $business,
        public readonly Review $review,
    ) {}

    public function envelope(): Envelope
    {
        $rating = $this->review->rating;
        $stars = str_repeat('★', $rating).str_repeat('☆', 5 - $rating);

        return new Envelope(
            subject: "New {$rating}-star review on {$this->business->name} {$stars}",
        );
    }

    public function content(): Content
    {
        return new Content(markdown: 'emails.new-review-alert');
    }
}
