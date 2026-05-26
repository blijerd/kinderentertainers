<x-layouts.app :title="$entertainer->name">
    <section class="brand-shell py-10">
        <div class="grid gap-8 lg:grid-cols-[1fr_380px]">
            <div>
                <div class="brand-panel p-6">
                    <p class="brand-kicker">{{ $entertainer->city }} · {{ $entertainer->region }}</p>
                    <h1 class="brand-heading mt-2 text-3xl">{{ $entertainer->name }}</h1>
                    <p class="mt-4 text-lg leading-8 text-slate-700">{{ $entertainer->short_introduction }}</p>
                    <div class="mt-5 flex flex-wrap gap-2">
                        @foreach ($entertainer->skills as $skill)
                            <span class="brand-pill text-sm">{{ $skill->name }}</span>
                        @endforeach
                    </div>
                </div>

                <div class="brand-card mt-6 p-6">
                    <h2 class="text-xl font-bold text-brand-ink">Over {{ $entertainer->name }}</h2>
                    <div class="mt-4 leading-7 text-slate-700">{{ $entertainer->bio }}</div>
                </div>
            </div>

            <aside class="space-y-5">
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
