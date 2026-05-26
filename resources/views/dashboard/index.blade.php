<x-layouts.app title="Entertainer dashboard">
    <section class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
        <h1 class="text-3xl font-bold text-slate-950">Dashboard {{ $entertainer->name }}</h1>
        <div class="mt-8 grid gap-6 lg:grid-cols-[320px_1fr]">
            <aside class="space-y-4 rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                <h2 class="text-lg font-semibold">Profiel</h2>
                <p class="text-sm text-slate-600">{{ $entertainer->short_introduction }}</p>
                <p class="text-sm"><strong>Regio:</strong> {{ $entertainer->region }}</p>
                <p class="text-sm"><strong>Status:</strong> {{ $entertainer->active ? 'Actief' : 'Inactief' }}</p>
                <div class="flex flex-wrap gap-2">
                    @foreach ($entertainer->skills as $skill)
                        <span class="rounded-full bg-teal-50 px-3 py-1 text-xs font-medium text-teal-800">{{ $skill->name }}</span>
                    @endforeach
                </div>
            </aside>
            <div class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                <h2 class="text-lg font-semibold">Aanvragen</h2>
                <div class="mt-4 divide-y divide-slate-200">
                    @forelse ($bookingRequests as $bookingRequest)
                        <div class="py-4">
                            <div class="flex flex-wrap items-center justify-between gap-3">
                                <div>
                                    <p class="font-semibold">{{ $bookingRequest->name }} · {{ $bookingRequest->event_date->format('d-m-Y') }}</p>
                                    <p class="text-sm text-slate-600">{{ $bookingRequest->city }} · {{ $bookingRequest->status->label() }}</p>
                                </div>
                                <form method="POST" action="{{ route('dashboard.booking-requests.status', $bookingRequest) }}" class="flex gap-2">
                                    @csrf
                                    @method('PATCH')
                                    <select name="status" class="rounded-md border-slate-300 text-sm">
                                        <option value="optie">Optie</option>
                                        <option value="bevestigd">Bevestigd</option>
                                        <option value="afgewezen">Afgewezen</option>
                                    </select>
                                    <button class="rounded-md bg-slate-900 px-3 py-2 text-sm font-semibold text-white">Opslaan</button>
                                </form>
                            </div>
                        </div>
                    @empty
                        <p class="py-6 text-sm text-slate-600">Nog geen aanvragen.</p>
                    @endforelse
                </div>
                <div class="mt-4">{{ $bookingRequests->links() }}</div>
            </div>
        </div>
    </section>
</x-layouts.app>
