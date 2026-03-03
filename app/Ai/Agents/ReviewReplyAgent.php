<?php

namespace App\Ai\Agents;

use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Promptable;
use Stringable;

class ReviewReplyAgent implements Agent
{
    use Promptable;

    public function __construct(
        private string $businessName,
        private string $businessType,
        private string $ownerName,
    ) {}

    public function instructions(): Stringable|string
    {
        return <<<INSTRUCTIONS
        You are a reputation management assistant for {$this->businessName}, a {$this->businessType} business.
        The owner is {$this->ownerName}.

        Your job is to generate 3 distinct, professional reply options for a customer's Google review.
        Each reply should:
        - Be warm, genuine, and personal
        - Acknowledge specific points from the review
        - Be appropriately concise (2-4 sentences max)
        - Reflect the business type's tone and style
        - Not be sycophantic or generic

        Return ONLY a JSON array of exactly 3 reply strings, like:
        ["Reply 1 here", "Reply 2 here", "Reply 3 here"]

        Do not include any other text, markdown, or explanation.
        INSTRUCTIONS;
    }
}
