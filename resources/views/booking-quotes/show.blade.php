<x-layouts.app title="Offerte {{ $bookingRequest->entertainer->name }}">
    <section class="brand-shell py-10">
        <div class="mx-auto max-w-3xl">
            @if (session('status'))
                <p class="mb-6 rounded-md bg-green-50 px-4 py-3 text-sm font-medium text-green-800">{{ session('status') }}</p>
            @endif

            <div class="brand-card p-6">
                <p class="brand-kicker">Offerte</p>
                <h1 class="brand-heading mt-2 text-3xl">{{ $bookingRequest->entertainer->name }}</h1>
                <p class="mt-3 text-sm text-slate-600">
                    Voor {{ $bookingRequest->name }} op {{ $bookingRequest->event_date->format('d-m-Y') }}
                    van {{ $bookingRequest->start_time->format('H:i') }} tot {{ $bookingRequest->end_time->format('H:i') }} in {{ $bookingRequest->city }}.
                </p>
                <div class="mt-5 grid gap-2 text-xs font-bold uppercase tracking-wide text-slate-600 sm:grid-cols-3">
                    <span class="rounded-md bg-teal-50 px-3 py-2 text-teal-900">1. Offerte</span>
                    <span class="rounded-md bg-teal-50 px-3 py-2 text-teal-900">2. Akkoord</span>
                    <span class="rounded-md bg-teal-50 px-3 py-2 text-teal-900">3. Bevestigd</span>
                </div>

                <dl class="mt-6 divide-y divide-slate-200 text-sm">
                    <div class="flex justify-between gap-4 py-3">
                        <dt>Optreden</dt>
                        <dd class="font-semibold">€ {{ number_format($bookingRequest->quote_performance_cents / 100, 2, ',', '.') }}</dd>
                    </div>
                    <div class="flex justify-between gap-4 py-3">
                        <dt>Reistoeslag {{ number_format((float) $bookingRequest->quote_travel_distance_km, 1, ',', '.') }} km</dt>
                        <dd class="font-semibold">€ {{ number_format($bookingRequest->quote_travel_cents / 100, 2, ',', '.') }}</dd>
                    </div>
                    <div class="flex justify-between gap-4 py-3 text-base">
                        <dt class="font-bold">Totaal</dt>
                        <dd class="font-bold">€ {{ number_format($bookingRequest->quote_total_cents / 100, 2, ',', '.') }}</dd>
                    </div>
                    @if ($bookingRequest->deposit_cents)
                        <div class="flex justify-between gap-4 py-3">
                            <dt>Aanbetaling</dt>
                            <dd class="font-semibold">€ {{ number_format($bookingRequest->deposit_cents / 100, 2, ',', '.') }}</dd>
                        </div>
                    @endif
                </dl>

                <p class="mt-4 text-sm text-slate-600">
                    Geldig tot en met {{ $bookingRequest->quote_valid_until?->format('d-m-Y') }}. Betaalstatus: {{ $bookingRequest->payment_status }}.
                </p>
                <p class="mt-2 text-sm text-slate-600">
                    {{ $bookingRequest->entertainer->name }} factureert zelf. Kinderentertainers.nl int geen betaling voor deze boeking.
                </p>

                @if ($bookingRequest->quote_accepted_at)
                    <p class="mt-6 rounded-md bg-green-50 px-4 py-3 text-sm font-semibold text-green-800">
                        Akkoord gegeven op {{ $bookingRequest->quote_accepted_at->format('d-m-Y H:i') }}. De boeking is bevestigd.
                            @if ($bookingRequest->deposit_cents && $bookingRequest->payment_status !== 'paid')
                                De aanbetaling staat nog open; de entertainer kan hiervoor zelf een betaalverzoek sturen
                                @if ($bookingRequest->payment_provider)
                                    via {{ \App\Enums\PaymentProvider::tryFrom($bookingRequest->payment_provider)?->label() ?? $bookingRequest->payment_provider }}
                                @endif
                            @if ($bookingRequest->cash_payment_allowed)
                                of contant betaling op locatie accepteren
                            @endif
                                .
                            @endif
                        </p>
                        @if ($bookingRequest->payment_checkout_url && $bookingRequest->payment_status !== 'paid')
                            <a href="{{ $bookingRequest->payment_checkout_url }}" class="brand-button mt-4 w-full justify-center">Aanbetaling betalen</a>
                        @endif
                @elseif ($bookingRequest->quote_valid_until?->isPast())
                    <p class="mt-6 rounded-md bg-amber-50 px-4 py-3 text-sm font-semibold text-amber-900">Deze offerte is verlopen.</p>
                @else
                    <form method="POST" action="{{ route('booking-quotes.accept', $bookingRequest->quote_acceptance_token) }}" class="mt-6">
                        @csrf
                        <label class="mb-3 block space-y-1">
                            <span class="text-sm font-medium text-slate-700">Naam voor akkoord</span>
                            <input name="acceptance_name" value="{{ old('acceptance_name', $bookingRequest->name) }}" required class="w-full rounded-md border-slate-300 text-sm">
                        </label>
                        <button class="brand-button w-full justify-center">Akkoord en boeking bevestigen</button>
                    </form>
                @endif
            </div>

            @if ($bookingRequest->quote_terms_body)
                <article class="brand-card mt-6 p-6">
                    <h2 class="text-lg font-bold text-brand-ink">Voorwaarden {{ $bookingRequest->quote_terms_version }}</h2>
                    <div class="prose prose-sm mt-4 max-w-none text-slate-700">
                        {!! nl2br(e($bookingRequest->quote_terms_body)) !!}
                    </div>
                </article>
            @endif
        </div>
    </section>
</x-layouts.app>
