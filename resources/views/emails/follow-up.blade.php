<x-mail::message>
{{ $body }}

<x-mail::button :url="$reviewLink" color="primary">
⭐ Leave a Google Review
</x-mail::button>

@if(!empty($facebookReviewUrl))
Or leave us a Facebook review:

<x-mail::button :url="$facebookReviewUrl" color="success">
👍 Leave a Facebook Review
</x-mail::button>
@endif

Thanks,<br>
{{ $ownerName }}<br>
{{ $businessName }}

---

<small>Don't want to receive emails from {{ $businessName }}? [Unsubscribe]({{ $unsubscribeUrl }})</small>
</x-mail::message>
