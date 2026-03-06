<?php

namespace App\Mail;

use App\Models\WaitlistEntry;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class WaitlistConfirmationMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public WaitlistEntry $entry,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "You're on the ReviewMate waitlist!",
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.waitlist-confirmation',
            with: [
                'name' => $this->entry->name,
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
