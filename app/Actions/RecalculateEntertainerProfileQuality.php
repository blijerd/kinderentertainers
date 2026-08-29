<?php

namespace App\Actions;

use App\Models\Entertainer;
use App\Services\ProfileQualityService;

class RecalculateEntertainerProfileQuality
{
    public function __construct(private readonly ProfileQualityService $profileQualityService) {}

    public function handle(Entertainer $entertainer): int
    {
        $score = $this->profileQualityService->score($entertainer);

        if ($entertainer->profile_quality_score !== $score) {
            $entertainer->forceFill(['profile_quality_score' => $score])->save();
        }

        return $score;
    }
}
