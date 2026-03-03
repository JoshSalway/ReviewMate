<?php

namespace App\Mail;

use App\Models\Business;
use App\Models\Customer;
use App\Models\EmailTemplate;
use App\Models\ReviewRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ReviewRequestMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public string $renderedSubject;

    public string $renderedBody;

    public function __construct(
        public Business $business,
        public Customer $customer,
        public ?ReviewRequest $reviewRequest = null,
    ) {
        $trackingUrl = $reviewRequest?->tracking_token
            ? url('/r/' . $reviewRequest->tracking_token)
            : $business->googleReviewUrl();

        $template = $business->emailTemplates()->where('type', 'request')->first();

        $variables = [
            'customer_name' => $customer->name,
            'business_name' => $business->name,
            'owner_name' => $business->owner_name ?? $business->user->name,
            'review_link' => $trackingUrl,
        ];

        $this->renderedSubject = $template
            ? $template->renderBody(['customer_name' => $customer->name, 'business_name' => $business->name, 'owner_name' => $variables['owner_name'], 'review_link' => $variables['review_link']])
            : "How was your experience with {$business->name}?";

        $this->renderedBody = $template
            ? $template->renderBody($variables)
            : "Hi {$customer->name},\n\nWe'd love to hear about your experience. Please leave us a review:\n\n{$variables['review_link']}";

        $subjectTemplate = $template?->subject ?? "How was your experience with {$business->name}?";
        $this->renderedSubject = str_replace(
            array_map(fn ($k) => "{{$k}}", array_keys($variables)),
            array_values($variables),
            $subjectTemplate
        );

        $this->reviewLink = $variables['review_link'];
        $this->unsubscribeUrl = $customer->unsubscribe_token
            ? url('/unsubscribe/' . $customer->unsubscribe_token)
            : null;
    }

    public string $reviewLink;

    public ?string $unsubscribeUrl;

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->renderedSubject,
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.review-request',
            with: [
                'customerName' => $this->customer->name,
                'businessName' => $this->business->name,
                'ownerName' => $this->business->owner_name ?? $this->business->user->name,
                'reviewLink' => $this->reviewLink,
                'unsubscribeUrl' => $this->unsubscribeUrl ?? url('/'),
                'body' => $this->renderedBody,
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
