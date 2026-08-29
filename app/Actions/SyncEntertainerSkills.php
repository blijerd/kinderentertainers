<?php

namespace App\Actions;

use App\Models\Entertainer;

class SyncEntertainerSkills
{
    /**
     * @param  list<int>|null  $skillIds
     */
    public function handle(Entertainer $entertainer, ?array $skillIds): Entertainer
    {
        $entertainer->skills()->sync($skillIds ?? []);

        return $entertainer->refresh();
    }
}
