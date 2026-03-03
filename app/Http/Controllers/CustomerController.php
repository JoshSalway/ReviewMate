<?php

namespace App\Http\Controllers;

use App\Mail\ReviewRequestMail;
use App\Models\Customer;
use App\Models\ReviewRequest;
use App\Services\TwilioSmsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Inertia\Inertia;
use Inertia\Response;

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
            'customer_ids'   => ['required', 'array', 'min:1'],
            'customer_ids.*' => ['required', 'integer'],
            'channel'        => ['required', 'in:email,sms,both'],
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
        foreach ($customers as $customer) {
            ReviewRequest::create([
                'business_id' => $business->id,
                'customer_id' => $customer->id,
                'status'      => 'sent',
                'channel'     => $validated['channel'],
                'sent_at'     => now(),
            ]);

            if (in_array($validated['channel'], ['email', 'both']) && $customer->email) {
                Mail::to($customer->email, $customer->name)
                    ->queue(new ReviewRequestMail($business, $customer));
            }

            if (in_array($validated['channel'], ['sms', 'both']) && $customer->phone && TwilioSmsService::isConfigured()) {
                rescue(fn () => app(TwilioSmsService::class)->sendReviewRequest($business, $customer));
            }

            $sent++;
        }

        $skipped = count($validated['customer_ids']) - $sent;
        $message = "Sent {$sent} review request(s).";
        if ($skipped > 0) {
            $message .= " {$skipped} skipped (monthly limit reached).";
        }

        return back()->with('success', $message);
    }

    public function importCsv(Request $request): RedirectResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:csv,txt', 'max:2048'],
        ]);

        $business = $request->user()->currentBusiness();
        $file = $request->file('file');
        $handle = fopen($file->getRealPath(), 'r');

        $header = null;
        $imported = 0;
        $skipped = 0;

        while (($row = fgetcsv($handle)) !== false) {
            if ($header === null) {
                $header = array_map('strtolower', array_map('trim', $row));
                continue;
            }

            $data = array_combine($header, $row);

            $name = trim($data['name'] ?? $data['full name'] ?? $data['customer name'] ?? '');
            $email = trim($data['email'] ?? $data['email address'] ?? '');
            $phone = trim($data['phone'] ?? $data['mobile'] ?? $data['phone number'] ?? '');

            if (empty($name)) {
                $skipped++;
                continue;
            }

            $business->customers()->firstOrCreate(
                array_filter(['email' => $email ?: null, 'phone' => $phone ?: null]),
                ['name' => $name, 'phone' => $phone ?: null, 'email' => $email ?: null]
            );

            $imported++;
        }

        fclose($handle);

        return back()->with('success', "Imported {$imported} customer(s). Skipped {$skipped} row(s).");
    }

    private function authorizeCustomer(Request $request, Customer $customer): void
    {
        abort_unless($customer->business_id === $request->user()->currentBusiness()?->id, 403);
    }
}
