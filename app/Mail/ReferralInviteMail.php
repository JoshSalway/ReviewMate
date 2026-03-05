<?php

namespace App\Mail;

use App\Models\Business;
use App\Models\Customer;
use App\Models\Referral;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ReferralInviteMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly Business $business,
        public readonly Customer $customer,
        public readonly Referral $referral,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Thanks for your review of {$this->business->name}!",
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.referral-invite',
            with: [
                'customerName' => $this->customer->name,
                'businessName' => $this->business->name,
                'referralUrl' => url('/r/ref/'.$this->referral->referral_token),
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
