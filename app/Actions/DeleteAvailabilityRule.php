<?php

namespace App\Actions;

use App\Models\AvailabilityRule;

class DeleteAvailabilityRule
{
    public function handle(AvailabilityRule $availabilityRule): void
    {
        $availabilityRule->delete();
    }
}
