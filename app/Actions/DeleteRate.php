<?php

namespace App\Actions;

use App\Models\Rate;

class DeleteRate
{
    public function handle(Rate $rate): void
    {
        $rate->delete();
    }
}
