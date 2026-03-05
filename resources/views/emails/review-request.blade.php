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

@if(!empty($confirmUrl))
<p style="text-align:center; margin-top: 16px;">
  <a href="{{ $confirmUrl }}" style="color: #6b7280; font-size: 13px; text-decoration: underline;">
    ✓ I already left a review
  </a>
</p>
@endif

Thanks,<br>
{{ $ownerName }}<br>
{{ $businessName }}

---

<small>Don't want to receive emails from {{ $businessName }}? [Unsubscribe]({{ $unsubscribeUrl }})</small>
</x-mail::message>
