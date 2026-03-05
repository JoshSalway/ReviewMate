<x-mail::message>
Hi {{ $customerName }},

Thank you so much for leaving a review for **{{ $businessName }}**! We really appreciate it.

---

**Know another local business owner who could use more reviews?**

Share ReviewMate with them and they'll get their **first month completely free**. It's the tool {{ $businessName }} uses to collect reviews from customers automatically.

<x-mail::button :url="$referralUrl">
Share ReviewMate — Get a Month Free
</x-mail::button>

When your friend signs up using your link, they get 1 month free — and we'll give {{ $businessName }} 1 month free too as a thank you.

Thanks again for the review,
**The ReviewMate Team**
</x-mail::message>
