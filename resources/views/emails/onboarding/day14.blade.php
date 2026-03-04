<x-mail::message>
# Did you know ReviewMate can reply to reviews for you?

Hi {{ $user->name }},

Two weeks in — hope the reviews are rolling in.

One feature a lot of people miss: **AI reply suggestions.**

When a customer leaves a Google review, ReviewMate pulls it in automatically. Click on any review → click "Suggest a reply" → get 3 AI-written options → pick one and post it to Google without leaving ReviewMate.

Responding to reviews (especially negative ones) improves your Google ranking and shows potential customers you care. Most business owners know this but never find the time to do it.

ReviewMate makes it a 30-second task.

<x-mail::button :url="config('app.url') . '/reviews'" color="success">
Reply to Your Reviews
</x-mail::button>

Try it on your next review.

Josh
ReviewMate

---

<small>You're receiving this because you created a ReviewMate account. [Manage notifications]({{ config('app.url') }}/settings/notifications)</small>
</x-mail::message>
