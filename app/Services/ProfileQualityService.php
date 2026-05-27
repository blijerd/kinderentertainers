<?php

namespace App\Services;

use App\Models\Entertainer;

class ProfileQualityService
{
    public function score(Entertainer $entertainer): int
    {
        $checks = [
            filled($entertainer->profile_photo_path),
            count($entertainer->gallery_photo_paths ?? []) >= 3,
            filled($entertainer->short_introduction),
            filled($entertainer->bio) && mb_strlen((string) $entertainer->bio) >= 250,
            count($entertainer->profile_highlights ?? []) >= 3,
            filled($entertainer->audience_age_range),
            count($entertainer->event_types ?? []) > 0,
            count($entertainer->languages ?? []) > 0,
            filled($entertainer->show_reel_url),
            $entertainer->rates()->exists(),
            $entertainer->availabilityRules()->exists() || $entertainer->availabilities()->upcoming()->exists(),
            count($entertainer->packages ?? []) > 0,
        ];

        return (int) round((collect($checks)->filter()->count() / count($checks)) * 100);
    }

    /**
     * @return list<string>
     */
    public function missingItems(Entertainer $entertainer): array
    {
        $items = [];

        if (! $entertainer->profile_photo_path) {
            $items[] = 'Profielfoto';
        }
        if (count($entertainer->gallery_photo_paths ?? []) < 3) {
            $items[] = 'Minimaal 3 galerijfoto\'s';
        }
        if (! filled($entertainer->bio) || mb_strlen((string) $entertainer->bio) < 250) {
            $items[] = 'Uitgebreide bio';
        }
        if (! filled($entertainer->show_reel_url)) {
            $items[] = 'Showreel';
        }
        if (! $entertainer->rates()->exists()) {
            $items[] = 'Tarieven';
        }
        if (! $entertainer->availabilityRules()->exists() && ! $entertainer->availabilities()->upcoming()->exists()) {
            $items[] = 'Beschikbaarheid';
        }
        if (count($entertainer->packages ?? []) === 0) {
            $items[] = 'Pakketten';
        }

        return $items;
    }
}
