<?php

use App\Models\Entertainer;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Component;

new class extends Component
{
    public ?Entertainer $entertainer = null;

    /** @var Collection<int, \App\Models\Skill> */
    public Collection $skills;
};
?>

<form method="POST" action="{{ $entertainer ? route('booking-requests.store', $entertainer) : route('booking-requests.general.store') }}" class="grid gap-5 rounded-lg border border-slate-200 bg-white p-5 shadow-sm md:grid-cols-2">
    @csrf
    <fieldset class="space-y-3 md:col-span-2">
        <legend class="text-sm font-medium">Aanvraagtype</legend>
        <div class="grid gap-3 md:grid-cols-2">
            <label class="rounded-md border border-slate-200 p-4 text-sm">
                <input type="radio" name="request_type" value="specific" @checked(old('request_type', $entertainer ? 'specific' : 'general') === 'specific') @disabled(! $entertainer) class="mr-2 border-slate-300 text-teal-700">
                Ik wil een specifieke entertainer aanvragen
            </label>
            <label class="rounded-md border border-slate-200 p-4 text-sm">
                <input type="radio" name="request_type" value="general" @checked(old('request_type', $entertainer ? 'specific' : 'general') === 'general') class="mr-2 border-slate-300 text-teal-700">
                Zoek beschikbare entertainers voor deze skill
            </label>
        </div>
        <p class="rounded-md bg-teal-50 p-3 text-sm text-teal-900">
            @if ($entertainer)
                Je vraagt beschikbaarheid aan bij deze specifieke entertainer.
            @else
                We zoeken beschikbare entertainers voor je aanvraag.
            @endif
        </p>
        @error('request_type') <p class="text-sm text-red-700">{{ $message }}</p> @enderror
    </fieldset>
    <label class="space-y-1 md:col-span-2">
        <span class="text-sm font-medium">Skill</span>
        <select name="skill_id" class="w-full rounded-md border-slate-300 text-sm">
            <option value="">Kies een skill</option>
            @foreach ($skills as $skill)
                <option value="{{ $skill->id }}" @selected((string) old('skill_id') === (string) $skill->id || (! old('skill_id') && $entertainer?->skills->contains($skill)))>{{ $skill->name }}</option>
            @endforeach
        </select>
        @error('skill_id') <span class="text-sm text-red-700">{{ $message }}</span> @enderror
    </label>
    <label class="space-y-1">
        <span class="text-sm font-medium">Klanttype</span>
        <select name="customer_type" class="w-full rounded-md border-slate-300 text-sm">
            <option value="consument" @selected(old('customer_type') === 'consument')>Consument</option>
            <option value="b2b" @selected(old('customer_type') === 'b2b')>Zakelijk</option>
        </select>
        @error('customer_type') <span class="text-sm text-red-700">{{ $message }}</span> @enderror
    </label>
    <label class="space-y-1">
        <span class="text-sm font-medium">Naam</span>
        <input name="name" value="{{ old('name') }}" required class="w-full rounded-md border-slate-300 text-sm">
        @error('name') <span class="text-sm text-red-700">{{ $message }}</span> @enderror
    </label>
    <label class="space-y-1">
        <span class="text-sm font-medium">Bedrijfsnaam</span>
        <input name="company_name" value="{{ old('company_name') }}" class="w-full rounded-md border-slate-300 text-sm">
        @error('company_name') <span class="text-sm text-red-700">{{ $message }}</span> @enderror
    </label>
    <label class="space-y-1">
        <span class="text-sm font-medium">E-mail</span>
        <input name="email" type="email" value="{{ old('email') }}" required class="w-full rounded-md border-slate-300 text-sm">
        @error('email') <span class="text-sm text-red-700">{{ $message }}</span> @enderror
    </label>
    <label class="space-y-1">
        <span class="text-sm font-medium">Telefoon</span>
        <input name="phone" value="{{ old('phone') }}" required class="w-full rounded-md border-slate-300 text-sm">
        @error('phone') <span class="text-sm text-red-700">{{ $message }}</span> @enderror
    </label>
    <label class="space-y-1">
        <span class="text-sm font-medium">Evenementdatum</span>
        <input name="event_date" type="date" value="{{ old('event_date') }}" required class="w-full rounded-md border-slate-300 text-sm">
        @error('event_date') <span class="text-sm text-red-700">{{ $message }}</span> @enderror
    </label>
    <label class="space-y-1">
        <span class="text-sm font-medium">Starttijd</span>
        <input name="start_time" type="time" value="{{ old('start_time') }}" required class="w-full rounded-md border-slate-300 text-sm">
        @error('start_time') <span class="text-sm text-red-700">{{ $message }}</span> @enderror
    </label>
    <label class="space-y-1">
        <span class="text-sm font-medium">Eindtijd</span>
        <input name="end_time" type="time" value="{{ old('end_time') }}" required class="w-full rounded-md border-slate-300 text-sm">
        @error('end_time') <span class="text-sm text-red-700">{{ $message }}</span> @enderror
    </label>
    <label class="space-y-1 md:col-span-2">
        <span class="text-sm font-medium">Adres</span>
        <input name="address" value="{{ old('address') }}" required class="w-full rounded-md border-slate-300 text-sm">
        @error('address') <span class="text-sm text-red-700">{{ $message }}</span> @enderror
    </label>
    <label class="space-y-1">
        <span class="text-sm font-medium">Postcode</span>
        <input name="postal_code" value="{{ old('postal_code') }}" required class="w-full rounded-md border-slate-300 text-sm">
        @error('postal_code') <span class="text-sm text-red-700">{{ $message }}</span> @enderror
    </label>
    <label class="space-y-1">
        <span class="text-sm font-medium">Plaats</span>
        <input name="city" value="{{ old('city') }}" required class="w-full rounded-md border-slate-300 text-sm">
        @error('city') <span class="text-sm text-red-700">{{ $message }}</span> @enderror
    </label>
    <label class="space-y-1">
        <span class="text-sm font-medium">Aantal kinderen</span>
        <input name="children_count" type="number" min="0" value="{{ old('children_count') }}" class="w-full rounded-md border-slate-300 text-sm">
        @error('children_count') <span class="text-sm text-red-700">{{ $message }}</span> @enderror
    </label>
    <label class="space-y-1">
        <span class="text-sm font-medium">Leeftijd kinderen</span>
        <input name="children_ages" value="{{ old('children_ages') }}" class="w-full rounded-md border-slate-300 text-sm">
        @error('children_ages') <span class="text-sm text-red-700">{{ $message }}</span> @enderror
    </label>
    <fieldset class="space-y-2 md:col-span-2">
        <legend class="text-sm font-medium">Gewenste skills</legend>
        <div class="flex flex-wrap gap-2">
            @foreach ($skills as $skill)
                <label class="rounded-full border border-slate-200 px-3 py-1.5 text-sm">
                    <input type="checkbox" name="desired_skills[]" value="{{ $skill->name }}" @checked(in_array($skill->name, old('desired_skills', []), true)) class="mr-1 rounded border-slate-300">
                    {{ $skill->name }}
                </label>
            @endforeach
        </div>
        @error('desired_skills') <p class="text-sm text-red-700">{{ $message }}</p> @enderror
    </fieldset>
    <label class="space-y-1 md:col-span-2">
        <span class="text-sm font-medium">Bericht</span>
        <textarea name="message" rows="5" class="w-full rounded-md border-slate-300 text-sm">{{ old('message') }}</textarea>
        @error('message') <span class="text-sm text-red-700">{{ $message }}</span> @enderror
    </label>
    @if ($errors->any())
        <div class="rounded-md bg-red-50 p-4 text-sm text-red-800 md:col-span-2">
            Controleer de gemarkeerde velden en probeer opnieuw.
        </div>
    @endif
    <button class="rounded-md bg-teal-700 px-5 py-3 text-sm font-semibold text-white hover:bg-teal-800 md:col-span-2">Aanvraag verzenden</button>
</form>
