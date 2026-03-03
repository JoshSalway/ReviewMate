<x-mail::message>
{{ $body }}

<x-mail::button :url="$reviewLink" color="primary">
⭐ Leave a Review
</x-mail::button>

Thanks,<br>
{{ $ownerName }}<br>
{{ $businessName }}

---

<small>Don't want to receive emails from {{ $businessName }}? [Unsubscribe]({{ $unsubscribeUrl }})</small>
</x-mail::message>
