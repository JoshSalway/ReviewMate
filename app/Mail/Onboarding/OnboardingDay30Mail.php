<?php

namespace App\Mail\Onboarding;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OnboardingDay30Mail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public readonly User $user) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "{$this->user->name}, you've been using ReviewMate for a month",
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.onboarding.day30',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
