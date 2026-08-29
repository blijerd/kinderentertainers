<?php

namespace App\Actions;

use App\Models\Entertainer;
use App\Services\ProfileQualityService;
use Illuminate\Validation\ValidationException;

class RequestEntertainerPublication
{
    public function __construct(private readonly ProfileQualityService $profileQualityService) {}

    public function handle(Entertainer $entertainer): Entertainer
    {
        $score = $this->profileQualityService->score($entertainer);

        if ($score < 70) {
            throw ValidationException::withMessages([
                'publication' => 'Vul je profiel verder aan voordat je publicatie aanvraagt.',
            ]);
        }

        $entertainer->update([
            'profile_complete' => true,
            'profile_quality_score' => $score,
            'publication_requested_at' => now(),
            'publication_reviewed_at' => null,
            'publication_review_note' => null,
        ]);

        return $entertainer->refresh();
    }
}
