<?php

namespace App\Actions;

use App\Models\EntertainerIntegration;
use App\Services\IntegrationHealthService;

class UpdateEntertainerIntegration
{
    public function __construct(private readonly IntegrationHealthService $health) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function handle(EntertainerIntegration $integration, array $data): EntertainerIntegration
    {
        $integration->update($data);
        $check = $this->health->check($integration->refresh());
        $integration->update([
            'last_checked_at' => now(),
            'last_check_status' => $check['status'],
            'last_check_message' => $check['message'],
        ]);

        return $integration->refresh();
    }
}
