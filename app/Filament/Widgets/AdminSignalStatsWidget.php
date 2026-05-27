<?php

namespace App\Filament\Widgets;

use App\Services\AdminDashboardSignalService;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class AdminSignalStatsWidget extends StatsOverviewWidget
{
    protected static ?int $sort = -2;

    protected static bool $isLazy = false;

    protected ?string $heading = 'Signalen';

    protected ?string $description = 'Operationele aandachtspunten voor aanvragen, aanbod en profielen.';

    /**
     * @return array<Stat>
     */
    protected function getStats(): array
    {
        $signals = app(AdminDashboardSignalService::class);

        return [
            Stat::make('Nieuwe aanvragen zonder match', $signals->newRequestsWithoutMatchesCount())
                ->description('Nog geen entertainer gekoppeld')
                ->descriptionColor('danger')
                ->icon(Heroicon::OutlinedSignalSlash)
                ->color('danger'),
            Stat::make('Offertes verlopen binnenkort', $signals->expiringQuoteOptionsCount())
                ->description('Opties met evenement binnen 14 dagen')
                ->descriptionColor('warning')
                ->icon(Heroicon::OutlinedClock)
                ->color('warning'),
            Stat::make('Entertainers zonder tarieven', $signals->entertainersWithoutRatesCount())
                ->description('Actief, maar geen tariefregels')
                ->descriptionColor('warning')
                ->icon(Heroicon::OutlinedCurrencyEuro)
                ->color('warning'),
            Stat::make('Profielen incompleet', $signals->incompleteProfilesCount())
                ->description('Actieve profielen missen kerninformatie')
                ->descriptionColor('gray')
                ->icon(Heroicon::OutlinedUserCircle)
                ->color('gray'),
            Stat::make('Populaire skills met aanbodtekort', $signals->underSuppliedPopularSkillsCount())
                ->description('Vraag groter dan beschikbaar aanbod')
                ->descriptionColor('info')
                ->icon(Heroicon::OutlinedTag)
                ->color('info'),
        ];
    }
}
