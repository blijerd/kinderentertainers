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
    public string $sort = 'recommended';
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
        'sort' => ['except' => 'recommended'],
        'availableOnly' => ['except' => false],
    ];

    public function updated($property): void
    {
        if (in_array($property, ['skill', 'region', 'date', 'startTime', 'endTime', 'age', 'eventType', 'maxPrice', 'maxRadius', 'minRating', 'language', 'sort', 'availableOnly'], true)) {
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
            $entertainers = $this->applySort($query)->get();

            if ($this->age !== '') {
                $entertainers = $entertainers->filter(fn (Entertainer $entertainer): bool => $this->matchesAge($entertainer));
            }

            if ($this->availableOnly && $this->date && $this->startTime && $this->endTime) {
                $checker = app(CheckEntertainerAvailability::class);
                $entertainers = $entertainers->filter(fn (Entertainer $entertainer): bool => $checker->handle($entertainer, $this->date, $this->startTime, $this->endTime));
            }
        } else {
            $entertainers = $this->applySort($query)->paginate(9);
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

    private function applySort($query)
    {
        return match ($this->sort) {
            'rating' => $query->orderByDesc('rating')->orderByDesc('reviews_count')->orderBy('name'),
            'price' => $query
                ->leftJoin('rates as consumer_rates', function ($join): void {
                    $join->on('consumer_rates.entertainer_id', '=', 'entertainers.id')
                        ->where('consumer_rates.customer_type', CustomerType::Consumer->value);
                })
                ->select('entertainers.*')
                ->orderBy('consumer_rates.starting_rate_cents')
                ->orderBy('entertainers.name'),
            'radius' => $query->orderBy('working_radius_km')->orderBy('name'),
            default => $query->orderByDesc('featured')->orderByDesc('rating')->orderBy('name'),
        };
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
    <div class="brand-panel grid gap-4 p-4 md:grid-cols-4 lg:p-5">
        <label class="brand-field">
            <span class="brand-field-label">Skill</span>
            <select wire:model.live="skill" class="brand-input">
                <option value="">Alle skills</option>
                @foreach ($skills as $skillOption)
                    <option value="{{ $skillOption->slug }}">{{ $skillOption->name }}</option>
                @endforeach
            </select>
        </label>
        <label class="brand-field">
            <span class="brand-field-label">Regio</span>
            <select wire:model.live="region" class="brand-input">
                <option value="">Alle regio's</option>
                @foreach ($regions as $regionOption)
                    <option value="{{ $regionOption }}">{{ $regionOption }}</option>
                @endforeach
            </select>
        </label>
        <label class="brand-field">
            <span class="brand-field-label">Leeftijd</span>
            <input wire:model.live.debounce.300ms="age" type="number" min="0" max="18" placeholder="Bijv. 7" class="brand-input">
        </label>
        <label class="brand-field">
            <span class="brand-field-label">Type feest</span>
            <select wire:model.live="eventType" class="brand-input">
                <option value="">Alle types</option>
                @foreach ($eventTypes as $eventTypeOption)
                    <option value="{{ $eventTypeOption }}">{{ $eventTypeOption }}</option>
                @endforeach
            </select>
        </label>
        <label class="brand-field">
            <span class="brand-field-label">Max. prijs</span>
            <input wire:model.live.debounce.300ms="maxPrice" type="number" min="0" step="25" placeholder="EUR" class="brand-input">
        </label>
        <label class="brand-field">
            <span class="brand-field-label">Max. werkgebied</span>
            <input wire:model.live.debounce.300ms="maxRadius" type="number" min="1" max="500" placeholder="Km" class="brand-input">
        </label>
        <label class="brand-field">
            <span class="brand-field-label">Beoordeling</span>
            <select wire:model.live="minRating" class="brand-input">
                <option value="">Alle beoordelingen</option>
                <option value="4">Vanaf 4,0</option>
                <option value="4.5">Vanaf 4,5</option>
                <option value="5">5,0</option>
            </select>
        </label>
        <label class="brand-field">
            <span class="brand-field-label">Taal</span>
            <select wire:model.live="language" class="brand-input">
                <option value="">Alle talen</option>
                @foreach ($languages as $languageOption)
                    <option value="{{ $languageOption }}">{{ $languageOption }}</option>
                @endforeach
            </select>
        </label>
        <label class="brand-field">
            <span class="brand-field-label">Sorteren</span>
            <select wire:model.live="sort" class="brand-input">
                <option value="recommended">Aanbevolen</option>
                <option value="rating">Beste beoordeling</option>
                <option value="price">Laagste startprijs</option>
                <option value="radius">Kleinste werkgebied</option>
            </select>
        </label>
        <label class="brand-field">
            <span class="brand-field-label">Datum</span>
            <input wire:model.live="date" type="date" class="brand-input">
        </label>
        <div class="grid grid-cols-2 gap-3">
            <label class="brand-field">
                <span class="brand-field-label">Start</span>
                <input wire:model.live="startTime" type="time" class="brand-input">
            </label>
            <label class="brand-field">
                <span class="brand-field-label">Einde</span>
                <input wire:model.live="endTime" type="time" class="brand-input">
            </label>
        </div>
        <label class="flex min-h-11 items-center gap-2 self-end rounded-md border border-slate-200 bg-white px-3 py-2 text-sm font-bold text-slate-700 shadow-sm dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200">
            <input wire:model.live="availableOnly" type="checkbox" class="rounded border-slate-300 text-brand-teal">
            Direct beschikbaar
        </label>
        @if ($availableOnly && (! $date || ! $startTime || ! $endTime))
            <p class="rounded-md bg-amber-50 px-3 py-2 text-sm font-medium text-amber-900 md:col-span-4">
                Vul datum, starttijd en eindtijd in om directe beschikbaarheid te filteren.
            </p>
        @endif
    </div>

    <div class="grid gap-5 md:grid-cols-2 lg:grid-cols-3">
        @forelse ($entertainers as $entertainer)
            <article class="group brand-card overflow-hidden transition hover:-translate-y-1 hover:border-brand-teal hover:shadow-lg">
                @if ($entertainer->profilePhotoUrl())
                    <img src="{{ $entertainer->profilePhotoUrl() }}" alt="{{ $entertainer->name }}" class="h-48 w-full object-cover">
                @else
                    <div class="grid h-48 place-items-center bg-brand-peach text-sm font-bold text-brand-ink">{{ $entertainer->name }}</div>
                @endif
                <div class="p-5">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <h2 class="text-xl font-black text-brand-ink group-hover:text-brand-teal dark:text-white">{{ $entertainer->name }}</h2>
                            <p class="mt-1 text-sm text-slate-600 dark:text-slate-300">{{ $entertainer->city }} · {{ $entertainer->region }}</p>
                        </div>
                        @if ($entertainer->featured)
                            <span class="rounded-full bg-amber-100 px-2.5 py-1 text-xs font-bold text-amber-800">Uitgelicht</span>
                        @endif
                    </div>
                    <p class="mt-4 text-sm leading-6 text-slate-700 dark:text-slate-300">{{ $entertainer->short_introduction }}</p>
                    <div class="mt-4 flex flex-wrap gap-2">
                        @foreach ($entertainer->skills as $skill)
                            <span class="brand-pill">{{ $skill->name }}</span>
                        @endforeach
                    </div>
                    <div class="mt-4 space-y-1 text-sm text-slate-700 dark:text-slate-300">
                        @if ($entertainer->audience_age_range)
                            <p>Leeftijd {{ $entertainer->audience_age_range }}</p>
                        @endif
                        @if ($entertainer->rating)
                            <p>{{ number_format((float) $entertainer->rating, 1, ',', '.') }} beoordeling · {{ $entertainer->reviews_count }} reviews</p>
                        @endif
                    </div>
                    @if ($entertainer->consumerRate)
                        <p class="mt-4 text-sm font-black text-brand-ink dark:text-white">Vanaf EUR {{ number_format($entertainer->consumerRate->starting_rate_cents / 100, 2, ',', '.') }}</p>
                    @endif
                    <div class="mt-5 grid gap-2 sm:grid-cols-2">
                        <a href="{{ route('entertainers.show', $entertainer) }}" class="brand-button-secondary px-3 py-2">Profiel bekijken</a>
                        <a href="{{ route('booking-requests.create', $entertainer) }}" class="brand-button px-3 py-2">Aanvraag doen</a>
                    </div>
                </div>
            </article>
        @empty
            <div class="rounded-lg border border-dashed border-slate-300 bg-white p-8 text-slate-600 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-300 md:col-span-2 lg:col-span-3">
                Geen entertainers gevonden voor deze filters.
            </div>
        @endforelse
    </div>

    @if (method_exists($entertainers, 'links'))
        {{ $entertainers->links() }}
    @endif
</div>
