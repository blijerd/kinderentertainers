<x-layouts.app :title="$entertainer->name">
    <section class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
        <div class="grid gap-8 lg:grid-cols-[1fr_380px]">
            <div>
                <div class="rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
                    <p class="text-sm font-semibold text-teal-700">{{ $entertainer->city }} · {{ $entertainer->region }}</p>
                    <h1 class="mt-2 text-3xl font-bold text-slate-950">{{ $entertainer->name }}</h1>
                    <p class="mt-4 text-lg leading-8 text-slate-700">{{ $entertainer->short_introduction }}</p>
                    <div class="mt-5 flex flex-wrap gap-2">
                        @foreach ($entertainer->skills as $skill)
                            <span class="rounded-full bg-teal-50 px-3 py-1 text-sm font-medium text-teal-800">{{ $skill->name }}</span>
                        @endforeach
                    </div>
                </div>

                <div class="mt-6 rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
                    <h2 class="text-xl font-semibold text-slate-950">Over {{ $entertainer->name }}</h2>
                    <div class="mt-4 leading-7 text-slate-700">{{ $entertainer->bio }}</div>
                </div>
            </div>

            <aside class="space-y-5">
                <livewire:availability-check :entertainer="$entertainer" />
                <div class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                    <h2 class="text-lg font-semibold text-slate-950">Tarieven</h2>
                    <div class="mt-4 space-y-3">
                        @foreach ($entertainer->rates as $rate)
                            <div class="rounded-md bg-slate-50 p-3">
                                <p class="font-semibold">{{ $rate->customer_type->label() }}</p>
                                <p class="text-sm text-slate-700">Starttarief EUR {{ number_format($rate->starting_rate_cents / 100, 2, ',', '.') }} · uurtarief EUR {{ number_format($rate->hourly_rate_cents / 100, 2, ',', '.') }}</p>
                            </div>
                        @endforeach
                    </div>
                    <a href="{{ route('booking-requests.create', $entertainer) }}" class="mt-5 block rounded-md bg-teal-700 px-4 py-3 text-center text-sm font-semibold text-white hover:bg-teal-800">Aanvraag doen</a>
                </div>
            </aside>
        </div>
    </section>
</x-layouts.app>
