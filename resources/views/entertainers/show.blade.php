<x-layouts.app :title="$entertainer->name">
    <section class="brand-shell py-10">
        <div class="grid gap-8 lg:grid-cols-[1fr_380px]">
            <div>
                <div class="brand-panel overflow-hidden">
                    @if ($entertainer->profilePhotoUrl())
                        <img src="{{ $entertainer->profilePhotoUrl() }}" alt="{{ $entertainer->name }}" class="h-72 w-full object-cover md:h-96">
                    @endif
                    <div class="p-6">
                        <p class="brand-kicker">{{ $entertainer->city }} · {{ $entertainer->region }}</p>
                        <h1 class="brand-heading mt-2 text-3xl">{{ $entertainer->name }}</h1>
                        <p class="mt-4 text-lg leading-8 text-slate-700">{{ $entertainer->short_introduction }}</p>
                        <div class="mt-5 flex flex-wrap gap-2">
                            @foreach ($entertainer->skills as $skill)
                                <span class="brand-pill text-sm">{{ $skill->name }}</span>
                            @endforeach
                        </div>
                    </div>
                </div>

                <div class="brand-card mt-6 p-6">
                    <h2 class="text-xl font-bold text-brand-ink">Over {{ $entertainer->name }}</h2>
                    <div class="mt-4 whitespace-pre-line leading-7 text-slate-700">{{ $entertainer->bio }}</div>
                </div>

                @if ($entertainer->profileHighlightsList())
                    <div class="brand-card mt-6 p-6">
                        <h2 class="text-xl font-bold text-brand-ink">Waarom boeken</h2>
                        <div class="mt-4 grid gap-3 md:grid-cols-2">
                            @foreach ($entertainer->profileHighlightsList() as $highlight)
                                <div class="rounded-md bg-teal-50 px-4 py-3 text-sm font-semibold text-brand-ink">{{ $highlight }}</div>
                            @endforeach
                        </div>
                    </div>
                @endif

                @if ($entertainer->galleryPhotoUrls())
                    <div class="mt-6 grid gap-3 sm:grid-cols-2">
                        @foreach ($entertainer->galleryPhotoUrls() as $photoUrl)
                            <img src="{{ $photoUrl }}" alt="{{ $entertainer->name }} foto {{ $loop->iteration }}" class="h-64 w-full rounded-lg object-cover">
                        @endforeach
                    </div>
                @endif

                @if ($entertainer->practical_requirements)
                    <div class="brand-card mt-6 p-6">
                        <h2 class="text-xl font-bold text-brand-ink">Praktisch</h2>
                        <div class="mt-4 whitespace-pre-line leading-7 text-slate-700">{{ $entertainer->practical_requirements }}</div>
                    </div>
                @endif

                @if ($entertainer->packages || $entertainer->extras)
                    <div class="brand-card mt-6 p-6">
                        <h2 class="text-xl font-bold text-brand-ink">Pakketten en extras</h2>
                        @if ($entertainer->packages)
                            <div class="mt-4 grid gap-3 md:grid-cols-2">
                                @foreach ($entertainer->packages as $package)
                                    <div class="rounded-md border border-slate-200 p-4">
                                        <p class="font-bold text-brand-ink">{{ $package['name'] ?? 'Pakket' }}</p>
                                        @if (($package['price_cents'] ?? null) !== null)
                                            <p class="mt-1 text-sm font-semibold">€ {{ number_format($package['price_cents'] / 100, 2, ',', '.') }}</p>
                                        @endif
                                        @if ($package['description'] ?? null)
                                            <p class="mt-2 text-sm text-slate-700">{{ $package['description'] }}</p>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        @endif
                        @if ($entertainer->extras)
                            <div class="mt-4 flex flex-wrap gap-2">
                                @foreach ($entertainer->extras as $extra)
                                    <span class="brand-pill">
                                        {{ $extra['name'] ?? 'Extra' }}
                                        @if (($extra['price_cents'] ?? null) !== null)
                                            · € {{ number_format($extra['price_cents'] / 100, 2, ',', '.') }}
                                        @endif
                                    </span>
                                @endforeach
                            </div>
                        @endif
                    </div>
                @endif

                @if ($entertainer->approvedReviews->isNotEmpty())
                    <div class="brand-card mt-6 p-6">
                        <div class="flex flex-wrap items-end justify-between gap-3">
                            <div>
                                <p class="brand-kicker">Reviews</p>
                                <h2 class="text-xl font-bold text-brand-ink">Ervaringen met {{ $entertainer->name }}</h2>
                            </div>
                            <p class="text-sm font-semibold text-brand-ink">
                                {{ number_format($entertainer->approvedReviews->avg('rating'), 1, ',', '.') }}/5
                            </p>
                        </div>
                        <div class="mt-5 space-y-4">
                            @foreach ($entertainer->approvedReviews as $review)
                                <article class="rounded-md bg-teal-50 p-4">
                                    <div class="flex flex-wrap items-center justify-between gap-2">
                                        <h3 class="font-bold text-brand-ink">{{ $review->title ?: 'Review van '.$review->customer_name }}</h3>
                                        <span class="text-sm font-semibold text-brand-ink">{{ $review->rating }}/5</span>
                                    </div>
                                    <p class="mt-2 leading-7 text-slate-700">{{ $review->body }}</p>
                                    <p class="mt-3 text-sm text-slate-600">{{ $review->customer_name }}</p>
                                </article>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>

            <aside class="space-y-5">
                <div class="brand-card p-5">
                    <h2 class="text-lg font-bold text-brand-ink">Profielinformatie</h2>
                    <dl class="mt-4 space-y-3 text-sm">
                        @if ($entertainer->audience_age_range)
                            <div class="flex justify-between gap-4">
                                <dt class="text-slate-600">Leeftijd</dt>
                                <dd class="font-semibold text-brand-ink">{{ $entertainer->audience_age_range }}</dd>
                            </div>
                        @endif
                        @if ($entertainer->performance_duration_minutes)
                            <div class="flex justify-between gap-4">
                                <dt class="text-slate-600">Duur</dt>
                                <dd class="font-semibold text-brand-ink">{{ $entertainer->performance_duration_minutes }} min</dd>
                            </div>
                        @endif
                        @if ($entertainer->setup_time_minutes !== null)
                            <div class="flex justify-between gap-4">
                                <dt class="text-slate-600">Opbouwtijd</dt>
                                <dd class="font-semibold text-brand-ink">{{ $entertainer->setup_time_minutes }} min</dd>
                            </div>
                        @endif
                        <div class="flex justify-between gap-4">
                            <dt class="text-slate-600">Werkgebied</dt>
                            <dd class="font-semibold text-brand-ink">{{ $entertainer->working_radius_km }} km</dd>
                        </div>
                        @if ($entertainer->rating)
                            <div class="flex justify-between gap-4">
                                <dt class="text-slate-600">Beoordeling</dt>
                                <dd class="font-semibold text-brand-ink">{{ number_format((float) $entertainer->rating, 1, ',', '.') }} ({{ $entertainer->reviews_count }})</dd>
                            </div>
                        @endif
                    </dl>
                    @if ($entertainer->eventTypesList() || $entertainer->languagesList())
                        <div class="mt-5 space-y-3">
                            @if ($entertainer->eventTypesList())
                                <div class="flex flex-wrap gap-2">
                                    @foreach ($entertainer->eventTypesList() as $eventType)
                                        <span class="brand-pill">{{ $eventType }}</span>
                                    @endforeach
                                </div>
                            @endif
                            @if ($entertainer->languagesList())
                                <p class="text-sm text-slate-700">Talen: {{ implode(', ', $entertainer->languagesList()) }}</p>
                            @endif
                        </div>
                    @endif
                    @if ($entertainer->show_reel_url)
                        <a href="{{ $entertainer->show_reel_url }}" target="_blank" rel="noopener noreferrer" class="brand-button-secondary mt-5 w-full">Showreel bekijken</a>
                    @endif
                    @auth
                        @if (auth()->user()->hasRole('klant'))
                            <form method="POST" action="{{ route('customer-portal.favorites.store', $entertainer) }}" class="mt-3">
                                @csrf
                                <button class="brand-button-secondary w-full">Bewaar als favoriet</button>
                            </form>
                        @endif
                    @endauth
                </div>

                <livewire:availability-check :entertainer="$entertainer" />
                <div class="brand-card p-5">
                    <h2 class="text-lg font-bold text-brand-ink">Tarieven</h2>
                    <div class="mt-4 space-y-3">
                        @foreach ($entertainer->rates as $rate)
                            <div class="rounded-md bg-brand-peach p-3">
                                <p class="font-semibold">{{ $rate->customer_type->label() }}</p>
                                <p class="text-sm text-slate-700">Starttarief EUR {{ number_format($rate->starting_rate_cents / 100, 2, ',', '.') }} · uurtarief EUR {{ number_format($rate->hourly_rate_cents / 100, 2, ',', '.') }}</p>
                            </div>
                        @endforeach
                    </div>
                    <a href="{{ route('booking-requests.create', $entertainer) }}" class="brand-button mt-5 w-full">Aanvraag doen</a>
                </div>
            </aside>
        </div>
    </section>
</x-layouts.app>
