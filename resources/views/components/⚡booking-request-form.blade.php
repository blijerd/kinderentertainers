<?php

use App\Models\Entertainer;
use Livewire\Component;

new class extends Component
{
    public Entertainer $entertainer;
};
?>

<form method="POST" action="{{ route('booking-requests.store', $entertainer) }}" class="grid gap-5 rounded-lg border border-slate-200 bg-white p-5 shadow-sm md:grid-cols-2">
    @csrf
    <label class="space-y-1">
        <span class="text-sm font-medium">Klanttype</span>
        <select name="customer_type" class="w-full rounded-md border-slate-300 text-sm">
            <option value="consument">Consument</option>
            <option value="b2b">Zakelijk</option>
        </select>
    </label>
    <label class="space-y-1">
        <span class="text-sm font-medium">Naam</span>
        <input name="name" value="{{ old('name') }}" required class="w-full rounded-md border-slate-300 text-sm">
    </label>
    <label class="space-y-1">
        <span class="text-sm font-medium">Bedrijfsnaam</span>
        <input name="company_name" value="{{ old('company_name') }}" class="w-full rounded-md border-slate-300 text-sm">
    </label>
    <label class="space-y-1">
        <span class="text-sm font-medium">E-mail</span>
        <input name="email" type="email" value="{{ old('email') }}" required class="w-full rounded-md border-slate-300 text-sm">
    </label>
    <label class="space-y-1">
        <span class="text-sm font-medium">Telefoon</span>
        <input name="phone" value="{{ old('phone') }}" required class="w-full rounded-md border-slate-300 text-sm">
    </label>
    <label class="space-y-1">
        <span class="text-sm font-medium">Evenementdatum</span>
        <input name="event_date" type="date" value="{{ old('event_date') }}" required class="w-full rounded-md border-slate-300 text-sm">
    </label>
    <label class="space-y-1">
        <span class="text-sm font-medium">Starttijd</span>
        <input name="start_time" type="time" value="{{ old('start_time') }}" required class="w-full rounded-md border-slate-300 text-sm">
    </label>
    <label class="space-y-1">
        <span class="text-sm font-medium">Eindtijd</span>
        <input name="end_time" type="time" value="{{ old('end_time') }}" required class="w-full rounded-md border-slate-300 text-sm">
    </label>
    <label class="space-y-1 md:col-span-2">
        <span class="text-sm font-medium">Adres</span>
        <input name="address" value="{{ old('address') }}" required class="w-full rounded-md border-slate-300 text-sm">
    </label>
    <label class="space-y-1">
        <span class="text-sm font-medium">Postcode</span>
        <input name="postal_code" value="{{ old('postal_code') }}" required class="w-full rounded-md border-slate-300 text-sm">
    </label>
    <label class="space-y-1">
        <span class="text-sm font-medium">Plaats</span>
        <input name="city" value="{{ old('city') }}" required class="w-full rounded-md border-slate-300 text-sm">
    </label>
    <label class="space-y-1">
        <span class="text-sm font-medium">Aantal kinderen</span>
        <input name="children_count" type="number" min="0" value="{{ old('children_count') }}" class="w-full rounded-md border-slate-300 text-sm">
    </label>
    <label class="space-y-1">
        <span class="text-sm font-medium">Leeftijd kinderen</span>
        <input name="children_ages" value="{{ old('children_ages') }}" class="w-full rounded-md border-slate-300 text-sm">
    </label>
    <fieldset class="space-y-2 md:col-span-2">
        <legend class="text-sm font-medium">Gewenste skills</legend>
        <div class="flex flex-wrap gap-2">
            @foreach ($entertainer->skills as $skill)
                <label class="rounded-full border border-slate-200 px-3 py-1.5 text-sm">
                    <input type="checkbox" name="desired_skills[]" value="{{ $skill->name }}" class="mr-1 rounded border-slate-300">
                    {{ $skill->name }}
                </label>
            @endforeach
        </div>
    </fieldset>
    <label class="space-y-1 md:col-span-2">
        <span class="text-sm font-medium">Bericht</span>
        <textarea name="message" rows="5" class="w-full rounded-md border-slate-300 text-sm">{{ old('message') }}</textarea>
    </label>
    @if ($errors->any())
        <div class="rounded-md bg-red-50 p-4 text-sm text-red-800 md:col-span-2">
            Controleer de gemarkeerde velden en probeer opnieuw.
        </div>
    @endif
    <button class="rounded-md bg-teal-700 px-5 py-3 text-sm font-semibold text-white hover:bg-teal-800 md:col-span-2">Aanvraag verzenden</button>
</form>
