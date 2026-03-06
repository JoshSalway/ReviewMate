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
        private string $tone = 'friendly',
        private string $length = 'medium',
        private ?string $signature = null,
        private ?string $customInstructions = null,
        private bool $multipleOptions = true,
    ) {}

    public function instructions(): Stringable|string
    {
        $toneGuide = match ($this->tone) {
            'professional' => 'formal, polished, and business-like — avoid casual language or contractions',
            'casual' => 'relaxed and conversational, like a friend — use contractions freely',
            'enthusiastic' => 'energetic, warm, and upbeat — show genuine excitement and gratitude',
            default => 'warm and approachable — friendly without being over the top',
        };

        $lengthGuide = match ($this->length) {
            'short' => '1-2 sentences maximum. Be concise and impactful.',
            'long' => '4-5 sentences. Be thorough, acknowledge specifics, and expand on your appreciation.',
            default => '2-3 sentences. Hit the key points without rambling.',
        };

        $signatureInstruction = $this->signature
            ? "Always end every reply with this exact signature on a new line: {$this->signature}"
            : "Sign off naturally as {$this->ownerName} from {$this->businessName}.";

        $customBlock = $this->customInstructions
            ? "\nAdditional business-specific instructions (follow these carefully):\n{$this->customInstructions}"
            : '';

        $businessTypeGuidance = $this->businessTypeGuidance();

        if ($this->multipleOptions) {
            $outputInstruction = 'Return ONLY a JSON array of exactly 3 reply strings, like: ["Reply 1", "Reply 2", "Reply 3"]. Do not include any other text, markdown, or explanation.';
        } else {
            $outputInstruction = 'Return ONLY the reply text itself — no JSON, no explanation, no markdown. Just the reply.';
        }

        return <<<INSTRUCTIONS
        You are a reputation management assistant for {$this->businessName}, a {$this->businessType} business.
        The owner is {$this->ownerName}.

        TONE: Write in a {$toneGuide} tone.
        LENGTH: {$lengthGuide}
        SIGNATURE: {$signatureInstruction}

        {$businessTypeGuidance}

        General rules:
        - Acknowledge specific details mentioned in the review (don't be generic)
        - Never be sycophantic (no "What a wonderful review!")
        - Never copy-paste the same reply for different reviews
        - Never mention competitor names
        - Keep it authentic — it should sound like a real human wrote it
        {$customBlock}

        {$outputInstruction}
        INSTRUCTIONS;
    }

    private function businessTypeGuidance(): string
    {
        return match ($this->businessType) {
            'tradie' => 'Industry context: This is a trades business (plumber, electrician, builder, etc.). Where relevant, mention the specific type of work done, reference the local area, and offer to help with future jobs.',
            'cafe', 'restaurant' => 'Industry context: This is a food & beverage business. Reference the dining experience, specific dishes or drinks if mentioned, and invite them back for another visit or a special occasion.',
            'salon', 'barber' => 'Industry context: This is a hair/beauty business. Reference the specific service if mentioned, mention their stylist/barber warmly, and encourage them to book their next appointment.',
            'healthcare' => 'Industry context: This is a healthcare practice (doctor, dentist, physio, etc.). Keep replies professional and HIPAA/privacy-aware — do not acknowledge or reference specific health conditions or treatments. Focus on the patient experience and comfort.',
            'real_estate' => 'Industry context: This is a real estate agency. Reference the property journey (buying, selling, renting) if mentioned, and highlight the personalised service provided.',
            'retail' => 'Industry context: This is a retail business. Reference the product or shopping experience if mentioned, and invite them back.',
            'pet_services' => 'Industry context: This is a pet services business (vet, groomer, trainer). Reference their pet warmly by name if mentioned, and show genuine care for animals.',
            'fitness' => 'Industry context: This is a fitness business (gym, personal trainer, yoga studio). Be motivating and energetic, reference their fitness journey if mentioned.',
            default => 'Industry context: This is a local business. Be genuine and personalised.',
        };
    }
}
