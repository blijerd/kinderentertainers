<?php

namespace App\Filament\Resources\Rates\Schemas;

use App\Enums\CustomerType;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class RateForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('entertainer_id')->label('Entertainer')->relationship('entertainer', 'name')->searchable()->required(),
            Select::make('customer_type')->label('Doelgroep')->options(collect(CustomerType::cases())->mapWithKeys(fn ($type) => [$type->value => $type->label()]))->required(),
            TextInput::make('starting_rate_cents')->label('Starttarief in centen')->numeric()->required(),
            TextInput::make('hourly_rate_cents')->label('Uurtarief in centen')->numeric()->required(),
            TextInput::make('minimum_hours')->label('Minimum aantal uren')->numeric()->required(),
            TextInput::make('travel_cost_cents_per_km')->label('Reiskosten per km in centen')->numeric()->required(),
            Toggle::make('vat_included')->label('Btw inclusief')->default(true),
            Textarea::make('remarks')->label('Opmerkingen')->columnSpanFull(),
        ]);
    }
}
