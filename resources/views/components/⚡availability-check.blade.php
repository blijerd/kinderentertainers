<?php

use App\Actions\CheckEntertainerAvailability;
use App\Models\Entertainer;
use Livewire\Component;

new class extends Component
{
    public Entertainer $entertainer;
    public string $date = '';
    public string $startTime = '';
    public string $endTime = '';
    public ?bool $available = null;

    public function check(CheckEntertainerAvailability $checker): void
    {
        $this->validate([
            'date' => ['required', 'date', 'after_or_equal:today'],
            'startTime' => ['required', 'date_format:H:i'],
            'endTime' => ['required', 'date_format:H:i', 'after:startTime'],
        ], attributes: [
            'date' => 'datum',
            'startTime' => 'starttijd',
            'endTime' => 'eindtijd',
        ]);

        $this->available = $checker->handle($this->entertainer, $this->date, $this->startTime, $this->endTime);
    }
};
?>

<div class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
    <h2 class="text-lg font-semibold text-slate-950">Beschikbaarheid controleren</h2>
    <form wire:submit="check" class="mt-4 grid gap-3 md:grid-cols-4">
        <input wire:model="date" type="date" class="rounded-md border-slate-300 text-sm" aria-label="Datum">
        <input wire:model="startTime" type="time" class="rounded-md border-slate-300 text-sm" aria-label="Starttijd">
        <input wire:model="endTime" type="time" class="rounded-md border-slate-300 text-sm" aria-label="Eindtijd">
        <button class="rounded-md bg-teal-700 px-4 py-2 text-sm font-semibold text-white hover:bg-teal-800">Check</button>
    </form>
    @error('date') <p class="mt-2 text-sm text-red-700">{{ $message }}</p> @enderror
    @error('startTime') <p class="mt-2 text-sm text-red-700">{{ $message }}</p> @enderror
    @error('endTime') <p class="mt-2 text-sm text-red-700">{{ $message }}</p> @enderror

    @if ($available === true)
        <p class="mt-4 rounded-md bg-green-50 px-4 py-3 text-sm font-medium text-green-800">Deze entertainer lijkt beschikbaar op dit moment.</p>
    @elseif ($available === false)
        <p class="mt-4 rounded-md bg-amber-50 px-4 py-3 text-sm font-medium text-amber-900">Geen passend beschikbaarheidsblok gevonden. Je kunt alsnog een aanvraag sturen.</p>
    @endif
</div>
