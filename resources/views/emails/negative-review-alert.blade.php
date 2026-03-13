<x-mail::message>
# {{ $review->rating }}-star review needs your attention on {{ $business->name }}

Hi {{ $business->user->name }},

**{{ $review->reviewer_name ?? 'Someone' }}** left a {{ $review->rating }}-star review on Google. Low-star reviews benefit from a prompt, professional reply.

<x-mail::panel>
@for($i = 1; $i <= 5; $i++){{ $i <= $review->rating ? '★' : '☆' }}@endfor &nbsp; **{{ $review->rating }} / 5**

@if($review->body)
*"{{ $review->body }}"*
@else
*(No written review left)*
@endif
</x-mail::panel>

Replying promptly shows future customers you care about feedback — even negative feedback.

<x-mail::button :url="config('app.url') . '/reviews'" color="error">
Reply to this Review
</x-mail::button>

@if($business->google_place_id)
Or respond directly on Google:
[Open on Google Maps](https://search.google.com/local/writereview?placeid={{ $business->google_place_id }})
@endif

Thanks,<br>
The ReviewMate Team

---
<small>[Manage notification preferences]({{ config('app.url') }}/settings/notifications)</small>
</x-mail::message>
