<?php

namespace App\Filament\Resources\Availabilities\Schemas;

use App\Enums\AvailabilityStatus;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TimePicker;
use Filament\Schemas\Schema;

class AvailabilityForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('entertainer_id')->label('Entertainer')->relationship('entertainer', 'name')->searchable()->required(),
                DatePicker::make('date')->label('Datum')->required(),
                TimePicker::make('start_time')->label('Starttijd')->seconds(false)->required(),
                TimePicker::make('end_time')->label('Eindtijd')->seconds(false)->required(),
                Select::make('status')->label('Status')->options(collect(AvailabilityStatus::cases())->mapWithKeys(fn ($status) => [$status->value => $status->label()]))->required(),
                Textarea::make('internal_note')->label('Interne notitie')->columnSpanFull(),
            ]);
    }
}
