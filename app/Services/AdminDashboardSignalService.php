<?php

namespace App\Services;

use App\Enums\BookingStatus;
use App\Models\BookingRequest;
use App\Models\Entertainer;
use App\Models\Review;
use App\Models\Skill;
use Illuminate\Support\Collection;

class AdminDashboardSignalService
{
    public function newRequestsWithoutMatchesCount(): int
    {
        return BookingRequest::query()
            ->where('status', BookingStatus::New->value)
            ->whereDoesntHave('matches')
            ->count();
    }

    public function expiringQuoteOptionsCount(): int
    {
        return BookingRequest::query()
            ->where('status', BookingStatus::Option->value)
            ->whereNull('quote_accepted_at')
            ->whereBetween('event_date', [today(), today()->addDays(14)])
            ->count();
    }

    public function entertainersWithoutRatesCount(): int
    {
        return Entertainer::query()
            ->where('active', true)
            ->whereDoesntHave('rates')
            ->count();
    }

    public function incompleteProfilesCount(): int
    {
        return Entertainer::query()
            ->where('active', true)
            ->where(function ($query): void {
                $query
                    ->where('profile_complete', false)
                    ->orWhereNull('profile_photo_path')
                    ->orWhereNull('bio')
                    ->orWhereNull('audience_age_range')
                    ->orWhereNull('performance_duration_minutes');
            })
            ->count();
    }

    public function pendingReviewsCount(): int
    {
        return Review::query()
            ->where('status', 'pending')
            ->whereNotNull('submitted_at')
            ->count();
    }

    public function failedCalendarSyncsCount(): int
    {
        return BookingRequest::query()
            ->whereNotNull('calendar_sync_status')
            ->where('calendar_sync_status', 'like', 'sync_failed%')
            ->count();
    }

    public function overdueDepositsCount(): int
    {
        return BookingRequest::query()
            ->where('status', BookingStatus::Confirmed->value)
            ->where('payment_status', 'deposit_due')
            ->whereNotNull('payment_due_at')
            ->where('payment_due_at', '<', now())
            ->count();
    }

    public function underSuppliedPopularSkillsCount(): int
    {
        return $this->underSuppliedPopularSkills()->count();
    }

    /**
     * @return Collection<int, Skill>
     */
    public function underSuppliedPopularSkills(int $limit = 5): Collection
    {
        return Skill::query()
            ->where('active', true)
            ->withCount([
                'bookingRequests as demand_count' => fn ($query) => $query
                    ->whereIn('status', [
                        BookingStatus::New->value,
                        BookingStatus::InProgress->value,
                        BookingStatus::Option->value,
                    ])
                    ->whereDate('event_date', '>=', today()->subDays(30))
                    ->whereDate('event_date', '<=', today()->addDays(90)),
                'entertainers as active_entertainers_count' => fn ($query) => $query
                    ->where('active', true),
            ])
            ->get()
            ->filter(fn (Skill $skill): bool => $skill->demand_count >= 2 && $skill->active_entertainers_count < 2)
            ->sortByDesc('demand_count')
            ->take($limit)
            ->values();
    }
}
