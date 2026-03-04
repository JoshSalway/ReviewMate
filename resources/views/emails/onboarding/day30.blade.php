<x-mail::message>
# One month with ReviewMate

Hi {{ $user->name }},

It's been a month — hope ReviewMate has been earning its keep.

If you're on the free plan and running into limits (50 customers, 10 requests/month), upgrading to Starter ($49/month) removes all limits and unlocks unlimited customers and requests.

At $49/month, you need one extra customer a month from improved Google visibility to break even. Most businesses see far more than that.

<x-mail::button :url="config('app.url') . '/settings/billing'" color="success">
Upgrade to Starter
</x-mail::button>

If the free plan is working fine for you, no pressure at all.

Thanks for being an early user.

Josh
ReviewMate

---

<small>You're receiving this because you created a ReviewMate account. [Manage notifications]({{ config('app.url') }}/settings/notifications)</small>
</x-mail::message>
