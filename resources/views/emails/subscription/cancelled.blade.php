<x-mail::message>
# Your subscription has been cancelled

Hi {{ $user->name }},

We're sorry to see you go. Your ReviewMate subscription has been cancelled and your account has been downgraded to the free plan.

You can still access your account with up to 50 customers and 10 requests per month.

If you change your mind, you can resubscribe at any time from your billing settings.

<x-mail::button :url="config('app.url') . '/settings/billing'">
Resubscribe
</x-mail::button>

If you cancelled by mistake or have any questions, reply to this email and we'll sort it out right away.

Thanks,<br>
The ReviewMate Team
</x-mail::message>
