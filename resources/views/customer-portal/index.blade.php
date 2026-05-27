<x-layouts.app title="Klantportaal">
    <section class="brand-shell py-10">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <h1 class="brand-heading text-3xl">Klantportaal</h1>
                <p class="mt-2 text-sm text-slate-700 dark:text-slate-300">Bekijk je aanvragen, offertes, documenten en berichten.</p>
            </div>
            @if (session('status'))
                <p class="rounded-md bg-green-50 px-4 py-3 text-sm font-medium text-green-800">{{ session('status') }}</p>
            @endif
        </div>

        @if ($favoriteEntertainers->isNotEmpty())
            <div class="mt-8 brand-card p-5">
                <h2 class="text-lg font-bold text-brand-ink dark:text-white">Favorieten</h2>
                <div class="mt-4 grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($favoriteEntertainers as $entertainer)
                        <div class="rounded-md border border-slate-200 p-3">
                            <p class="font-semibold">{{ $entertainer->name }}</p>
                            <p class="text-sm text-slate-600">{{ $entertainer->city }} · {{ $entertainer->rating ?: '-' }} / 5</p>
                            <div class="mt-3 flex gap-2">
                                <a href="{{ route('entertainers.show', $entertainer) }}" class="rounded-md bg-brand-ink px-3 py-2 text-sm font-bold text-white">Bekijk</a>
                                <form method="POST" action="{{ route('customer-portal.favorites.destroy', $entertainer) }}">
                                    @csrf
                                    @method('DELETE')
                                    <button class="rounded-md border border-slate-200 px-3 py-2 text-sm font-semibold">Verwijderen</button>
                                </form>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        <div class="mt-8 grid gap-4">
            @forelse ($bookingRequests as $bookingRequest)
                <article class="brand-card p-5">
                    <div class="flex flex-wrap items-start justify-between gap-4">
                        <div>
                            <p class="text-sm font-bold text-brand-coral">{{ $bookingRequest->status->label() }}</p>
                            <h2 class="mt-1 text-xl font-bold text-brand-ink dark:text-white">
                                {{ $bookingRequest->event_date->format('d-m-Y') }} in {{ $bookingRequest->city }}
                            </h2>
                            <p class="mt-1 text-sm text-slate-600 dark:text-slate-300">
                                {{ $bookingRequest->entertainer?->name ?? 'Nog geen entertainer gekozen' }}
                                @if ($bookingRequest->skill)
                                    · {{ $bookingRequest->skill->name }}
                                @endif
                            </p>
                        </div>
                        <a href="{{ route('customer-portal.show', $bookingRequest) }}" class="brand-button px-4 py-2">Bekijken</a>
                    </div>
                </article>
            @empty
                <div class="brand-card p-6">
                    <p class="text-sm text-slate-600 dark:text-slate-300">Er zijn nog geen aanvragen gekoppeld aan je account.</p>
                </div>
            @endforelse
        </div>

        <div class="mt-6">{{ $bookingRequests->links() }}</div>
    </section>
</x-layouts.app>
