<?php

namespace App\Mail;

use App\Models\Business;
use App\Models\Customer;
use App\Models\ReviewRequest;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PrivateFeedbackMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly User $user,
        public readonly Business $business,
        public readonly Customer $customer,
        public readonly ReviewRequest $reviewRequest,
    ) {}

    public function envelope(): Envelope
    {
        $rating = $this->reviewRequest->private_rating;
        $stars = str_repeat('★', $rating).str_repeat('☆', 5 - $rating);

        return new Envelope(
            subject: "Private feedback from {$this->customer->name} — {$rating} stars {$stars}",
        );
    }

    public function content(): Content
    {
        return new Content(markdown: 'emails.private-feedback');
    }
}
