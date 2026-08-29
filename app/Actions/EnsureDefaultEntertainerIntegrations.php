<?php

namespace App\Actions;

use App\Enums\IntegrationProvider;
use App\Models\Entertainer;

class EnsureDefaultEntertainerIntegrations
{
    public function handle(Entertainer $entertainer): void
    {
        foreach (IntegrationProvider::cases() as $provider) {
            $entertainer->integrations()->firstOrCreate(['provider' => $provider]);
        }
    }
}
