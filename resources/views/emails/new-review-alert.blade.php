<x-mail::message>
# New {{ $review->stars() }}-star review on {{ $business->name }}

Hi {{ $user->name }},

**{{ $review->reviewer_name ?? 'Someone' }}** just left a review on Google.

<x-mail::panel>
@for($i = 1; $i <= 5; $i++){{ $i <= $review->rating ? '★' : '☆' }}@endfor &nbsp; **{{ $review->rating }} / 5**

@if($review->body)
*"{{ $review->body }}"*
@else
*(No written review)*
@endif
</x-mail::panel>

@if($review->stars() <= 3)
This review may need attention. Replying promptly can help protect your reputation.
@endif

<x-mail::button :url="config('app.url') . '/reviews'" color="success">
Reply to this Review
</x-mail::button>

Thanks,<br>
The ReviewMate Team

---
<small>[Manage notification preferences]({{ config('app.url') }}/settings/notifications)</small>
</x-mail::message>
