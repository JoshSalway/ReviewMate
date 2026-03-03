<?php

namespace App\Console\Commands;

use App\Mail\FollowUpMail;
use App\Models\ReviewRequest;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class SendFollowUpRequests extends Command
{
    protected $signature = 'reviewmate:send-followups';

    protected $description = 'Send follow-up emails to customers who have not left a review after 3 days';

    public function handle(): int
    {
        $cutoff = now()->subDays(3);

        $requests = ReviewRequest::query()
            ->with(['business.user', 'customer'])
            ->whereIn('status', ['sent', 'opened'])
            ->where('sent_at', '<=', $cutoff)
            ->whereNull('reviewed_at')
            ->whereDoesntHave('customer.reviewRequests', fn ($q) => $q->where('status', 'reviewed'))
            ->get();

        $sent = 0;

        foreach ($requests as $request) {
            $customer = $request->customer;
            $business = $request->business;

            if (! $customer->email) {
                continue;
            }

            Mail::to($customer->email, $customer->name)
                ->queue(new FollowUpMail($business, $customer));

            $request->update(['status' => 'no_response']);

            $sent++;
        }

        $this->info("Sent {$sent} follow-up email(s).");

        return Command::SUCCESS;
    }
}
