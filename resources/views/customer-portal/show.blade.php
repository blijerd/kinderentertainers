<x-layouts.app title="Aanvraag bekijken">
    <section class="brand-shell py-10">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <a href="{{ route('customer-portal.index') }}" class="text-sm font-bold text-brand-teal hover:text-brand-coral">Terug naar klantportaal</a>
                <h1 class="brand-heading mt-2 text-3xl">Aanvraag {{ $bookingRequest->event_date->format('d-m-Y') }}</h1>
                <p class="mt-2 text-sm text-slate-700 dark:text-slate-300">{{ $bookingRequest->status->label() }} · {{ $bookingRequest->city }}</p>
            </div>
            <a href="{{ route('customer-portal.download', $bookingRequest) }}" class="brand-button-secondary px-4 py-2">Document downloaden</a>
        </div>

        @if (session('status'))
            <p class="mt-6 rounded-md bg-green-50 px-4 py-3 text-sm font-medium text-green-800">{{ session('status') }}</p>
        @endif
        @if ($errors->any())
            <p class="mt-6 rounded-md bg-red-50 px-4 py-3 text-sm font-medium text-red-800">Controleer de invoer en probeer opnieuw.</p>
        @endif

        <div class="mt-8 grid gap-6 xl:grid-cols-[minmax(0,1fr)_360px]">
            <form method="POST" action="{{ route('customer-portal.update', $bookingRequest) }}" class="brand-card grid gap-4 p-5 md:grid-cols-2">
                @csrf
                @method('PATCH')
                <h2 class="text-lg font-bold text-brand-ink dark:text-white md:col-span-2">Gegevens wijzigen</h2>
                <label class="space-y-1">
                    <span class="text-sm font-medium">Klanttype</span>
                    <select name="customer_type" required class="w-full rounded-md border-slate-300 text-sm">
                        <option value="consument" @selected(old('customer_type', $bookingRequest->customer_type->value) === 'consument')>Consument</option>
                        <option value="b2b" @selected(old('customer_type', $bookingRequest->customer_type->value) === 'b2b')>Zakelijk</option>
                    </select>
                    @error('customer_type') <span class="text-sm text-red-700">{{ $message }}</span> @enderror
                </label>
                <label class="space-y-1">
                    <span class="text-sm font-medium">Bedrijfsnaam</span>
                    <input name="company_name" value="{{ old('company_name', $bookingRequest->company_name) }}" class="w-full rounded-md border-slate-300 text-sm">
                    @error('company_name') <span class="text-sm text-red-700">{{ $message }}</span> @enderror
                </label>
                <label class="space-y-1">
                    <span class="text-sm font-medium">Naam</span>
                    <input name="name" value="{{ old('name', $bookingRequest->name) }}" required class="w-full rounded-md border-slate-300 text-sm">
                    @error('name') <span class="text-sm text-red-700">{{ $message }}</span> @enderror
                </label>
                <label class="space-y-1">
                    <span class="text-sm font-medium">E-mail</span>
                    <input name="email" type="email" value="{{ old('email', $bookingRequest->email) }}" required class="w-full rounded-md border-slate-300 text-sm">
                    @error('email') <span class="text-sm text-red-700">{{ $message }}</span> @enderror
                </label>
                <label class="space-y-1">
                    <span class="text-sm font-medium">Telefoon</span>
                    <input name="phone" value="{{ old('phone', $bookingRequest->phone) }}" required class="w-full rounded-md border-slate-300 text-sm">
                    @error('phone') <span class="text-sm text-red-700">{{ $message }}</span> @enderror
                </label>
                <label class="space-y-1">
                    <span class="text-sm font-medium">Datum</span>
                    <input name="event_date" type="date" value="{{ old('event_date', $bookingRequest->event_date->toDateString()) }}" required class="w-full rounded-md border-slate-300 text-sm">
                    @error('event_date') <span class="text-sm text-red-700">{{ $message }}</span> @enderror
                </label>
                <label class="space-y-1">
                    <span class="text-sm font-medium">Starttijd</span>
                    <input name="start_time" type="time" value="{{ old('start_time', $bookingRequest->start_time->format('H:i')) }}" required class="w-full rounded-md border-slate-300 text-sm">
                    @error('start_time') <span class="text-sm text-red-700">{{ $message }}</span> @enderror
                </label>
                <label class="space-y-1">
                    <span class="text-sm font-medium">Eindtijd</span>
                    <input name="end_time" type="time" value="{{ old('end_time', $bookingRequest->end_time->format('H:i')) }}" required class="w-full rounded-md border-slate-300 text-sm">
                    @error('end_time') <span class="text-sm text-red-700">{{ $message }}</span> @enderror
                </label>
                <label class="space-y-1">
                    <span class="text-sm font-medium">Adres</span>
                    <input name="address" value="{{ old('address', $bookingRequest->address) }}" required class="w-full rounded-md border-slate-300 text-sm">
                    @error('address') <span class="text-sm text-red-700">{{ $message }}</span> @enderror
                </label>
                <label class="space-y-1">
                    <span class="text-sm font-medium">Postcode</span>
                    <input name="postal_code" value="{{ old('postal_code', $bookingRequest->postal_code) }}" required class="w-full rounded-md border-slate-300 text-sm">
                    @error('postal_code') <span class="text-sm text-red-700">{{ $message }}</span> @enderror
                </label>
                <label class="space-y-1">
                    <span class="text-sm font-medium">Plaats</span>
                    <input name="city" value="{{ old('city', $bookingRequest->city) }}" required class="w-full rounded-md border-slate-300 text-sm">
                    @error('city') <span class="text-sm text-red-700">{{ $message }}</span> @enderror
                </label>
                <label class="space-y-1">
                    <span class="text-sm font-medium">Aantal kinderen</span>
                    <input name="children_count" type="number" min="0" max="999" value="{{ old('children_count', $bookingRequest->children_count) }}" class="w-full rounded-md border-slate-300 text-sm">
                    @error('children_count') <span class="text-sm text-red-700">{{ $message }}</span> @enderror
                </label>
                <label class="space-y-1">
                    <span class="text-sm font-medium">Leeftijden</span>
                    <input name="children_ages" value="{{ old('children_ages', $bookingRequest->children_ages) }}" class="w-full rounded-md border-slate-300 text-sm">
                    @error('children_ages') <span class="text-sm text-red-700">{{ $message }}</span> @enderror
                </label>
                <label class="space-y-1 md:col-span-2">
                    <span class="text-sm font-medium">Bericht</span>
                    <textarea name="message" rows="4" class="w-full rounded-md border-slate-300 text-sm">{{ old('message', $bookingRequest->message) }}</textarea>
                    @error('message') <span class="text-sm text-red-700">{{ $message }}</span> @enderror
                </label>
                @if ($bookingRequest->entertainer?->packages)
                    <label class="space-y-1">
                        <span class="text-sm font-medium">Pakket</span>
                        <select name="selected_package" class="w-full rounded-md border-slate-300 text-sm">
                            <option value="">Geen voorkeur</option>
                            @foreach ($bookingRequest->entertainer->packages as $package)
                                <option value="{{ $package['name'] ?? '' }}" @selected(old('selected_package', $bookingRequest->selected_package) === ($package['name'] ?? ''))>
                                    {{ $package['name'] ?? 'Pakket' }}
                                    @if (($package['price_cents'] ?? null) !== null)
                                        · € {{ number_format($package['price_cents'] / 100, 2, ',', '.') }}
                                    @endif
                                </option>
                            @endforeach
                        </select>
                        @error('selected_package') <span class="text-sm text-red-700">{{ $message }}</span> @enderror
                    </label>
                @endif
                @if ($bookingRequest->entertainer?->extras)
                    <div class="space-y-2 md:col-span-2">
                        <span class="text-sm font-medium">Extras</span>
                        <div class="flex flex-wrap gap-2">
                            @foreach ($bookingRequest->entertainer->extras as $extra)
                                @php($extraName = $extra['name'] ?? '')
                                <label class="rounded-md border border-slate-200 px-3 py-2 text-sm">
                                    <input type="checkbox" name="selected_extras[]" value="{{ $extraName }}" @checked(in_array($extraName, old('selected_extras', $bookingRequest->selected_extras ?? []), true)) class="mr-1 rounded border-slate-300">
                                    {{ $extraName }}
                                    @if (($extra['price_cents'] ?? null) !== null)
                                        · € {{ number_format($extra['price_cents'] / 100, 2, ',', '.') }}
                                    @endif
                                </label>
                            @endforeach
                        </div>
                        @error('selected_extras') <span class="text-sm text-red-700">{{ $message }}</span> @enderror
                    </div>
                @endif
                <button class="brand-button md:col-span-2">Gegevens opslaan</button>
            </form>

            <aside class="space-y-6">
                <div class="brand-card p-5">
                    <h2 class="text-lg font-bold text-brand-ink dark:text-white">Offerte</h2>
                    <p class="mt-2 text-sm text-slate-600 dark:text-slate-300">
                        @if (! $bookingRequest->quote_sent_at)
                            Er is nog geen offerte verstuurd. Zodra de entertainer een offerte maakt, zie je hier het bedrag en kun je akkoord geven.
                        @elseif ($bookingRequest->quote_accepted_at)
                            Geaccepteerd op {{ $bookingRequest->quote_accepted_at->format('d-m-Y H:i') }}.
                        @else
                            Totaal: € {{ number_format($bookingRequest->quote_total_cents / 100, 2, ',', '.') }} · geldig t/m {{ $bookingRequest->quote_valid_until?->format('d-m-Y') }}.
                            @if ($bookingRequest->deposit_cents)
                                Aanbetaling: € {{ number_format($bookingRequest->deposit_cents / 100, 2, ',', '.') }}.
                            @endif
                        @endif
                    </p>
                    @if ($bookingRequest->quote_sent_at)
                        <p class="mt-2 text-sm text-slate-600 dark:text-slate-300">Betaalstatus: {{ $bookingRequest->payment_status }}.</p>
                    @endif
                    @if (
                        $bookingRequest->quote_sent_at
                        && $bookingRequest->quote_total_cents !== null
                        && ! $bookingRequest->quote_accepted_at
                        && ! $bookingRequest->quote_valid_until?->isPast()
                    )
                        <form method="POST" action="{{ route('customer-portal.accept-quote', $bookingRequest) }}" class="mt-4">
                            @csrf
                            <button class="brand-button w-full">Offerte accepteren</button>
                        </form>
                    @endif
                </div>

                <div class="brand-card p-5">
                    <h2 class="text-lg font-bold text-brand-ink dark:text-white">Berichten</h2>
                    <div class="mt-3 space-y-3">
                        @if ($bookingRequest->customer_message)
                            <div class="rounded-md border border-slate-200 bg-slate-50 p-3 text-sm text-slate-700 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-200">
                                {{ $bookingRequest->customer_message }}
                            </div>
                        @endif
                        @forelse ($bookingRequest->events as $event)
                            <div class="rounded-md border border-slate-200 bg-slate-50 p-3 text-sm text-slate-700 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-200">
                                <p class="font-semibold">{{ $event->actor_name ?: $event->type->label() }} · {{ $event->created_at->format('d-m-Y H:i') }}</p>
                                <p class="mt-1">{{ $event->body ?: $event->type->label() }}</p>
                            </div>
                        @empty
                            <div class="rounded-md border border-slate-200 bg-slate-50 p-3 text-sm text-slate-700 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-200">
                                Er zijn nog geen berichten voor deze aanvraag.
                            </div>
                        @endforelse
                    </div>
                    <form method="POST" action="{{ route('customer-portal.messages.store', $bookingRequest) }}" class="mt-4 grid gap-2">
                        @csrf
                        <textarea name="body" rows="3" required class="w-full rounded-md border-slate-300 text-sm" placeholder="Nieuw bericht"></textarea>
                        @error('body') <span class="text-sm text-red-700">{{ $message }}</span> @enderror
                        <button class="rounded-md bg-brand-ink px-3 py-2 text-sm font-bold text-white">Bericht plaatsen</button>
                    </form>
                </div>

                <div class="brand-card p-5">
                    <h2 class="text-lg font-bold text-brand-ink dark:text-white">Samenvatting</h2>
                    <dl class="mt-3 space-y-2 text-sm">
                        <div><dt class="font-bold">Entertainer</dt><dd>{{ $bookingRequest->entertainer?->name ?? 'Nog te kiezen' }}</dd></div>
                        <div><dt class="font-bold">Skill</dt><dd>{{ $bookingRequest->skill?->name ?? implode(', ', $bookingRequest->desired_skills ?? []) }}</dd></div>
                        <div><dt class="font-bold">Pakket</dt><dd>{{ $bookingRequest->selected_package ?: '-' }}</dd></div>
                        <div><dt class="font-bold">Extras</dt><dd>{{ implode(', ', $bookingRequest->selected_extras ?? []) ?: '-' }}</dd></div>
                        <div><dt class="font-bold">Locatie</dt><dd>{{ $bookingRequest->address }}, {{ $bookingRequest->postal_code }} {{ $bookingRequest->city }}</dd></div>
                        @if ($bookingRequest->entertainer?->cancellation_policy)
                            <div><dt class="font-bold">Annuleren</dt><dd>{{ $bookingRequest->entertainer->cancellation_policy }}</dd></div>
                        @endif
                    </dl>
                    @if (! in_array($bookingRequest->status, [\App\Enums\BookingStatus::Rejected, \App\Enums\BookingStatus::Cancelled], true))
                        <form method="POST" action="{{ route('customer-portal.cancel', $bookingRequest) }}" class="mt-4 grid gap-2">
                            @csrf
                            <textarea name="cancellation_reason" rows="3" required class="w-full rounded-md border-slate-300 text-sm" placeholder="Reden van annulering"></textarea>
                            @error('cancellation_reason') <span class="text-sm text-red-700">{{ $message }}</span> @enderror
                            <button class="rounded-md border border-red-200 px-3 py-2 text-sm font-semibold text-red-700">Aanvraag annuleren</button>
                        </form>
                    @elseif ($bookingRequest->cancelled_at)
                        <p class="mt-4 rounded-md bg-red-50 px-3 py-2 text-sm text-red-800">
                            Geannuleerd op {{ $bookingRequest->cancelled_at->format('d-m-Y H:i') }}: {{ $bookingRequest->cancellation_reason }}
                        </p>
                    @endif
                </div>
            </aside>
        </div>
    </section>
</x-layouts.app>
