<x-mail::message>
# Your Weekly Review Digest

Hi {{ $user->name }},

Here's a summary of what happened on **{{ $business->name }}** this week.

<x-mail::panel>
**{{ $stats['new_reviews'] }}** new review{{ $stats['new_reviews'] !== 1 ? 's' : '' }} this week

**{{ $stats['total_reviews'] }}** total reviews &nbsp;·&nbsp; **{{ $stats['average_rating'] }}★** average rating

**{{ $stats['requests_sent'] }}** review requests sent this week
</x-mail::panel>

@if($stats['pending_replies'] > 0)
<x-mail::panel>
⚠️ You have **{{ $stats['pending_replies'] }}** Google review{{ $stats['pending_replies'] !== 1 ? 's' : '' }} waiting for a reply.

Replying to reviews improves your Google ranking and shows customers you care.
</x-mail::panel>
@endif

<x-mail::button :url="config('app.url') . '/reviews'" color="success">
View Your Reviews
</x-mail::button>

Have a great week,<br>
The ReviewMate Team

---
<small>You're receiving this because you have a ReviewMate account. [Unsubscribe]({{ config('app.url') }}/settings/notifications)</small>
</x-mail::message>
