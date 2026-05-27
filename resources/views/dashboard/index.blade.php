<x-layouts.app title="Entertainer dashboard">
    <section class="brand-shell py-10">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <h1 class="brand-heading text-3xl">Dashboard {{ $entertainer->name }}</h1>
                <p class="mt-2 text-sm text-slate-700">Beheer je profiel, skills, beschikbaarheid, tarieven en aanvragen.</p>
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

        <div class="mt-6 brand-card p-5">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h2 class="text-lg font-bold text-brand-ink">Profielkwaliteit {{ $profileQualityScore }}%</h2>
                    <p class="mt-1 text-sm text-slate-600">
                        @if ($profileMissingItems === [])
                            Je profiel is compleet genoeg voor sterke matches.
                        @else
                            Mist nog: {{ implode(', ', $profileMissingItems) }}.
                        @endif
                    </p>
                </div>
                <div class="h-3 w-48 overflow-hidden rounded-full bg-slate-100">
                    <div class="h-full bg-brand-teal" style="width: {{ $profileQualityScore }}%"></div>
                </div>
            </div>
        </div>

        <div class="mt-8 flex flex-wrap gap-2" data-dashboard-tabs>
            <button type="button" class="dashboard-tab" data-dashboard-tab="aanvragen" aria-pressed="true">Aanvragen</button>
            <button type="button" class="dashboard-tab" data-dashboard-tab="beschikbaarheid" aria-pressed="false">Beschikbaarheid</button>
            <button type="button" class="dashboard-tab" data-dashboard-tab="tarieven" aria-pressed="false">Tarieven</button>
            <button type="button" class="dashboard-tab" data-dashboard-tab="profiel" aria-pressed="false">Profiel</button>
        </div>

        <div class="mt-6 space-y-6">
            <div class="space-y-6 hidden" data-dashboard-panel="profiel">
                <form method="POST" action="{{ route('dashboard.profile.update') }}" enctype="multipart/form-data" class="brand-card grid gap-4 p-5 md:grid-cols-2">
                    @csrf
                    @method('PATCH')
                    <h2 class="text-lg font-bold text-brand-ink md:col-span-2">Profiel</h2>
                    <div class="md:col-span-2 grid gap-4 md:grid-cols-[180px_1fr]">
                        <div>
                            @if ($entertainer->profilePhotoUrl())
                                <img src="{{ $entertainer->profilePhotoUrl() }}" alt="{{ $entertainer->name }}" class="h-40 w-full rounded-lg object-cover">
                            @else
                                <div class="grid h-40 place-items-center rounded-lg bg-slate-100 text-sm font-semibold text-slate-500">Geen profielfoto</div>
                            @endif
                        </div>
                        <label class="space-y-1">
                            <span class="text-sm font-medium">Profielfoto</span>
                            <input name="profile_photo" type="file" accept="image/*" class="w-full rounded-md border border-slate-300 p-2 text-sm">
                            @error('profile_photo') <span class="text-sm text-red-700">{{ $message }}</span> @enderror
                        </label>
                    </div>
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
                    <label class="space-y-1 md:col-span-2">
                        <span class="text-sm font-medium">Highlights</span>
                        <textarea name="profile_highlights" rows="4" class="w-full rounded-md border-slate-300 text-sm" placeholder="Een highlight per regel">{{ old('profile_highlights', implode("\n", $entertainer->profileHighlightsList())) }}</textarea>
                        @error('profile_highlights') <span class="text-sm text-red-700">{{ $message }}</span> @enderror
                    </label>
                    <label class="space-y-1">
                        <span class="text-sm font-medium">Leeftijdsrange</span>
                        <input name="audience_age_range" value="{{ old('audience_age_range', $entertainer->audience_age_range) }}" placeholder="Bijv. 3 t/m 10 jaar" class="w-full rounded-md border-slate-300 text-sm">
                        @error('audience_age_range') <span class="text-sm text-red-700">{{ $message }}</span> @enderror
                    </label>
                    <label class="space-y-1">
                        <span class="text-sm font-medium">Feesttypes</span>
                        <textarea name="event_types" rows="3" class="w-full rounded-md border-slate-300 text-sm" placeholder="Een type per regel">{{ old('event_types', implode("\n", $entertainer->eventTypesList())) }}</textarea>
                        @error('event_types') <span class="text-sm text-red-700">{{ $message }}</span> @enderror
                    </label>
                    <label class="space-y-1">
                        <span class="text-sm font-medium">Talen</span>
                        <textarea name="languages" rows="3" class="w-full rounded-md border-slate-300 text-sm" placeholder="Een taal per regel">{{ old('languages', implode("\n", $entertainer->languagesList())) }}</textarea>
                        @error('languages') <span class="text-sm text-red-700">{{ $message }}</span> @enderror
                    </label>
                    <label class="space-y-1">
                        <span class="text-sm font-medium">Duur optreden in minuten</span>
                        <input name="performance_duration_minutes" type="number" min="1" max="1440" value="{{ old('performance_duration_minutes', $entertainer->performance_duration_minutes) }}" class="w-full rounded-md border-slate-300 text-sm">
                        @error('performance_duration_minutes') <span class="text-sm text-red-700">{{ $message }}</span> @enderror
                    </label>
                    <label class="space-y-1">
                        <span class="text-sm font-medium">Opbouwtijd in minuten</span>
                        <input name="setup_time_minutes" type="number" min="0" max="1440" value="{{ old('setup_time_minutes', $entertainer->setup_time_minutes) }}" class="w-full rounded-md border-slate-300 text-sm">
                        @error('setup_time_minutes') <span class="text-sm text-red-700">{{ $message }}</span> @enderror
                    </label>
                    <label class="space-y-1">
                        <span class="text-sm font-medium">Showreel URL</span>
                        <input name="show_reel_url" type="url" value="{{ old('show_reel_url', $entertainer->show_reel_url) }}" placeholder="https://..." class="w-full rounded-md border-slate-300 text-sm">
                        @error('show_reel_url') <span class="text-sm text-red-700">{{ $message }}</span> @enderror
                    </label>
                    <label class="space-y-1 md:col-span-2">
                        <span class="text-sm font-medium">Praktische eisen</span>
                        <textarea name="practical_requirements" rows="4" class="w-full rounded-md border-slate-300 text-sm">{{ old('practical_requirements', $entertainer->practical_requirements) }}</textarea>
                        @error('practical_requirements') <span class="text-sm text-red-700">{{ $message }}</span> @enderror
                    </label>
                    <label class="space-y-1 md:col-span-2">
                        <span class="text-sm font-medium">Pakketten</span>
                        <textarea name="packages" rows="4" class="w-full rounded-md border-slate-300 text-sm" placeholder="Naam | prijs | omschrijving">{{ old('packages', collect($entertainer->packages ?? [])->map(fn ($item) => trim(($item['name'] ?? '').' | '.(($item['price_cents'] ?? null) !== null ? number_format($item['price_cents'] / 100, 2, ',', '.') : '').' | '.($item['description'] ?? '')))->implode("\n")) }}</textarea>
                        @error('packages') <span class="text-sm text-red-700">{{ $message }}</span> @enderror
                    </label>
                    <label class="space-y-1 md:col-span-2">
                        <span class="text-sm font-medium">Extras</span>
                        <textarea name="extras" rows="4" class="w-full rounded-md border-slate-300 text-sm" placeholder="Naam | prijs | omschrijving">{{ old('extras', collect($entertainer->extras ?? [])->map(fn ($item) => trim(($item['name'] ?? '').' | '.(($item['price_cents'] ?? null) !== null ? number_format($item['price_cents'] / 100, 2, ',', '.') : '').' | '.($item['description'] ?? '')))->implode("\n")) }}</textarea>
                        @error('extras') <span class="text-sm text-red-700">{{ $message }}</span> @enderror
                    </label>
                    <label class="space-y-1">
                        <span class="text-sm font-medium">Aanbetaling in %</span>
                        <input name="deposit_percentage" type="number" min="0" max="100" value="{{ old('deposit_percentage', $entertainer->deposit_percentage) }}" required class="w-full rounded-md border-slate-300 text-sm">
                        @error('deposit_percentage') <span class="text-sm text-red-700">{{ $message }}</span> @enderror
                    </label>
                    <label class="space-y-1 md:col-span-2">
                        <span class="text-sm font-medium">Annuleringsvoorwaarden</span>
                        <textarea name="cancellation_policy" rows="4" class="w-full rounded-md border-slate-300 text-sm">{{ old('cancellation_policy', $entertainer->cancellation_policy) }}</textarea>
                        @error('cancellation_policy') <span class="text-sm text-red-700">{{ $message }}</span> @enderror
                    </label>
                    <div class="md:col-span-2 space-y-3">
                        <label class="space-y-1 block">
                            <span class="text-sm font-medium">Galerijfoto's toevoegen</span>
                            <input name="gallery_photos[]" type="file" accept="image/*" multiple class="w-full rounded-md border border-slate-300 p-2 text-sm">
                            @error('gallery_photos') <span class="text-sm text-red-700">{{ $message }}</span> @enderror
                            @error('gallery_photos.*') <span class="text-sm text-red-700">{{ $message }}</span> @enderror
                        </label>
                        @if ($entertainer->gallery_photo_paths)
                            <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                                @foreach ($entertainer->gallery_photo_paths as $photoPath)
                                    <label class="block overflow-hidden rounded-lg border border-slate-200">
                                        <img src="{{ Storage::disk('public')->url($photoPath) }}" alt="Galerijfoto {{ $loop->iteration }}" class="h-32 w-full object-cover">
                                        <span class="flex items-center gap-2 px-3 py-2 text-sm">
                                            <input type="checkbox" name="remove_gallery_photos[]" value="{{ $photoPath }}" class="rounded border-slate-300">
                                            Verwijderen
                                        </span>
                                    </label>
                                @endforeach
                            </div>
                        @endif
                    </div>
                    <button class="brand-button md:col-span-2">Profiel opslaan</button>
                </form>

                <form method="POST" action="{{ route('dashboard.skills.update') }}" class="brand-card p-5">
                    @csrf
                    @method('PATCH')
                    <h2 class="text-lg font-bold text-brand-ink">Skills</h2>
                    <div class="mt-4 flex flex-wrap gap-2">
                        @foreach ($skills as $skill)
                            <label class="rounded-full border border-teal-100 bg-teal-50 px-3 py-1.5 text-sm">
                                <input type="checkbox" name="skills[]" value="{{ $skill->id }}" @checked($entertainer->skills->contains($skill)) class="mr-1 rounded border-slate-300">
                                {{ $skill->name }}
                            </label>
                        @endforeach
                    </div>
                    @error('skills') <p class="mt-2 text-sm text-red-700">{{ $message }}</p> @enderror
                    <button class="brand-button mt-4 px-4 py-2">Skills opslaan</button>
                </form>

                <form method="POST" action="{{ route('dashboard.billing.update') }}" class="brand-card grid gap-4 p-5 md:grid-cols-2">
                    @csrf
                    @method('PATCH')
                    <div class="md:col-span-2">
                        <h2 class="text-lg font-bold text-brand-ink dark:text-white">Facturatie</h2>
                        <p class="mt-1 text-sm text-slate-600 dark:text-slate-300">Je factureert zelf: via een koppeling hieronder of handmatig buiten het platform.</p>
                    </div>
                    <label class="space-y-1">
                        <span class="text-sm font-medium">Boekhoudpakket</span>
                        <select name="accounting_provider" required class="w-full rounded-md border-slate-300 text-sm">
                            @foreach (\App\Enums\AccountingProvider::cases() as $provider)
                                <option value="{{ $provider->value }}" @selected($entertainer->accounting_provider === $provider)>{{ $provider->label() }}</option>
                            @endforeach
                        </select>
                        @error('accounting_provider') <span class="text-sm text-red-700">{{ $message }}</span> @enderror
                    </label>
                    <label class="space-y-1 md:col-span-2">
                        <span class="text-sm font-medium">Facturatienotities</span>
                        <textarea name="accounting_notes" rows="3" class="w-full rounded-md border-slate-300 text-sm" placeholder="Bijvoorbeeld: ik factureer handmatig, of ik gebruik een extern pakket dat nog niet gekoppeld is.">{{ old('accounting_notes', $entertainer->accounting_notes) }}</textarea>
                        @error('accounting_notes') <span class="text-sm text-red-700">{{ $message }}</span> @enderror
                    </label>
                    <button class="brand-button md:col-span-2">Facturatie opslaan</button>
                </form>

                <div class="brand-card p-5">
                    <h2 class="text-lg font-bold text-brand-ink dark:text-white">Integraties</h2>
                    <p class="mt-1 text-sm text-slate-600 dark:text-slate-300">Geavanceerd: gebruik dit alleen als je boekhouding, betalingen, e-mail of agenda wilt koppelen. Secrets worden versleuteld opgeslagen.</p>
                    <div class="mt-5 grid gap-4 lg:grid-cols-2">
                        @foreach ($entertainer->integrations as $integration)
                            <form method="POST" action="{{ route('dashboard.integrations.update', $integration) }}" class="grid gap-3 rounded-md border border-slate-200 p-4 dark:border-slate-700">
                                @csrf
                                @method('PATCH')
                                <div class="flex items-start justify-between gap-3">
                                    <div>
                                        <h3 class="font-bold">{{ $integration->provider->label() }}</h3>
                                        <p class="text-xs text-slate-500 dark:text-slate-400">
                                            @if ($integration->provider === \App\Enums\IntegrationProvider::Moneybird)
                                                Boekhouding en factuurreferenties.
                                            @elseif ($integration->provider === \App\Enums\IntegrationProvider::Mollie)
                                                Betaallinks en betaalstatussen.
                                            @elseif ($integration->provider === \App\Enums\IntegrationProvider::Postmark)
                                                Transactionele e-mail.
                                            @elseif ($integration->provider === \App\Enums\IntegrationProvider::Pushover)
                                                Pushmeldingen voor nieuwe aanvragen.
                                            @elseif ($integration->provider === \App\Enums\IntegrationProvider::GoogleCalendar)
                                                Blokkeer bezette momenten en synchroniseer boekingen.
                                            @else
                                                Microsoft 365 agenda en Outlook-beschikbaarheid.
                                            @endif
                                        </p>
                                    </div>
                                    <label class="flex items-center gap-2 text-sm">
                                        <input type="checkbox" name="enabled" value="1" @checked($integration->enabled) class="rounded border-slate-300">
                                        Actief
                                    </label>
                                </div>

                                @if ($integration->provider === \App\Enums\IntegrationProvider::Moneybird)
                                    <input name="api_token" type="password" autocomplete="off" placeholder="API-token" class="rounded-md border-slate-300 text-sm">
                                    <input name="administration_id" value="{{ old('administration_id', $integration->settings['administration_id'] ?? '') }}" placeholder="Administratie-ID" class="rounded-md border-slate-300 text-sm">
                                    <input name="workflow_id" value="{{ old('workflow_id', $integration->settings['workflow_id'] ?? '') }}" placeholder="Workflow-ID" class="rounded-md border-slate-300 text-sm">
                                @elseif ($integration->provider === \App\Enums\IntegrationProvider::Mollie)
                                    <input name="api_key" type="password" autocomplete="off" placeholder="API-key" class="rounded-md border-slate-300 text-sm">
                                    <input name="profile_id" value="{{ old('profile_id', $integration->settings['profile_id'] ?? '') }}" placeholder="Profiel-ID" class="rounded-md border-slate-300 text-sm">
                                @elseif ($integration->provider === \App\Enums\IntegrationProvider::Postmark)
                                    <input name="server_token" type="password" autocomplete="off" placeholder="Server-token" class="rounded-md border-slate-300 text-sm">
                                    <input name="message_stream" value="{{ old('message_stream', $integration->settings['message_stream'] ?? '') }}" placeholder="Message stream" class="rounded-md border-slate-300 text-sm">
                                    <input name="from_email" type="email" value="{{ old('from_email', $integration->settings['from_email'] ?? '') }}" placeholder="Afzender e-mail" class="rounded-md border-slate-300 text-sm">
                                @elseif ($integration->provider === \App\Enums\IntegrationProvider::Pushover)
                                    <input name="app_token" type="password" autocomplete="off" placeholder="App-token" class="rounded-md border-slate-300 text-sm">
                                    <input name="user_key" type="password" autocomplete="off" placeholder="User-key" class="rounded-md border-slate-300 text-sm">
                                @else
                                    @if ($integration->provider === \App\Enums\IntegrationProvider::OutlookCalendar)
                                        <input name="tenant_id" value="{{ old('tenant_id', $integration->settings['tenant_id'] ?? '') }}" placeholder="Azure tenant-ID" class="rounded-md border-slate-300 text-sm">
                                    @endif
                                    <input name="client_id" value="{{ old('client_id', $integration->settings['client_id'] ?? '') }}" placeholder="OAuth client-ID" class="rounded-md border-slate-300 text-sm">
                                    <input name="client_secret" type="password" autocomplete="off" placeholder="OAuth client secret" class="rounded-md border-slate-300 text-sm">
                                    <input name="refresh_token" type="password" autocomplete="off" placeholder="Refresh token" class="rounded-md border-slate-300 text-sm">
                                    <input name="calendar_id" value="{{ old('calendar_id', $integration->settings['calendar_id'] ?? '') }}" placeholder="Agenda-ID of e-mailadres" class="rounded-md border-slate-300 text-sm">
                                    <select name="sync_direction" required class="rounded-md border-slate-300 text-sm">
                                        <option value="read_only" @selected(old('sync_direction', $integration->settings['sync_direction'] ?? 'read_only') === 'read_only')>Alleen beschikbaarheid lezen</option>
                                        <option value="two_way" @selected(old('sync_direction', $integration->settings['sync_direction'] ?? 'read_only') === 'two_way')>Beschikbaarheid lezen en boekingen plaatsen</option>
                                    </select>
                                    <label class="flex items-center gap-2 text-sm text-slate-700 dark:text-slate-200">
                                        <input type="checkbox" name="block_busy_events" value="1" @checked((bool) old('block_busy_events', $integration->settings['block_busy_events'] ?? true)) class="rounded border-slate-300">
                                        Bezet in agenda blokkeert aanvragen
                                    </label>
                                @endif

                                <button class="rounded-md bg-brand-ink px-3 py-2 text-sm font-bold text-white dark:bg-brand-teal">Opslaan</button>
                            </form>
                        @endforeach
                    </div>
                </div>
            </div>

                <div class="brand-card p-5 hidden" data-dashboard-panel="beschikbaarheid">
                    <h2 class="text-lg font-bold text-brand-ink">Beschikbaarheid</h2>
                    @php($weekdayLabels = [0 => 'Zo', 1 => 'Ma', 2 => 'Di', 3 => 'Wo', 4 => 'Do', 5 => 'Vr', 6 => 'Za'])
                    <form method="POST" action="{{ route('dashboard.availability-rules.store') }}" class="mt-4 grid gap-3 md:grid-cols-6">
                        @csrf
                        <div class="md:col-span-6">
                            <h3 class="text-sm font-bold uppercase tracking-wide text-slate-500">Herhaling</h3>
                        </div>
                        <input name="name" value="{{ old('name') }}" required placeholder="Elke zaterdag / schoolvakantie" class="rounded-md border-slate-300 text-sm md:col-span-2" aria-label="Naam">
                        <select name="rule_type" required class="rounded-md border-slate-300 text-sm" aria-label="Herhalingstype">
                            <option value="weekly" @selected(old('rule_type', 'weekly') === 'weekly')>Wekelijks</option>
                            <option value="date_range" @selected(old('rule_type') === 'date_range')>Datumreeks</option>
                        </select>
                        <input name="starts_on" type="date" value="{{ old('starts_on', now()->toDateString()) }}" required class="rounded-md border-slate-300 text-sm" aria-label="Startdatum">
                        <input name="ends_on" type="date" value="{{ old('ends_on') }}" class="rounded-md border-slate-300 text-sm" aria-label="Einddatum">
                        <select name="status" required class="rounded-md border-slate-300 text-sm" aria-label="Status">
                            @foreach (\App\Enums\AvailabilityStatus::cases() as $status)
                                <option value="{{ $status->value }}" @selected(old('status', \App\Enums\AvailabilityStatus::Available->value) === $status->value)>{{ $status->label() }}</option>
                            @endforeach
                        </select>
                        <div class="flex flex-wrap gap-2 md:col-span-2">
                            @foreach ($weekdayLabels as $weekday => $label)
                                <label class="rounded-md border border-slate-200 px-2 py-1 text-sm">
                                    <input type="checkbox" name="weekdays[]" value="{{ $weekday }}" @checked(in_array((string) $weekday, old('weekdays', []), true)) class="mr-1 rounded border-slate-300">
                                    {{ $label }}
                                </label>
                            @endforeach
                        </div>
                        <input name="start_time" type="time" value="{{ old('start_time', '10:00') }}" required class="rounded-md border-slate-300 text-sm" aria-label="Starttijd">
                        <input name="end_time" type="time" value="{{ old('end_time', '16:00') }}" required class="rounded-md border-slate-300 text-sm" aria-label="Eindtijd">
                        <input name="internal_note" value="{{ old('internal_note') }}" placeholder="Notitie" class="rounded-md border-slate-300 text-sm md:col-span-2" aria-label="Interne notitie">
                        <button class="brand-button px-4 py-2">Herhaling toevoegen</button>
                    </form>

                    <div class="mt-5 divide-y divide-slate-200">
                        @forelse ($entertainer->availabilityRules as $rule)
                            <form method="POST" action="{{ route('dashboard.availability-rules.update', $rule) }}" class="grid gap-3 py-4 md:grid-cols-6">
                                @csrf
                                @method('PATCH')
                                <input name="name" value="{{ $rule->name }}" required class="rounded-md border-slate-300 text-sm md:col-span-2">
                                <select name="rule_type" required class="rounded-md border-slate-300 text-sm">
                                    <option value="weekly" @selected($rule->rule_type === 'weekly')>Wekelijks</option>
                                    <option value="date_range" @selected($rule->rule_type === 'date_range')>Datumreeks</option>
                                </select>
                                <input name="starts_on" type="date" value="{{ $rule->starts_on->toDateString() }}" required class="rounded-md border-slate-300 text-sm">
                                <input name="ends_on" type="date" value="{{ $rule->ends_on?->toDateString() }}" class="rounded-md border-slate-300 text-sm">
                                <select name="status" required class="rounded-md border-slate-300 text-sm">
                                    @foreach (\App\Enums\AvailabilityStatus::cases() as $status)
                                        <option value="{{ $status->value }}" @selected($rule->status === $status)>{{ $status->label() }}</option>
                                    @endforeach
                                </select>
                                <div class="flex flex-wrap gap-2 md:col-span-2">
                                    @foreach ($weekdayLabels as $weekday => $label)
                                        <label class="rounded-md border border-slate-200 px-2 py-1 text-sm">
                                            <input type="checkbox" name="weekdays[]" value="{{ $weekday }}" @checked(in_array($weekday, $rule->weekdays ?? [], true)) class="mr-1 rounded border-slate-300">
                                            {{ $label }}
                                        </label>
                                    @endforeach
                                </div>
                                <input name="start_time" type="time" value="{{ $rule->start_time->format('H:i') }}" required class="rounded-md border-slate-300 text-sm">
                                <input name="end_time" type="time" value="{{ $rule->end_time->format('H:i') }}" required class="rounded-md border-slate-300 text-sm">
                                <input name="internal_note" value="{{ $rule->internal_note }}" placeholder="Notitie" class="rounded-md border-slate-300 text-sm">
                                <button class="rounded-md bg-brand-ink px-3 py-2 text-sm font-bold text-white">Opslaan</button>
                                <button form="delete-availability-rule-{{ $rule->id }}" class="rounded-md border border-red-200 px-3 py-2 text-sm font-semibold text-red-700">Verwijderen</button>
                            </form>
                            <form id="delete-availability-rule-{{ $rule->id }}" method="POST" action="{{ route('dashboard.availability-rules.destroy', $rule) }}" class="hidden">
                                @csrf
                                @method('DELETE')
                            </form>
                        @empty
                            <p class="py-4 text-sm text-slate-600">Nog geen herhalende beschikbaarheid.</p>
                        @endforelse
                    </div>

                    <form method="POST" action="{{ route('dashboard.availabilities.store') }}" class="mt-6 grid gap-3 border-t border-slate-200 pt-5 md:grid-cols-5">
                        @csrf
                        <div class="md:col-span-5">
                            <h3 class="text-sm font-bold uppercase tracking-wide text-slate-500">Losse datum of uitzondering</h3>
                        </div>
                        <input name="date" type="date" required class="rounded-md border-slate-300 text-sm" aria-label="Datum">
                        <input name="start_time" type="time" required class="rounded-md border-slate-300 text-sm" aria-label="Starttijd">
                        <input name="end_time" type="time" required class="rounded-md border-slate-300 text-sm" aria-label="Eindtijd">
                        <select name="status" required class="rounded-md border-slate-300 text-sm" aria-label="Status">
                            @foreach (\App\Enums\AvailabilityStatus::cases() as $status)
                                <option value="{{ $status->value }}">{{ $status->label() }}</option>
                            @endforeach
                        </select>
                        <button class="brand-button px-4 py-2">Toevoegen</button>
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
                                <button class="rounded-md bg-brand-ink px-3 py-2 text-sm font-bold text-white">Opslaan</button>
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

                <div class="brand-card p-5 hidden" data-dashboard-panel="tarieven">
                    <h2 class="text-lg font-bold text-brand-ink">Tarieven</h2>
                    <form method="POST" action="{{ route('dashboard.rates.store') }}" class="mt-4 grid gap-3">
                        @csrf
                        <select name="customer_type" required class="rounded-md border-slate-300 text-sm" aria-label="Doelgroep">
                            @foreach (\App\Enums\CustomerType::cases() as $type)
                                <option value="{{ $type->value }}">{{ $type->label() }}</option>
                            @endforeach
                        </select>
                        <input name="starting_rate_euros" type="number" min="0" step="0.01" required placeholder="Starttarief in euro" class="rounded-md border-slate-300 text-sm">
                        <input name="hourly_rate_euros" type="number" min="0" step="0.01" required placeholder="Uurtarief in euro" class="rounded-md border-slate-300 text-sm">
                        <input name="minimum_hours" type="number" min="0.5" step="0.5" required placeholder="Minimum uren" class="rounded-md border-slate-300 text-sm">
                        <input name="travel_cost_euros_per_km" type="number" min="0" step="0.01" required placeholder="Reiskosten per km in euro" class="rounded-md border-slate-300 text-sm">
                        <label class="flex items-center gap-2 text-sm">
                            <input type="checkbox" name="vat_included" value="1" checked class="rounded border-slate-300">
                            Btw inclusief
                        </label>
                        <button class="brand-button px-4 py-2">Tarief toevoegen</button>
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
                                <input name="starting_rate_euros" type="number" min="0" step="0.01" value="{{ number_format($rate->starting_rate_cents / 100, 2, '.', '') }}" required class="rounded-md border-slate-300 text-sm">
                                <input name="hourly_rate_euros" type="number" min="0" step="0.01" value="{{ number_format($rate->hourly_rate_cents / 100, 2, '.', '') }}" required class="rounded-md border-slate-300 text-sm">
                                <input name="minimum_hours" type="number" min="0.5" step="0.5" value="{{ $rate->minimum_hours }}" required class="rounded-md border-slate-300 text-sm">
                                <input name="travel_cost_euros_per_km" type="number" min="0" step="0.01" value="{{ number_format($rate->travel_cost_cents_per_km / 100, 2, '.', '') }}" required class="rounded-md border-slate-300 text-sm">
                                <label class="flex items-center gap-2 text-sm">
                                    <input type="checkbox" name="vat_included" value="1" @checked($rate->vat_included) class="rounded border-slate-300">
                                    Btw inclusief
                                </label>
                                <div class="grid grid-cols-2 gap-2">
                                    <button class="rounded-md bg-brand-ink px-3 py-2 text-sm font-bold text-white">Opslaan</button>
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

                <div class="brand-card p-5" data-dashboard-panel="aanvragen">
                    <h2 class="text-lg font-bold text-brand-ink">Nieuwe algemene aanvragen</h2>
                    <p class="mt-1 text-sm text-slate-600">Reageer met beschikbaarheid en een prijsindicatie. De klant kiest daarna uit de beschikbare entertainers.</p>
                    <div class="mt-4 divide-y divide-slate-200">
                        @forelse ($bookingRequestMatches as $match)
                            @php($bookingRequest = $match->bookingRequest)
                            <div class="py-4">
                                <div class="flex items-start justify-between gap-3">
                                    <div>
                                        <p class="font-semibold">{{ $bookingRequest->name }} · {{ $bookingRequest->event_date->format('d-m-Y') }}</p>
                                        <p class="text-sm text-slate-600">
                                            {{ $bookingRequest->city }} · {{ $bookingRequest->skill?->name ?? 'Algemeen' }} · {{ $match->status->label() }}
                                            · match {{ $match->match_score }}%
                                            @if ($match->distance_km !== null)
                                                · {{ number_format((float) $match->distance_km, 1, ',', '.') }} km
                                            @endif
                                        </p>
                                    </div>
                                    @if ($match->responded_at)
                                        <span class="rounded-full bg-teal-50 px-2.5 py-1 text-xs font-semibold text-teal-800">Gereageerd</span>
                                    @endif
                                </div>
                                <p class="mt-2 text-sm text-slate-700">{{ $bookingRequest->message }}</p>
                                <form method="POST" action="{{ route('dashboard.matches.response', $match) }}" class="mt-3 grid gap-2">
                                    @csrf
                                    @method('PATCH')
                                    <div class="grid gap-2 sm:grid-cols-2">
                                        <select name="response" class="rounded-md border-slate-300 text-sm">
                                            <option value="available" @selected($match->status === \App\Enums\BookingRequestMatchStatus::Available)>Beschikbaar</option>
                                            <option value="rejected" @selected($match->status === \App\Enums\BookingRequestMatchStatus::Rejected)>Afwijzen</option>
                                        </select>
                                        <input
                                            name="price_indication_euros"
                                            type="number"
                                            min="0"
                                            step="0.01"
                                            value="{{ old('price_indication_euros', $match->price_indication_cents !== null ? number_format($match->price_indication_cents / 100, 2, '.', '') : '') }}"
                                            placeholder="Prijsindicatie in euro"
                                            class="rounded-md border-slate-300 text-sm"
                                        >
                                    </div>
                                    <textarea name="response_message" rows="3" class="rounded-md border-slate-300 text-sm" placeholder="Bericht aan klant">{{ old('response_message', $match->response_message) }}</textarea>
                                    <button class="rounded-md bg-brand-ink px-3 py-2 text-sm font-bold text-white">Reactie opslaan</button>
                                </form>
                                @include('dashboard.partials.booking-request-timeline', ['bookingRequest' => $bookingRequest])
                            </div>
                        @empty
                            <p class="py-6 text-sm text-slate-600">Geen algemene aanvragen gevonden.</p>
                        @endforelse
                    </div>
                    <div class="mt-4">{{ $bookingRequestMatches->links() }}</div>
                </div>

                <div class="brand-card p-5" data-dashboard-panel="aanvragen">
                    <h2 class="text-lg font-bold text-brand-ink">Gekozen en directe aanvragen</h2>
                    <p class="mt-1 text-sm text-slate-600">Werk status bij, maak een offerte en houd de tijdlijn compleet.</p>
                    <div class="mt-4 divide-y divide-slate-200">
                        @forelse ($bookingRequests as $bookingRequest)
                            <div class="py-4">
                                <p class="font-semibold">{{ $bookingRequest->name }} · {{ $bookingRequest->event_date->format('d-m-Y') }}</p>
                                <p class="text-sm text-slate-600">{{ $bookingRequest->city }} · {{ $bookingRequest->status->label() }}</p>
                                @if ($bookingRequest->price_indication_min_cents)
                                    @php($minIndication = number_format($bookingRequest->price_indication_min_cents / 100, 0, ',', '.'))
                                    @php($maxIndication = number_format(($bookingRequest->price_indication_max_cents ?? $bookingRequest->price_indication_min_cents) / 100, 0, ',', '.'))
                                    <p class="mt-1 text-sm font-semibold text-brand-ink">
                                        Indicatie {{ $minIndication === $maxIndication ? "€ {$minIndication}" : "€ {$minIndication} - € {$maxIndication}" }}
                                    </p>
                                @endif
                                @if ($bookingRequest->quote_sent_at)
                                    <div class="mt-3 rounded-md border border-teal-100 bg-teal-50 p-3 text-sm text-teal-950">
                                        <p class="font-semibold">
                                            Offerte: € {{ number_format($bookingRequest->quote_total_cents / 100, 2, ',', '.') }}
                                        </p>
                                        <p class="mt-1">
                                            Geldig t/m {{ $bookingRequest->quote_valid_until?->format('d-m-Y') }}
                                            @if ($bookingRequest->quote_accepted_at)
                                                · akkoord op {{ $bookingRequest->quote_accepted_at->format('d-m-Y H:i') }}
                                            @endif
                                        </p>
                                        @if ($bookingRequest->deposit_cents)
                                            <p class="mt-1">Aanbetaling: € {{ number_format($bookingRequest->deposit_cents / 100, 2, ',', '.') }} · betaalstatus {{ $bookingRequest->payment_status }}</p>
                                        @endif
                                        <a href="{{ route('booking-quotes.show', $bookingRequest->quote_acceptance_token) }}" class="mt-2 inline-block break-all font-semibold text-brand-ink">
                                            {{ route('booking-quotes.show', $bookingRequest->quote_acceptance_token) }}
                                        </a>
                                    </div>
                                @endif
                                <form method="POST" action="{{ route('dashboard.booking-requests.status', $bookingRequest) }}" class="mt-3 flex gap-2">
                                    @csrf
                                    @method('PATCH')
                                    <select name="status" class="min-w-0 flex-1 rounded-md border-slate-300 text-sm">
                                        <option value="optie" @selected($bookingRequest->status === \App\Enums\BookingStatus::Option)>Optie</option>
                                        <option value="bevestigd" @selected($bookingRequest->status === \App\Enums\BookingStatus::Confirmed)>Bevestigd</option>
                                        <option value="afgewezen" @selected($bookingRequest->status === \App\Enums\BookingStatus::Rejected)>Afgewezen</option>
                                        <option value="geannuleerd" @selected($bookingRequest->status === \App\Enums\BookingStatus::Cancelled)>Geannuleerd</option>
                                    </select>
                                    <button class="rounded-md bg-brand-ink px-3 py-2 text-sm font-bold text-white">Opslaan</button>
                                    <input name="cancellation_reason" class="min-w-0 flex-1 rounded-md border-slate-300 text-sm" placeholder="Reden bij annulering">
                                </form>
                                <form method="POST" action="{{ route('dashboard.booking-requests.quote', $bookingRequest) }}" class="mt-3 grid gap-2 sm:grid-cols-[1fr_1fr_auto]">
                                    @csrf
                                    <input name="travel_distance_km" type="number" min="0" max="999.9" step="0.1" value="{{ old('travel_distance_km', $bookingRequest->quote_travel_distance_km ?? 0) }}" class="rounded-md border-slate-300 text-sm" aria-label="Reisafstand in km">
                                    <select name="valid_days" class="rounded-md border-slate-300 text-sm" aria-label="Geldigheid">
                                        <option value="7">7 dagen geldig</option>
                                        <option value="14" selected>14 dagen geldig</option>
                                        <option value="30">30 dagen geldig</option>
                                    </select>
                                    <button class="rounded-md border border-teal-200 px-3 py-2 text-sm font-bold text-teal-900">
                                        {{ $bookingRequest->quote_sent_at ? 'Offerte vernieuwen' : 'Offerte maken' }}
                                    </button>
                                </form>
                                @include('dashboard.partials.booking-request-timeline', ['bookingRequest' => $bookingRequest, 'canAddEvent' => true])
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
