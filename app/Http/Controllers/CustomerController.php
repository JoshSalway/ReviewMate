<?php

namespace App\Http\Controllers;

use App\Mail\ReviewRequestMail;
use App\Models\Customer;
use App\Models\ReviewRequest;
use App\Services\SmsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CustomerController extends Controller
{
    public function index(Request $request): Response
    {
        $business = $request->user()->currentBusiness();

        $customers = $business->customers()
            ->with('latestReviewRequest')
            ->latest()
            ->paginate(20)
            ->through(fn ($customer) => [
                'id' => $customer->id,
                'name' => $customer->name,
                'email' => $customer->email,
                'phone' => $customer->phone,
                'initials' => $customer->initials(),
                'status' => $customer->requestStatus(),
                'created_at' => $customer->created_at->format('d M Y'),
            ]);

        return Inertia::render('customers/index', [
            'customers' => $customers,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();
        $business = $user->currentBusiness();

        // Enforce 50 customer limit on free plan
        if ($user->onFreePlan() && $business->customers()->count() >= 50) {
            return back()->with('error', 'Free plan is limited to 50 customers. Upgrade to add more.');
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $business->customers()->create($validated);

        return back()->with('success', 'Customer added successfully.');
    }

    public function update(Request $request, Customer $customer): RedirectResponse
    {
        $this->authorizeCustomer($request, $customer);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $customer->update($validated);

        return back()->with('success', 'Customer updated successfully.');
    }

    public function destroy(Request $request, Customer $customer): RedirectResponse
    {
        $this->authorizeCustomer($request, $customer);

        $customer->delete();

        return back()->with('success', 'Customer removed.');
    }

    public function bulkSend(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'customer_ids' => ['required', 'array', 'min:1'],
            'customer_ids.*' => ['required', 'integer'],
            'channel' => ['required', 'in:email,sms,both'],
        ]);

        $user = $request->user();
        $business = $user->currentBusiness();

        // Enforce free plan monthly limit
        if ($user->onFreePlan()) {
            $monthlyCount = $business->reviewRequests()
                ->whereMonth('created_at', now()->month)
                ->whereYear('created_at', now()->year)
                ->count();

            $remaining = max(0, 10 - $monthlyCount);
            $toSend = min(count($validated['customer_ids']), $remaining);

            if ($remaining === 0) {
                return back()->with('error', 'Free plan allows 10 review requests per month. Upgrade for unlimited.');
            }

            $validated['customer_ids'] = array_slice($validated['customer_ids'], 0, $toSend);
        }

        $customers = $business->customers()->whereIn('id', $validated['customer_ids'])->get();

        $sent = 0;
        $skipped = 0;
        foreach ($customers as $customer) {
            if ($customer->isUnsubscribed() || ReviewRequest::hasRecentRequest($business->id, $customer->id)) {
                $skipped++;

                continue;
            }

            $reviewRequest = ReviewRequest::create([
                'business_id' => $business->id,
                'customer_id' => $customer->id,
                'status' => 'sent',
                'channel' => $validated['channel'],
                'sent_at' => now(),
            ]);

            if (in_array($validated['channel'], ['email', 'both']) && $customer->email) {
                Mail::to($customer->email, $customer->name)
                    ->queue(new ReviewRequestMail($business, $customer, $reviewRequest));
            }

            if (in_array($validated['channel'], ['sms', 'both']) && $customer->phone && SmsService::isConfigured()) {
                rescue(fn () => SmsService::make()->sendReviewRequest($business, $customer));
            }

            $sent++;
        }

        $message = "Sent {$sent} review request(s).";
        if ($skipped > 0) {
            $message .= " {$skipped} skipped (already sent recently or monthly limit reached).";
        }

        return back()->with('success', $message);
    }

    public function export(Request $request): StreamedResponse
    {
        $business = $request->user()->currentBusiness();

        $customers = $business->customers()
            ->with(['latestReviewRequest', 'reviewRequests' => fn ($q) => $q->where('status', 'reviewed')])
            ->latest()
            ->get();

        $filename = 'customers-'.now()->format('Y-m-d').'.csv';

        return response()->streamDownload(function () use ($customers) {
            $handle = fopen('php://output', 'w');

            fputcsv($handle, ['Name', 'Email', 'Phone', 'Added', 'Last Request Sent', 'Reviews Left']);

            foreach ($customers as $customer) {
                fputcsv($handle, [
                    $customer->name,
                    $customer->email ?? '',
                    $customer->phone ?? '',
                    $customer->created_at->format('Y-m-d'),
                    $customer->latestReviewRequest?->sent_at?->format('Y-m-d') ?? '',
                    $customer->reviewRequests->count(),
                ]);
            }

            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    public function importCsv(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'customers' => ['required', 'array', 'min:1', 'max:500'],
            'customers.*.name' => ['nullable', 'string', 'max:255'],
            'customers.*.email' => ['nullable', 'email', 'max:255'],
            'customers.*.phone' => ['nullable', 'string', 'max:50'],
        ]);

        $business = $request->user()->currentBusiness();

        if (! $business) {
            return back()->with('error', 'No business found.');
        }

        $imported = 0;
        foreach ($validated['customers'] as $row) {
            if (empty($row['name']) && empty($row['email'])) {
                continue;
            }

            // Skip duplicates (same email for same business)
            if (! empty($row['email']) && $business->customers()->where('email', $row['email'])->exists()) {
                continue;
            }

            $business->customers()->create([
                'name' => $row['name'] ?? 'Unknown',
                'email' => $row['email'] ?? null,
                'phone' => $row['phone'] ?? null,
            ]);

            $imported++;
        }

        return back()->with('success', "Imported {$imported} customers successfully.");
    }

    public function unsubscribe(Request $request, string $token): Response|RedirectResponse
    {
        $customer = Customer::where('unsubscribe_token', $token)->firstOrFail();

        $alreadyUnsubscribed = $customer->isUnsubscribed();

        if (! $alreadyUnsubscribed) {
            $customer->update(['unsubscribed_at' => now()]);
        }

        // Find the most recent review request for this customer to offer a self-confirm CTA
        $latestRequest = $customer->reviewRequests()
            ->whereNotIn('status', ['reviewed', 'self_confirmed', 'unverified_claim'])
            ->latest()
            ->first();

        $confirmUrl = $latestRequest?->tracking_token
            ? url('/reviewed/'.$latestRequest->tracking_token)
            : null;

        $customer->loadMissing('business');

        return Inertia::render('unsubscribed', [
            'businessName' => $customer->business?->name,
            'confirmUrl' => $confirmUrl,
        ]);
    }

    private function authorizeCustomer(Request $request, Customer $customer): void
    {
        abort_unless($customer->business_id === $request->user()->currentBusiness()?->id, 403);
    }
}
