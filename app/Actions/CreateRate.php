<?php

namespace App\Actions;

use App\Models\Entertainer;
use App\Models\Rate;

class CreateRate
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function handle(Entertainer $entertainer, array $data): Rate
    {
        return $entertainer->rates()->create($data);
    }
}
