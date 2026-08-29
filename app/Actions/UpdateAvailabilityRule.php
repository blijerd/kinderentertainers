<?php

namespace App\Actions;

use App\Models\AvailabilityRule;

class UpdateAvailabilityRule
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function handle(AvailabilityRule $availabilityRule, array $data): AvailabilityRule
    {
        $availabilityRule->update($data);

        return $availabilityRule->refresh();
    }
}
