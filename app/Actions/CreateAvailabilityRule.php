<?php

namespace App\Actions;

use App\Models\AvailabilityRule;
use App\Models\Entertainer;

class CreateAvailabilityRule
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function handle(Entertainer $entertainer, array $data): AvailabilityRule
    {
        return $entertainer->availabilityRules()->create($data);
    }
}
