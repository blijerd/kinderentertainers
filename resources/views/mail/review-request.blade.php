<x-mail::message>
# Bedankt voor je boeking

We horen graag hoe je ervaring met {{ $review->entertainer->name }} was.

<x-mail::button :url="route('reviews.create', $review->token)">
Review achterlaten
</x-mail::button>

Alvast bedankt,<br>
{{ config('app.name') }}
</x-mail::message>
