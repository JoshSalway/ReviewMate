<x-mail::message>
# Week 1 check-in

Hi {{ $user->name }},

One week in — how's it going?

A few things worth knowing:

**1. ReviewMate follows up automatically.** If a customer doesn't respond in 5 days, we send a gentle reminder for you. You don't need to do anything.

**2. Check your Reviews tab.** If you connected Google, you should be seeing your existing reviews. You can reply to them with AI suggestions in seconds.

**3. Your QR code is ready.** Go to ReviewMate → QR Code — print it and put it on your counter or include it in invoices.

<x-mail::button :url="config('app.url') . '/reviews'" color="success">
View Your Reviews
</x-mail::button>

Any questions, reply here.

Josh
ReviewMate

---

<small>You're receiving this because you created a ReviewMate account. [Manage notifications]({{ config('app.url') }}/settings/notifications)</small>
</x-mail::message>
