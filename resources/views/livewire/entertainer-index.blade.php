<?php

use App\Actions\CheckEntertainerAvailability;
use App\Enums\CustomerType;
use App\Models\Entertainer;
use App\Models\Skill;
use Illuminate\Support\Collection;
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
    public string $age = '';
    public string $eventType = '';
    public string $maxPrice = '';
    public string $maxRadius = '';
    public string $minRating = '';
    public string $language = '';
    public bool $availableOnly = false;

    protected $queryString = [
        'skill' => ['except' => ''],
        'region' => ['except' => ''],
        'date' => ['except' => ''],
        'startTime' => ['except' => ''],
        'endTime' => ['except' => ''],
        'age' => ['except' => ''],
        'eventType' => ['except' => ''],
        'maxPrice' => ['except' => ''],
        'maxRadius' => ['except' => ''],
        'minRating' => ['except' => ''],
        'language' => ['except' => ''],
        'availableOnly' => ['except' => false],
    ];

    public function updated($property): void
    {
        if (in_array($property, ['skill', 'region', 'date', 'startTime', 'endTime', 'age', 'eventType', 'maxPrice', 'maxRadius', 'minRating', 'language', 'availableOnly'], true)) {
            $this->resetPage();
        }
    }

    public function with(): array
    {
        $query = Entertainer::query()
            ->with(['skills', 'consumerRate'])
            ->where('active', true)
            ->when($this->skill, fn ($query) => $query->whereHas('skills', fn ($skillQuery) => $skillQuery->where('slug', $this->skill)))
            ->when($this->region, fn ($query) => $query->where('region', $this->region))
            ->when($this->eventType, fn ($query) => $query->whereJsonContains('event_types', $this->eventType))
            ->when($this->language, fn ($query) => $query->whereJsonContains('languages', $this->language))
            ->when($this->maxRadius !== '', fn ($query) => $query->where('working_radius_km', '<=', (int) $this->maxRadius))
            ->when($this->minRating !== '', fn ($query) => $query->where('rating', '>=', (float) $this->minRating))
            ->when($this->maxPrice !== '', fn ($query) => $query->whereHas('consumerRate', fn ($rateQuery) => $rateQuery
                ->where('customer_type', CustomerType::Consumer->value)
                ->where('starting_rate_cents', '<=', (int) round(((float) str_replace(',', '.', $this->maxPrice)) * 100))
            ));

        if ($this->usesCollectionFilters()) {
            $entertainers = $query->orderByDesc('featured')->orderBy('name')->get();

            if ($this->age !== '') {
                $entertainers = $entertainers->filter(fn (Entertainer $entertainer): bool => $this->matchesAge($entertainer));
            }

            if ($this->availableOnly && $this->date && $this->startTime && $this->endTime) {
                $checker = app(CheckEntertainerAvailability::class);
                $entertainers = $entertainers->filter(fn (Entertainer $entertainer): bool => $checker->handle($entertainer, $this->date, $this->startTime, $this->endTime));
            }
        } else {
            $entertainers = $query->orderByDesc('featured')->orderBy('name')->paginate(9);
        }

        return [
            'entertainers' => $entertainers,
            'skills' => Skill::where('active', true)->orderBy('name')->get(),
            'regions' => Entertainer::where('active', true)->distinct()->orderBy('region')->pluck('region'),
            'eventTypes' => $this->distinctJsonValues('event_types'),
            'languages' => $this->distinctJsonValues('languages'),
        ];
    }

    private function usesCollectionFilters(): bool
    {
        return $this->age !== '' || ($this->availableOnly && $this->date && $this->startTime && $this->endTime);
    }

    private function matchesAge(Entertainer $entertainer): bool
    {
        preg_match_all('/\d+/', (string) $entertainer->audience_age_range, $matches);
        $numbers = array_map('intval', $matches[0] ?? []);

        if (count($numbers) < 2) {
            return false;
        }

        $age = (int) $this->age;

        return $age >= min($numbers) && $age <= max($numbers);
    }

    private function distinctJsonValues(string $column): Collection
    {
        return Entertainer::where('active', true)
            ->get([$column])
            ->pluck($column)
            ->flatten()
            ->filter()
            ->unique()
            ->sort()
            ->values();
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
            <span class="text-sm font-medium text-slate-700">Leeftijd</span>
            <input wire:model.live.debounce.300ms="age" type="number" min="0" max="18" placeholder="Bijv. 7" class="w-full rounded-md border-slate-300 text-sm">
        </label>
        <label class="space-y-1">
            <span class="text-sm font-medium text-slate-700">Type feest</span>
            <select wire:model.live="eventType" class="w-full rounded-md border-slate-300 text-sm">
                <option value="">Alle types</option>
                @foreach ($eventTypes as $eventTypeOption)
                    <option value="{{ $eventTypeOption }}">{{ $eventTypeOption }}</option>
                @endforeach
            </select>
        </label>
        <label class="space-y-1">
            <span class="text-sm font-medium text-slate-700">Max. prijs</span>
            <input wire:model.live.debounce.300ms="maxPrice" type="number" min="0" step="25" placeholder="EUR" class="w-full rounded-md border-slate-300 text-sm">
        </label>
        <label class="space-y-1">
            <span class="text-sm font-medium text-slate-700">Max. werkgebied</span>
            <input wire:model.live.debounce.300ms="maxRadius" type="number" min="1" max="500" placeholder="Km" class="w-full rounded-md border-slate-300 text-sm">
        </label>
        <label class="space-y-1">
            <span class="text-sm font-medium text-slate-700">Beoordeling</span>
            <select wire:model.live="minRating" class="w-full rounded-md border-slate-300 text-sm">
                <option value="">Alle beoordelingen</option>
                <option value="4">Vanaf 4,0</option>
                <option value="4.5">Vanaf 4,5</option>
                <option value="5">5,0</option>
            </select>
        </label>
        <label class="space-y-1">
            <span class="text-sm font-medium text-slate-700">Taal</span>
            <select wire:model.live="language" class="w-full rounded-md border-slate-300 text-sm">
                <option value="">Alle talen</option>
                @foreach ($languages as $languageOption)
                    <option value="{{ $languageOption }}">{{ $languageOption }}</option>
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
        <label class="flex items-center gap-2 self-end rounded-md border border-slate-200 bg-white px-3 py-2 text-sm font-medium text-slate-700">
            <input wire:model.live="availableOnly" type="checkbox" class="rounded border-slate-300 text-brand-teal">
            Direct beschikbaar
        </label>
    </div>

    <div class="grid gap-5 md:grid-cols-2 lg:grid-cols-3">
        @forelse ($entertainers as $entertainer)
            <a href="{{ route('entertainers.show', $entertainer) }}" class="group brand-card overflow-hidden transition hover:-translate-y-0.5 hover:border-brand-coral hover:shadow-md">
                @if ($entertainer->profilePhotoUrl())
                    <img src="{{ $entertainer->profilePhotoUrl() }}" alt="{{ $entertainer->name }}" class="h-48 w-full object-cover">
                @else
                    <div class="grid h-48 place-items-center bg-brand-peach text-sm font-bold text-brand-ink">{{ $entertainer->name }}</div>
                @endif
                <div class="p-5">
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
                    <div class="mt-4 space-y-1 text-sm text-slate-700">
                        @if ($entertainer->audience_age_range)
                            <p>Leeftijd {{ $entertainer->audience_age_range }}</p>
                        @endif
                        @if ($entertainer->rating)
                            <p>{{ number_format((float) $entertainer->rating, 1, ',', '.') }} beoordeling · {{ $entertainer->reviews_count }} reviews</p>
                        @endif
                    </div>
                    @if ($entertainer->consumerRate)
                        <p class="mt-4 text-sm font-bold text-brand-ink">Vanaf EUR {{ number_format($entertainer->consumerRate->starting_rate_cents / 100, 2, ',', '.') }}</p>
                    @endif
                </div>
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
