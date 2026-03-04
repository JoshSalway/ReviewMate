<x-mail::message>
# Quick tip about your first review

Hi {{ $user->name }},

Just checking in — have you sent your first review request yet?

If you haven't had a chance, here's the fastest way to start: use Quick Send.

Go to ReviewMate → Quick Send → enter a customer's name and email → send. Done in under 60 seconds.

No need to import a full customer list first. Just pick one happy customer you saw this week and send it now.

The hardest part is starting. Everything after that is automatic.

<x-mail::button :url="config('app.url') . '/quick-send'" color="success">
Send Your First Request
</x-mail::button>

Josh
ReviewMate

---

<small>You're receiving this because you created a ReviewMate account. [Manage notifications]({{ config('app.url') }}/settings/notifications)</small>
</x-mail::message>
