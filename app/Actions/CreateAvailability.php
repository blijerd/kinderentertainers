<?php

namespace App\Actions;

use App\Models\Availability;
use App\Models\Entertainer;

class CreateAvailability
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function handle(Entertainer $entertainer, array $data): Availability
    {
        return $entertainer->availabilities()->create($data);
    }
}
