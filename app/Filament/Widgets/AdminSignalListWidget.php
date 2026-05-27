<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\BookingRequests\BookingRequestResource;
use App\Filament\Resources\Entertainers\EntertainerResource;
use App\Filament\Resources\Rates\RateResource;
use App\Filament\Resources\Skills\SkillResource;
use App\Services\AdminDashboardSignalService;
use Filament\Widgets\Widget;

class AdminSignalListWidget extends Widget
{
    protected static ?int $sort = -1;

    protected static bool $isLazy = false;

    protected int | string | array $columnSpan = 'full';

    protected string $view = 'filament.widgets.admin-signal-list-widget';

    /**
     * @return array<string, mixed>
     */
    protected function getViewData(): array
    {
        $signals = app(AdminDashboardSignalService::class);

        return [
            'signals' => [
                [
                    'label' => 'Nieuwe aanvragen zonder match',
                    'count' => $signals->newRequestsWithoutMatchesCount(),
                    'description' => 'Aanvragen met status Nieuw waar nog geen matchregel aan hangt.',
                    'color' => 'danger',
                    'url' => BookingRequestResource::getUrl('index'),
                    'action' => 'Aanvragen openen',
                ],
                [
                    'label' => 'Offertes verlopen binnenkort',
                    'count' => $signals->expiringQuoteOptionsCount(),
                    'description' => 'Opties zonder acceptatie waarvan het evenement binnen 14 dagen plaatsvindt.',
                    'color' => 'warning',
                    'url' => BookingRequestResource::getUrl('index'),
                    'action' => 'Opties bekijken',
                ],
                [
                    'label' => 'Entertainers zonder tarieven',
                    'count' => $signals->entertainersWithoutRatesCount(),
                    'description' => 'Actieve entertainers die nog geen tariefregels hebben.',
                    'color' => 'warning',
                    'url' => RateResource::getUrl('index'),
                    'action' => 'Tarieven beheren',
                ],
                [
                    'label' => 'Profielen incompleet',
                    'count' => $signals->incompleteProfilesCount(),
                    'description' => 'Actieve profielen zonder foto, bio, leeftijdsrange, duur of complete-status.',
                    'color' => 'gray',
                    'url' => EntertainerResource::getUrl('index'),
                    'action' => 'Profielen nalopen',
                ],
            ],
            'underSuppliedSkills' => $signals->underSuppliedPopularSkills(),
            'skillsUrl' => SkillResource::getUrl('index'),
        ];
    }
}
