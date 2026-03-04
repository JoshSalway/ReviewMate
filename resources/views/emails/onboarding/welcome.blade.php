<x-mail::message>
# Welcome to ReviewMate

Hi {{ $user->name }},

Welcome to ReviewMate. Getting your first review is easier than you think — here's exactly what to do:

**Step 1: Connect your Google Business Profile**

Go to Settings → Business → Connect Google. This lets ReviewMate sync your reviews automatically and lets you reply without leaving the app.

**Step 2: Import your customers**

Go to Customers → Import CSV. Most businesses import 20–100 customers in their first session. No CSV? Add them manually — takes 30 seconds per customer.

**Step 3: Send your first review request**

Go to Customers → select a few customers → Bulk Send. Or use Quick Send for a one-off.

Most businesses get their first new review within 48 hours of sending their first request.

<x-mail::button :url="config('app.url') . '/dashboard'" color="success">
Go to Dashboard
</x-mail::button>

Any questions, reply to this email.

Josh
ReviewMate

---

<small>You're receiving this because you created a ReviewMate account. [Manage notifications]({{ config('app.url') }}/settings/notifications)</small>
</x-mail::message>
