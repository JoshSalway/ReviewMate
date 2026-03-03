<x-mail::message>
# Welcome to ReviewMate! 🎉

Hi {{ $user->name }},

Your subscription is now active. You're all set to start collecting more Google reviews automatically.

Here's what you can do next:

- **Connect Google Business Profile** — sync your reviews and reply directly from ReviewMate
- **Add your customers** — import a CSV or add them one by one
- **Send review requests** — email or SMS your customers and watch the reviews roll in

<x-mail::button :url="config('app.url') . '/dashboard'" color="success">
Go to Dashboard
</x-mail::button>

If you have any questions, just reply to this email.

Thanks,<br>
The ReviewMate Team
</x-mail::message>
