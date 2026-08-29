<?php

namespace App\Actions;

use App\Models\Rate;

class UpdateRate
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function handle(Rate $rate, array $data): Rate
    {
        $rate->update($data);

        return $rate->refresh();
    }
}
