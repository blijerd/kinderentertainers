<?php

use App\Actions\CheckEntertainerAvailability;
use App\Models\Entertainer;
use App\Models\Skill;
use Livewire\Component;
use Livewire\WithPagination;

new class extends Component
{
    use WithPagination;

    public string $skill = '';
    public string $region = '';
    public string $date = '';
    public string $startTime = '';
    public string $endTime = '';

    protected $queryString = [
        'skill' => ['except' => ''],
        'region' => ['except' => ''],
        'date' => ['except' => ''],
        'startTime' => ['except' => ''],
        'endTime' => ['except' => ''],
    ];

    public function updated($property): void
    {
        if (in_array($property, ['skill', 'region', 'date', 'startTime', 'endTime'], true)) {
            $this->resetPage();
        }
    }

    public function with(): array
    {
        $query = Entertainer::query()
            ->with(['skills', 'consumerRate'])
            ->where('active', true)
            ->when($this->skill, fn ($query) => $query->whereHas('skills', fn ($skillQuery) => $skillQuery->where('slug', $this->skill)))
            ->when($this->region, fn ($query) => $query->where('region', $this->region));

        if ($this->date && $this->startTime && $this->endTime) {
            $checker = app(CheckEntertainerAvailability::class);
            $entertainers = $query->get()->filter(fn (Entertainer $entertainer) => $checker->handle($entertainer, $this->date, $this->startTime, $this->endTime));
        } else {
            $entertainers = $query->orderByDesc('featured')->orderBy('name')->paginate(9);
        }

        return [
            'entertainers' => $entertainers,
            'skills' => Skill::where('active', true)->orderBy('name')->get(),
            'regions' => Entertainer::where('active', true)->distinct()->orderBy('region')->pluck('region'),
        ];
    }
};
?>

<div class="space-y-8">
    <div class="brand-panel grid gap-4 p-4 md:grid-cols-4">
        <label class="space-y-1">
            <span class="text-sm font-medium text-slate-700">Skill</span>
            <select wire:model.live="skill" class="w-full rounded-md border-slate-300 text-sm">
                <option value="">Alle skills</option>
                @foreach ($skills as $skillOption)
                    <option value="{{ $skillOption->slug }}">{{ $skillOption->name }}</option>
                @endforeach
            </select>
        </label>
        <label class="space-y-1">
            <span class="text-sm font-medium text-slate-700">Regio</span>
            <select wire:model.live="region" class="w-full rounded-md border-slate-300 text-sm">
                <option value="">Alle regio's</option>
                @foreach ($regions as $regionOption)
                    <option value="{{ $regionOption }}">{{ $regionOption }}</option>
                @endforeach
            </select>
        </label>
        <label class="space-y-1">
            <span class="text-sm font-medium text-slate-700">Datum</span>
            <input wire:model.live="date" type="date" class="w-full rounded-md border-slate-300 text-sm">
        </label>
        <div class="grid grid-cols-2 gap-3">
            <label class="space-y-1">
                <span class="text-sm font-medium text-slate-700">Start</span>
                <input wire:model.live="startTime" type="time" class="w-full rounded-md border-slate-300 text-sm">
            </label>
            <label class="space-y-1">
                <span class="text-sm font-medium text-slate-700">Einde</span>
                <input wire:model.live="endTime" type="time" class="w-full rounded-md border-slate-300 text-sm">
            </label>
        </div>
    </div>

    <div class="grid gap-5 md:grid-cols-2 lg:grid-cols-3">
        @forelse ($entertainers as $entertainer)
            <a href="{{ route('entertainers.show', $entertainer) }}" class="group brand-card p-5 transition hover:-translate-y-0.5 hover:border-brand-coral hover:shadow-md">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <h2 class="text-xl font-bold text-brand-ink group-hover:text-brand-coral">{{ $entertainer->name }}</h2>
                        <p class="mt-1 text-sm text-slate-600">{{ $entertainer->city }} · {{ $entertainer->region }}</p>
                    </div>
                    @if ($entertainer->featured)
                        <span class="rounded-full bg-amber-100 px-2.5 py-1 text-xs font-bold text-amber-800">Uitgelicht</span>
                    @endif
                </div>
                <p class="mt-4 text-sm leading-6 text-slate-700">{{ $entertainer->short_introduction }}</p>
                <div class="mt-4 flex flex-wrap gap-2">
                    @foreach ($entertainer->skills as $skill)
                        <span class="brand-pill">{{ $skill->name }}</span>
                    @endforeach
                </div>
                @if ($entertainer->consumerRate)
                    <p class="mt-4 text-sm font-bold text-brand-ink">Vanaf EUR {{ number_format($entertainer->consumerRate->starting_rate_cents / 100, 2, ',', '.') }}</p>
                @endif
            </a>
        @empty
            <div class="rounded-lg border border-dashed border-slate-300 bg-white p-8 text-slate-600 md:col-span-2 lg:col-span-3">
                Geen entertainers gevonden voor deze filters.
            </div>
        @endforelse
    </div>

    @if (method_exists($entertainers, 'links'))
        {{ $entertainers->links() }}
    @endif
</div>
