<x-mail::message>
{{ $body }}

<x-mail::button :url="$reviewLink" color="primary">
⭐ Leave a Review
</x-mail::button>

Thanks,<br>
{{ $ownerName }}<br>
{{ $businessName }}
</x-mail::message>
