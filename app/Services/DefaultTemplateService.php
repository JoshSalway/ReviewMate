<?php

namespace App\Services;

class DefaultTemplateService
{
    public static function forBusinessType(string $type): array
    {
        $templates = [
            'tradie' => [
                [
                    'type' => 'request',
                    'subject' => 'How was the job, {customer_name}?',
                    'body' => "Hi {customer_name},\n\nThanks for having us out! We really enjoyed working on your job and hope everything is looking great.\n\nWould you mind leaving us a quick Google review? It takes less than 60 seconds and really helps our business:\n\n{review_link}\n\nCheers,\n{owner_name}\n{business_name}",
                ],
                [
                    'type' => 'followup',
                    'subject' => 'Just checking in — how did we do?',
                    'body' => "Hi {customer_name},\n\nWe sent you a review request a few days ago and just wanted to follow up.\n\nYour feedback means the world to us — it only takes a minute:\n\n{review_link}\n\nThanks again for your business!\n{owner_name}",
                ],
                [
                    'type' => 'sms',
                    'subject' => null,
                    'body' => "Hi {customer_name}, thanks for choosing {business_name}! Got a minute to leave a review? It really helps: {review_link}",
                ],
            ],
            'cafe' => [
                [
                    'type' => 'request',
                    'subject' => 'How was your visit to {business_name}?',
                    'body' => "Hi {customer_name},\n\nThank you for stopping in! We hope you enjoyed your visit and the coffee was perfect.\n\nWould you mind sharing your experience on Google? It helps other coffee lovers find us:\n\n{review_link}\n\nSee you next time!\n{owner_name} & the team at {business_name}",
                ],
                [
                    'type' => 'followup',
                    'subject' => 'We\'d love to hear from you, {customer_name}!',
                    'body' => "Hi {customer_name},\n\nWe hope you had a great experience at {business_name}. We'd still love to hear your thoughts!\n\n{review_link}\n\nWarm regards,\n{owner_name}",
                ],
                [
                    'type' => 'sms',
                    'subject' => null,
                    'body' => "Hi {customer_name}! Thanks for visiting {business_name}. Would you leave us a quick review? {review_link} ☕",
                ],
            ],
            'salon' => [
                [
                    'type' => 'request',
                    'subject' => 'How are you loving your new look, {customer_name}?',
                    'body' => "Hi {customer_name},\n\nWe hope you're loving your new look! It was great having you in.\n\nIf you have a moment, we'd really appreciate a Google review:\n\n{review_link}\n\nYour feedback helps us grow!\nWith love,\n{owner_name} & the team at {business_name}",
                ],
                [
                    'type' => 'followup',
                    'subject' => 'Still loving your appointment?',
                    'body' => "Hi {customer_name},\n\nJust a gentle reminder to share your experience from your recent visit. It only takes 60 seconds:\n\n{review_link}\n\nThank you so much!\n{owner_name}",
                ],
                [
                    'type' => 'sms',
                    'subject' => null,
                    'body' => "Hi {customer_name}! Hope you're loving your visit to {business_name}. Mind leaving a quick review? {review_link} 💇",
                ],
            ],
        ];

        $defaultTemplates = [
            [
                'type' => 'request',
                'subject' => 'How was your experience with {business_name}?',
                'body' => "Hi {customer_name},\n\nThank you for choosing {business_name}. We really hope you had a great experience!\n\nWould you mind taking 60 seconds to leave us a Google review? It makes a huge difference:\n\n{review_link}\n\nThanks so much,\n{owner_name}\n{business_name}",
            ],
            [
                'type' => 'followup',
                'subject' => 'A quick reminder from {business_name}',
                'body' => "Hi {customer_name},\n\nWe just wanted to follow up on the review request we sent a few days ago.\n\nIf you have a moment, we'd love to hear about your experience:\n\n{review_link}\n\nThank you!\n{owner_name}",
            ],
            [
                'type' => 'sms',
                'subject' => null,
                'body' => "Hi {customer_name}, thanks for choosing {business_name}! Could you spare 60 seconds to leave a review? {review_link}",
            ],
        ];

        return $templates[$type] ?? $defaultTemplates;
    }
}
