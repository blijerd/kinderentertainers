<x-layouts.app title="Kies je match">
    <section class="brand-shell py-12">
        <div class="mx-auto max-w-4xl">
            <h1 class="brand-heading text-3xl">Kies je entertainer</h1>
            <p class="mt-2 text-sm text-slate-700">
                Beschikbare entertainers voor {{ $bookingRequest->event_date->format('d-m-Y') }}
                @if ($bookingRequest->skill)
                    · {{ $bookingRequest->skill->name }}
                @endif
            </p>

            @if (session('status'))
                <p class="mt-6 rounded-md bg-green-50 px-4 py-3 text-sm font-medium text-green-800">{{ session('status') }}</p>
            @endif

            <div class="mt-8 space-y-4">
                @forelse ($bookingRequest->matches as $match)
                    <article class="brand-card p-5">
                        <div class="flex flex-wrap items-start justify-between gap-4">
                            <div>
                                <h2 class="text-xl font-bold text-brand-ink">{{ $match->entertainer->name }}</h2>
                                <p class="mt-1 text-sm text-slate-600">
                                    {{ $match->entertainer->city }} · {{ $match->status->label() }} · match {{ $match->match_score }}%
                                    @if ($match->distance_km !== null)
                                        · ca. {{ number_format((float) $match->distance_km, 1, ',', '.') }} km
                                    @endif
                                    @if ($match->travel_minutes)
                                        · {{ $match->travel_minutes }} min reistijd
                                    @endif
                                </p>
                            </div>
                            @if ($match->price_indication_cents !== null)
                                <p class="text-lg font-bold text-brand-ink">€ {{ number_format($match->price_indication_cents / 100, 2, ',', '.') }}</p>
                            @endif
                        </div>

                        @if ($match->response_message)
                            <p class="mt-4 text-sm leading-6 text-slate-700">{{ $match->response_message }}</p>
                        @endif

                        <dl class="mt-4 grid gap-3 text-sm sm:grid-cols-3">
                            <div>
                                <dt class="font-bold text-brand-ink">Reviews</dt>
                                <dd>{{ $match->entertainer->rating ?: '-' }} / 5 · {{ $match->entertainer->reviews_count }} reviews</dd>
                            </div>
                            <div>
                                <dt class="font-bold text-brand-ink">Werkgebied</dt>
                                <dd>{{ $match->entertainer->working_radius_km }} km vanaf {{ $match->entertainer->city }}</dd>
                            </div>
                            <div>
                                <dt class="font-bold text-brand-ink">Profiel</dt>
                                <dd>{{ $match->entertainer->profile_quality_score }}% compleet</dd>
                            </div>
                        </dl>

                        @if ($match->status === \App\Enums\BookingRequestMatchStatus::Accepted)
                            <p class="mt-4 rounded-md bg-teal-50 px-3 py-2 text-sm font-semibold text-teal-800">Deze match is gekozen.</p>
                        @elseif ($bookingRequest->entertainer_id === null)
                            <form method="POST" action="{{ route('booking-requests.matches.select', [$bookingRequest, $match]) }}" class="mt-4">
                                @csrf
                                <input type="hidden" name="token" value="{{ $token }}">
                                <button class="brand-button px-4 py-2">Kies {{ $match->entertainer->name }}</button>
                            </form>
                        @endif
                    </article>
                @empty
                    <div class="brand-card p-5">
                        <p class="text-sm text-slate-700">Er zijn nog geen reacties binnen. Zodra entertainers reageren, verschijnen ze hier.</p>
                    </div>
                @endforelse
            </div>
        </div>
    </section>
</x-layouts.app>
