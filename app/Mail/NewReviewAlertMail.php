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
        $stars = str_repeat('★', $this->review->stars()) . str_repeat('☆', 5 - $this->review->stars());
        return new Envelope(
            subject: "New {$this->review->stars()}-star review on {$this->business->name} {$stars}",
        );
    }

    public function content(): Content
    {
        return new Content(markdown: 'emails.new-review-alert');
    }
}
