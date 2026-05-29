<?php

use App\Models\Entertainer;
use App\Services\PriceIndicationService;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Component;

new class extends Component
{
    public ?Entertainer $entertainer = null;

    /** @var Collection<int, \App\Models\Skill> */
    public Collection $skills;

    public string $requestType = 'general';

    public ?int $skillId = null;

    public string $customerType = 'consument';

    public string $eventDate = '';

    public string $startTime = '';

    public string $endTime = '';

    public string $eventRegion = '';

    public ?int $travelTimeMinutes = null;

    public ?int $childrenCount = null;

    public function mount(?Entertainer $entertainer = null): void
    {
        $this->requestType = old('request_type', $entertainer ? 'specific' : 'general');
        $this->skillId = old('skill_id') ? (int) old('skill_id') : $entertainer?->skills->first()?->id;
        $this->customerType = old('customer_type', 'consument');
        $this->eventDate = old('event_date', '');
        $this->startTime = old('start_time', '');
        $this->endTime = old('end_time', '');
        $this->eventRegion = old('event_region', '');
        $this->travelTimeMinutes = old('travel_time_minutes') !== null ? (int) old('travel_time_minutes') : null;
        $this->childrenCount = old('children_count') !== null ? (int) old('children_count') : null;
    }

    /**
     * @return array{min_cents: int, max_cents: int, currency: string, breakdown: array<string, mixed>}|null
     */
    public function priceIndication(): ?array
    {
        return app(PriceIndicationService::class)->estimate([
            'entertainer_id' => $this->requestType === 'specific' ? $this->entertainer?->id : null,
            'skill_id' => $this->skillId,
            'customer_type' => $this->customerType,
            'event_date' => $this->eventDate,
            'start_time' => $this->startTime,
            'end_time' => $this->endTime,
            'event_region' => $this->eventRegion,
            'travel_time_minutes' => $this->travelTimeMinutes,
            'children_count' => $this->childrenCount,
        ], $this->requestType === 'specific' ? $this->entertainer : null);
    }

    public function formatMoney(int $cents): string
    {
        return '€ '.number_format($cents / 100, 0, ',', '.');
    }
};
?>

@php($availableSkills = $entertainer?->skills ?? $skills)
@php($priceIndication = $this->priceIndication())

<form method="POST" action="{{ $entertainer ? route('booking-requests.store', $entertainer) : route('booking-requests.general.store') }}" class="brand-card grid gap-6 p-5 md:grid-cols-2 lg:p-7">
    @csrf
    <div class="md:col-span-2">
        <p class="brand-kicker">Stap 1</p>
        <h2 class="brand-section-title mt-1">Wat zoek je?</h2>
    </div>
    <fieldset class="space-y-3 md:col-span-2">
        <legend class="brand-field-label">Aanvraagtype</legend>
        <div class="grid gap-3 md:grid-cols-2">
            <label class="brand-choice">
                <input type="radio" name="request_type" value="specific" wire:model.live="requestType" @disabled(! $entertainer) class="mr-2 border-slate-300 text-brand-teal">
                Ik wil een specifieke entertainer aanvragen
            </label>
            <label class="brand-choice">
                <input type="radio" name="request_type" value="general" wire:model.live="requestType" @disabled($entertainer) class="mr-2 border-slate-300 text-brand-teal">
                Zoek beschikbare entertainers voor deze skill
            </label>
        </div>
        <p class="rounded-md bg-brand-mint p-3 text-sm font-bold text-teal-950">
            @if ($entertainer)
                Je vraagt beschikbaarheid aan bij deze specifieke entertainer.
            @else
                We zoeken beschikbare entertainers voor je aanvraag.
            @endif
        </p>
        @error('request_type') <p class="text-sm text-red-700">{{ $message }}</p> @enderror
    </fieldset>
    <label class="brand-field md:col-span-2">
        <span class="brand-field-label">Skill</span>
        <select name="skill_id" wire:model.live="skillId" class="brand-input">
            <option value="">Kies een skill</option>
            @foreach ($availableSkills as $skill)
                <option value="{{ $skill->id }}">{{ $skill->name }}</option>
            @endforeach
        </select>
        @error('skill_id') <span class="text-sm text-red-700">{{ $message }}</span> @enderror
    </label>
    <div class="border-t border-slate-200 pt-5 md:col-span-2">
        <p class="brand-kicker">Stap 2</p>
        <h2 class="brand-section-title mt-1">Contact en locatie</h2>
    </div>
    <label class="brand-field">
        <span class="brand-field-label">Klanttype</span>
        <select name="customer_type" wire:model.live="customerType" class="brand-input">
            <option value="consument">Consument</option>
            <option value="b2b">Zakelijk</option>
        </select>
        @error('customer_type') <span class="text-sm text-red-700">{{ $message }}</span> @enderror
    </label>
    <label class="brand-field">
        <span class="brand-field-label">Naam</span>
        <input name="name" value="{{ old('name') }}" required class="brand-input">
        @error('name') <span class="text-sm text-red-700">{{ $message }}</span> @enderror
    </label>
    <label class="brand-field">
        <span class="brand-field-label">Bedrijfsnaam <span class="font-normal text-slate-500">(verplicht bij zakelijk)</span></span>
        <input name="company_name" value="{{ old('company_name') }}" class="brand-input">
        @error('company_name') <span class="text-sm text-red-700">{{ $message }}</span> @enderror
    </label>
    <label class="brand-field">
        <span class="brand-field-label">E-mail</span>
        <input name="email" type="email" value="{{ old('email') }}" required class="brand-input">
        @error('email') <span class="text-sm text-red-700">{{ $message }}</span> @enderror
    </label>
    <label class="brand-field">
        <span class="brand-field-label">Telefoon</span>
        <input name="phone" value="{{ old('phone') }}" required class="brand-input">
        @error('phone') <span class="text-sm text-red-700">{{ $message }}</span> @enderror
    </label>
    <label class="brand-field">
        <span class="brand-field-label">Evenementdatum</span>
        <input name="event_date" type="date" wire:model.live="eventDate" required class="brand-input">
        @error('event_date') <span class="text-sm text-red-700">{{ $message }}</span> @enderror
    </label>
    <label class="brand-field">
        <span class="brand-field-label">Starttijd</span>
        <input name="start_time" type="time" wire:model.live="startTime" required class="brand-input">
        @error('start_time') <span class="text-sm text-red-700">{{ $message }}</span> @enderror
    </label>
    <label class="brand-field">
        <span class="brand-field-label">Eindtijd</span>
        <input name="end_time" type="time" wire:model.live="endTime" required class="brand-input">
        @error('end_time') <span class="text-sm text-red-700">{{ $message }}</span> @enderror
    </label>
    <label class="brand-field md:col-span-2">
        <span class="brand-field-label">Adres</span>
        <input name="address" value="{{ old('address') }}" required class="brand-input">
        @error('address') <span class="text-sm text-red-700">{{ $message }}</span> @enderror
    </label>
    <label class="brand-field">
        <span class="brand-field-label">Postcode</span>
        <input name="postal_code" value="{{ old('postal_code') }}" required class="brand-input">
        @error('postal_code') <span class="text-sm text-red-700">{{ $message }}</span> @enderror
    </label>
    <label class="brand-field">
        <span class="brand-field-label">Plaats</span>
        <input name="city" value="{{ old('city') }}" required class="brand-input">
        @error('city') <span class="text-sm text-red-700">{{ $message }}</span> @enderror
    </label>
    <label class="brand-field">
        <span class="brand-field-label">Regio</span>
        <input name="event_region" wire:model.live.debounce.300ms="eventRegion" placeholder="Bijv. Utrecht" class="brand-input">
        <span class="text-xs text-slate-500">Helpt bij het matchen met entertainers in de buurt.</span>
        @error('event_region') <span class="text-sm text-red-700">{{ $message }}</span> @enderror
    </label>
    <label class="brand-field">
        <span class="brand-field-label">Reistijd enkele reis <span class="font-normal text-slate-500">(optioneel)</span></span>
        <input name="travel_time_minutes" type="number" min="0" max="480" wire:model.live.debounce.300ms="travelTimeMinutes" class="brand-input">
        @error('travel_time_minutes') <span class="text-sm text-red-700">{{ $message }}</span> @enderror
    </label>
    <div class="border-t border-slate-200 pt-5 md:col-span-2">
        <p class="brand-kicker">Stap 3</p>
        <h2 class="brand-section-title mt-1">Kinderen, wensen en prijs</h2>
    </div>
    <label class="brand-field">
        <span class="brand-field-label">Aantal kinderen</span>
        <input name="children_count" type="number" min="0" wire:model.live.debounce.300ms="childrenCount" class="brand-input">
        @error('children_count') <span class="text-sm text-red-700">{{ $message }}</span> @enderror
    </label>
    <label class="brand-field">
        <span class="brand-field-label">Leeftijd kinderen</span>
        <input name="children_ages" value="{{ old('children_ages') }}" class="brand-input">
        @error('children_ages') <span class="text-sm text-red-700">{{ $message }}</span> @enderror
    </label>
    <fieldset class="space-y-2 md:col-span-2">
        <legend class="brand-field-label">Gewenste skills</legend>
        <div class="flex flex-wrap gap-2">
            @foreach ($availableSkills as $skill)
                <label class="rounded-full border border-teal-100 bg-brand-mint px-3 py-1.5 text-sm font-semibold text-brand-teal">
                    <input type="checkbox" name="desired_skills[]" value="{{ $skill->name }}" @checked(in_array($skill->name, old('desired_skills', []), true)) class="mr-1 rounded border-slate-300">
                    {{ $skill->name }}
                </label>
            @endforeach
        </div>
        @error('desired_skills') <p class="text-sm text-red-700">{{ $message }}</p> @enderror
    </fieldset>
    @if ($entertainer?->packages)
        <label class="brand-field md:col-span-2">
            <span class="brand-field-label">Pakket</span>
            <select name="selected_package" class="brand-input">
                <option value="">Geen voorkeur</option>
                @foreach ($entertainer->packages as $package)
                    <option value="{{ $package['name'] ?? '' }}" @selected(old('selected_package') === ($package['name'] ?? ''))>
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
    @if ($entertainer?->extras)
        <fieldset class="space-y-2 md:col-span-2">
            <legend class="brand-field-label">Extras</legend>
            <div class="flex flex-wrap gap-2">
                @foreach ($entertainer->extras as $extra)
                    @php($extraName = $extra['name'] ?? '')
                    <label class="rounded-full border border-amber-100 bg-amber-50 px-3 py-1.5 text-sm font-semibold text-amber-900">
                        <input type="checkbox" name="selected_extras[]" value="{{ $extraName }}" @checked(in_array($extraName, old('selected_extras', []), true)) class="mr-1 rounded border-slate-300">
                        {{ $extraName }}
                        @if (($extra['price_cents'] ?? null) !== null)
                            · € {{ number_format($extra['price_cents'] / 100, 2, ',', '.') }}
                        @endif
                    </label>
                @endforeach
            </div>
            @error('selected_extras') <p class="text-sm text-red-700">{{ $message }}</p> @enderror
        </fieldset>
    @endif
    <label class="brand-field md:col-span-2">
        <span class="brand-field-label">Bericht</span>
        <textarea name="message" rows="5" class="brand-input">{{ old('message') }}</textarea>
        @error('message') <span class="text-sm text-red-700">{{ $message }}</span> @enderror
    </label>
    <div class="rounded-md border border-teal-100 bg-brand-mint p-4 md:col-span-2">
        <p class="text-sm font-black text-teal-950">Prijsindicatie</p>
        @if ($priceIndication)
            <p class="mt-1 text-2xl font-black text-brand-ink">
                @if ($priceIndication['min_cents'] === $priceIndication['max_cents'])
                    {{ $this->formatMoney($priceIndication['min_cents']) }}
                @else
                    {{ $this->formatMoney($priceIndication['min_cents']) }} - {{ $this->formatMoney($priceIndication['max_cents']) }}
                @endif
            </p>
            <p class="mt-2 text-sm text-slate-700">Gebaseerd op gekozen skill, duur, regio, reistijd, weekend/feestdag en aantal kinderen. Definitieve prijs volgt na beoordeling door de entertainer.</p>
        @else
            <p class="mt-1 text-sm text-slate-700">Vul skill, datum en tijden in om automatisch een indicatie te tonen.</p>
        @endif
    </div>
    @if ($errors->any())
        <div class="rounded-md bg-red-50 p-4 text-sm text-red-800 md:col-span-2">
            Controleer de gemarkeerde velden en probeer opnieuw.
        </div>
    @endif
    <button class="brand-button md:col-span-2">Aanvraag verzenden</button>
</form>
