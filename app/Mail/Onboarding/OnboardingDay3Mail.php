<?php

namespace App\Mail\Onboarding;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OnboardingDay3Mail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public readonly User $user) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Quick tip — most businesses get their first review within 48 hours',
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.onboarding.day3',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
