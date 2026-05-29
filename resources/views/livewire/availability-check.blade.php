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

<div class="brand-card p-5">
    <h2 class="text-lg font-black text-brand-ink dark:text-white">Beschikbaarheid controleren</h2>
    <form wire:submit="check" class="mt-4 grid gap-3">
        <input wire:model="date" type="date" class="brand-input" aria-label="Datum">
        <input wire:model="startTime" type="time" class="brand-input" aria-label="Starttijd">
        <input wire:model="endTime" type="time" class="brand-input" aria-label="Eindtijd">
        <button class="brand-button px-4 py-2">Check</button>
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
