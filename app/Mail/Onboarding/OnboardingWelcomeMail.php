<?php

namespace App\Mail\Onboarding;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OnboardingWelcomeMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public readonly User $user) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Welcome to ReviewMate — here\'s how to get your first review today',
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.onboarding.welcome',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
