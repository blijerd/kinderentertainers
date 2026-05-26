<?php

namespace App\Actions;

use App\Models\Entertainer;
use App\Models\Skill;
use Illuminate\Database\Eloquent\Collection;

class FindAvailableEntertainersForRequest
{
    public function __construct(private readonly CheckEntertainerAvailability $availability)
    {
    }

    /**
     * @return Collection<int, Entertainer>
     */
    public function handle(Skill $skill, string $date, string $startTime, string $endTime, ?string $region = null): Collection
    {
        return Entertainer::query()
            ->with('skills')
            ->where('active', true)
            ->whereHas('skills', fn ($query) => $query
                ->whereKey($skill->id)
                ->where('skills.active', true))
            ->when($region, fn ($query) => $query->where('region', $region))
            ->orderByDesc('featured')
            ->orderBy('name')
            ->get()
            ->filter(fn (Entertainer $entertainer) => $this->availability->handle($entertainer, $date, $startTime, $endTime))
            ->values();
    }
}
