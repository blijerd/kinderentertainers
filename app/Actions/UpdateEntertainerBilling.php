<?php

namespace App\Actions;

use App\Models\Entertainer;

class UpdateEntertainerBilling
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function handle(Entertainer $entertainer, array $data): Entertainer
    {
        $entertainer->update($data);

        return $entertainer->refresh();
    }
}
