<?php

namespace App\Mail;

use App\Models\Business;
use App\Models\Customer;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class FollowUpMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public string $renderedSubject;

    public string $renderedBody;

    public function __construct(
        public Business $business,
        public Customer $customer,
    ) {
        $template = $business->emailTemplates()->where('type', 'followup')->first();

        $variables = [
            'customer_name' => $customer->name,
            'business_name' => $business->name,
            'owner_name' => $business->owner_name ?? $business->user->name,
            'review_link' => $business->googleReviewUrl(),
        ];

        $subjectTemplate = $template?->subject ?? "A quick reminder from {$business->name}";
        $this->renderedSubject = str_replace(
            array_map(fn ($k) => "{{$k}}", array_keys($variables)),
            array_values($variables),
            $subjectTemplate
        );

        $this->renderedBody = $template
            ? $template->renderBody($variables)
            : "Hi {$customer->name},\n\nWe just wanted to follow up — we'd love your feedback!\n\n{$business->googleReviewUrl()}";
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->renderedSubject,
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.follow-up',
            with: [
                'customerName' => $this->customer->name,
                'businessName' => $this->business->name,
                'ownerName' => $this->business->owner_name ?? $this->business->user->name,
                'reviewLink' => $this->business->googleReviewUrl(),
                'body' => $this->renderedBody,
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
