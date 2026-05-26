<x-layouts.app title="Entertainer dashboard">
    <section class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <h1 class="text-3xl font-bold text-slate-950">Dashboard {{ $entertainer->name }}</h1>
                <p class="mt-2 text-sm text-slate-600">Beheer je profiel, skills, beschikbaarheid, tarieven en aanvragen.</p>
            </div>
            @if (session('status'))
                <p class="rounded-md bg-green-50 px-4 py-3 text-sm font-medium text-green-800">{{ session('status') }}</p>
            @endif
        </div>

        @if ($errors->any())
            <div class="mt-6 rounded-md bg-red-50 p-4 text-sm text-red-800">
                Controleer de invoer en probeer opnieuw.
            </div>
        @endif

        <div class="mt-8 grid gap-6 xl:grid-cols-[minmax(0,1fr)_420px]">
            <div class="space-y-6">
                <form method="POST" action="{{ route('dashboard.profile.update') }}" class="grid gap-4 rounded-lg border border-slate-200 bg-white p-5 shadow-sm md:grid-cols-2">
                    @csrf
                    @method('PATCH')
                    <h2 class="text-lg font-semibold md:col-span-2">Profiel</h2>
                    <label class="space-y-1">
                        <span class="text-sm font-medium">Naam</span>
                        <input name="name" value="{{ old('name', $entertainer->name) }}" required class="w-full rounded-md border-slate-300 text-sm">
                        @error('name') <span class="text-sm text-red-700">{{ $message }}</span> @enderror
                    </label>
                    <label class="space-y-1">
                        <span class="text-sm font-medium">Woonplaats</span>
                        <input name="city" value="{{ old('city', $entertainer->city) }}" required class="w-full rounded-md border-slate-300 text-sm">
                        @error('city') <span class="text-sm text-red-700">{{ $message }}</span> @enderror
                    </label>
                    <label class="space-y-1">
                        <span class="text-sm font-medium">Regio</span>
                        <input name="region" value="{{ old('region', $entertainer->region) }}" required class="w-full rounded-md border-slate-300 text-sm">
                        @error('region') <span class="text-sm text-red-700">{{ $message }}</span> @enderror
                    </label>
                    <label class="space-y-1">
                        <span class="text-sm font-medium">Werkgebied in km</span>
                        <input name="working_radius_km" type="number" min="1" max="500" value="{{ old('working_radius_km', $entertainer->working_radius_km) }}" required class="w-full rounded-md border-slate-300 text-sm">
                        @error('working_radius_km') <span class="text-sm text-red-700">{{ $message }}</span> @enderror
                    </label>
                    <label class="space-y-1 md:col-span-2">
                        <span class="text-sm font-medium">Korte introductie</span>
                        <textarea name="short_introduction" rows="3" required class="w-full rounded-md border-slate-300 text-sm">{{ old('short_introduction', $entertainer->short_introduction) }}</textarea>
                        @error('short_introduction') <span class="text-sm text-red-700">{{ $message }}</span> @enderror
                    </label>
                    <label class="space-y-1 md:col-span-2">
                        <span class="text-sm font-medium">Bio</span>
                        <textarea name="bio" rows="6" class="w-full rounded-md border-slate-300 text-sm">{{ old('bio', $entertainer->bio) }}</textarea>
                        @error('bio') <span class="text-sm text-red-700">{{ $message }}</span> @enderror
                    </label>
                    <button class="rounded-md bg-slate-900 px-4 py-2 text-sm font-semibold text-white md:col-span-2">Profiel opslaan</button>
                </form>

                <form method="POST" action="{{ route('dashboard.skills.update') }}" class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                    @csrf
                    @method('PATCH')
                    <h2 class="text-lg font-semibold">Skills</h2>
                    <div class="mt-4 flex flex-wrap gap-2">
                        @foreach ($skills as $skill)
                            <label class="rounded-full border border-slate-200 px-3 py-1.5 text-sm">
                                <input type="checkbox" name="skills[]" value="{{ $skill->id }}" @checked($entertainer->skills->contains($skill)) class="mr-1 rounded border-slate-300">
                                {{ $skill->name }}
                            </label>
                        @endforeach
                    </div>
                    @error('skills') <p class="mt-2 text-sm text-red-700">{{ $message }}</p> @enderror
                    <button class="mt-4 rounded-md bg-slate-900 px-4 py-2 text-sm font-semibold text-white">Skills opslaan</button>
                </form>

                <div class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                    <h2 class="text-lg font-semibold">Beschikbaarheid</h2>
                    <form method="POST" action="{{ route('dashboard.availabilities.store') }}" class="mt-4 grid gap-3 md:grid-cols-5">
                        @csrf
                        <input name="date" type="date" required class="rounded-md border-slate-300 text-sm" aria-label="Datum">
                        <input name="start_time" type="time" required class="rounded-md border-slate-300 text-sm" aria-label="Starttijd">
                        <input name="end_time" type="time" required class="rounded-md border-slate-300 text-sm" aria-label="Eindtijd">
                        <select name="status" required class="rounded-md border-slate-300 text-sm" aria-label="Status">
                            @foreach (\App\Enums\AvailabilityStatus::cases() as $status)
                                <option value="{{ $status->value }}">{{ $status->label() }}</option>
                            @endforeach
                        </select>
                        <button class="rounded-md bg-teal-700 px-4 py-2 text-sm font-semibold text-white">Toevoegen</button>
                    </form>
                    <div class="mt-5 divide-y divide-slate-200">
                        @forelse ($entertainer->availabilities as $availability)
                            <form method="POST" action="{{ route('dashboard.availabilities.update', $availability) }}" class="grid gap-3 py-4 md:grid-cols-6">
                                @csrf
                                @method('PATCH')
                                <input name="date" type="date" value="{{ $availability->date->toDateString() }}" required class="rounded-md border-slate-300 text-sm">
                                <input name="start_time" type="time" value="{{ $availability->start_time->format('H:i') }}" required class="rounded-md border-slate-300 text-sm">
                                <input name="end_time" type="time" value="{{ $availability->end_time->format('H:i') }}" required class="rounded-md border-slate-300 text-sm">
                                <select name="status" required class="rounded-md border-slate-300 text-sm">
                                    @foreach (\App\Enums\AvailabilityStatus::cases() as $status)
                                        <option value="{{ $status->value }}" @selected($availability->status === $status)>{{ $status->label() }}</option>
                                    @endforeach
                                </select>
                                <button class="rounded-md bg-slate-900 px-3 py-2 text-sm font-semibold text-white">Opslaan</button>
                                <button form="delete-availability-{{ $availability->id }}" class="rounded-md border border-red-200 px-3 py-2 text-sm font-semibold text-red-700">Verwijderen</button>
                            </form>
                            <form id="delete-availability-{{ $availability->id }}" method="POST" action="{{ route('dashboard.availabilities.destroy', $availability) }}" class="hidden">
                                @csrf
                                @method('DELETE')
                            </form>
                        @empty
                            <p class="py-6 text-sm text-slate-600">Nog geen toekomstige beschikbaarheid.</p>
                        @endforelse
                    </div>
                </div>
            </div>

            <aside class="space-y-6">
                <div class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                    <h2 class="text-lg font-semibold">Tarieven</h2>
                    <form method="POST" action="{{ route('dashboard.rates.store') }}" class="mt-4 grid gap-3">
                        @csrf
                        <select name="customer_type" required class="rounded-md border-slate-300 text-sm" aria-label="Doelgroep">
                            @foreach (\App\Enums\CustomerType::cases() as $type)
                                <option value="{{ $type->value }}">{{ $type->label() }}</option>
                            @endforeach
                        </select>
                        <input name="starting_rate_cents" type="number" min="0" required placeholder="Starttarief in centen" class="rounded-md border-slate-300 text-sm">
                        <input name="hourly_rate_cents" type="number" min="0" required placeholder="Uurtarief in centen" class="rounded-md border-slate-300 text-sm">
                        <input name="minimum_hours" type="number" min="0.5" step="0.5" required placeholder="Minimum uren" class="rounded-md border-slate-300 text-sm">
                        <input name="travel_cost_cents_per_km" type="number" min="0" required placeholder="Reiskosten per km in centen" class="rounded-md border-slate-300 text-sm">
                        <label class="flex items-center gap-2 text-sm">
                            <input type="checkbox" name="vat_included" value="1" checked class="rounded border-slate-300">
                            Btw inclusief
                        </label>
                        <button class="rounded-md bg-teal-700 px-4 py-2 text-sm font-semibold text-white">Tarief toevoegen</button>
                    </form>
                    <div class="mt-5 space-y-4">
                        @foreach ($entertainer->rates as $rate)
                            <form method="POST" action="{{ route('dashboard.rates.update', $rate) }}" class="grid gap-2 rounded-md border border-slate-200 p-3">
                                @csrf
                                @method('PATCH')
                                <select name="customer_type" required class="rounded-md border-slate-300 text-sm">
                                    @foreach (\App\Enums\CustomerType::cases() as $type)
                                        <option value="{{ $type->value }}" @selected($rate->customer_type === $type)>{{ $type->label() }}</option>
                                    @endforeach
                                </select>
                                <input name="starting_rate_cents" type="number" min="0" value="{{ $rate->starting_rate_cents }}" required class="rounded-md border-slate-300 text-sm">
                                <input name="hourly_rate_cents" type="number" min="0" value="{{ $rate->hourly_rate_cents }}" required class="rounded-md border-slate-300 text-sm">
                                <input name="minimum_hours" type="number" min="0.5" step="0.5" value="{{ $rate->minimum_hours }}" required class="rounded-md border-slate-300 text-sm">
                                <input name="travel_cost_cents_per_km" type="number" min="0" value="{{ $rate->travel_cost_cents_per_km }}" required class="rounded-md border-slate-300 text-sm">
                                <label class="flex items-center gap-2 text-sm">
                                    <input type="checkbox" name="vat_included" value="1" @checked($rate->vat_included) class="rounded border-slate-300">
                                    Btw inclusief
                                </label>
                                <div class="grid grid-cols-2 gap-2">
                                    <button class="rounded-md bg-slate-900 px-3 py-2 text-sm font-semibold text-white">Opslaan</button>
                                    <button form="delete-rate-{{ $rate->id }}" class="rounded-md border border-red-200 px-3 py-2 text-sm font-semibold text-red-700">Verwijderen</button>
                                </div>
                            </form>
                            <form id="delete-rate-{{ $rate->id }}" method="POST" action="{{ route('dashboard.rates.destroy', $rate) }}" class="hidden">
                                @csrf
                                @method('DELETE')
                            </form>
                        @endforeach
                    </div>
                </div>

                <div class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                    <h2 class="text-lg font-semibold">Aanvragen</h2>
                    <div class="mt-4 divide-y divide-slate-200">
                        @forelse ($bookingRequests as $bookingRequest)
                            <div class="py-4">
                                <p class="font-semibold">{{ $bookingRequest->name }} · {{ $bookingRequest->event_date->format('d-m-Y') }}</p>
                                <p class="text-sm text-slate-600">{{ $bookingRequest->city }} · {{ $bookingRequest->status->label() }}</p>
                                <form method="POST" action="{{ route('dashboard.booking-requests.status', $bookingRequest) }}" class="mt-3 flex gap-2">
                                    @csrf
                                    @method('PATCH')
                                    <select name="status" class="min-w-0 flex-1 rounded-md border-slate-300 text-sm">
                                        <option value="optie" @selected($bookingRequest->status === \App\Enums\BookingStatus::Option)>Optie</option>
                                        <option value="bevestigd" @selected($bookingRequest->status === \App\Enums\BookingStatus::Confirmed)>Bevestigd</option>
                                        <option value="afgewezen" @selected($bookingRequest->status === \App\Enums\BookingStatus::Rejected)>Afgewezen</option>
                                    </select>
                                    <button class="rounded-md bg-slate-900 px-3 py-2 text-sm font-semibold text-white">Opslaan</button>
                                </form>
                            </div>
                        @empty
                            <p class="py-6 text-sm text-slate-600">Nog geen aanvragen.</p>
                        @endforelse
                    </div>
                    <div class="mt-4">{{ $bookingRequests->links() }}</div>
                </div>
            </aside>
        </div>
    </section>
</x-layouts.app>
