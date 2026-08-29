<?php

namespace App\Actions;

use App\Models\Availability;

class DeleteAvailability
{
    public function handle(Availability $availability): void
    {
        $availability->delete();
    }
}
