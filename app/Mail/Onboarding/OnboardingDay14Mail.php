<?php

namespace App\Mail\Onboarding;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OnboardingDay14Mail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public readonly User $user) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Did you know ReviewMate can reply to reviews for you?',
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.onboarding.day14',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
