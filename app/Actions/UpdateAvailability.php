<?php

namespace App\Actions;

use App\Models\Availability;

class UpdateAvailability
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function handle(Availability $availability, array $data): Availability
    {
        $availability->update($data);

        return $availability->refresh();
    }
}
