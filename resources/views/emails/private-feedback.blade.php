<x-mail::message>
# Private feedback from {{ $customer->name }}

Hi {{ $user->name }},

**{{ $customer->name }}** chose to share private feedback instead of a public review on **{{ $business->name }}**.

<x-mail::panel>
@for($i = 1; $i <= 5; $i++){{ $i <= $reviewRequest->private_rating ? '★' : '☆' }}@endfor &nbsp; **{{ $reviewRequest->private_rating }} / 5**

@if($reviewRequest->private_feedback)
*"{{ $reviewRequest->private_feedback }}"*
@else
*(No written feedback provided)*
@endif
</x-mail::panel>

This is a great opportunity to reach out to {{ $customer->name }} directly and resolve any concerns before they become a public review.

@if($customer->email)
**Customer email:** {{ $customer->email }}
@endif
@if($customer->phone)
**Customer phone:** {{ $customer->phone }}
@endif

<x-mail::button :url="config('app.url') . '/customers'" color="success">
View Customer
</x-mail::button>

Thanks,<br>
The ReviewMate Team

---
<small>[Manage notification preferences]({{ config('app.url') }}/settings/notifications)</small>
</x-mail::message>
