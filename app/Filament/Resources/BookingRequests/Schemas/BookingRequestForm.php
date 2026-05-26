<?php

namespace App\Filament\Resources\BookingRequests\Schemas;

use App\Enums\BookingStatus;
use App\Enums\CustomerType;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Schemas\Schema;

class BookingRequestForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('entertainer_id')->label('Entertainer')->relationship('entertainer', 'name')->searchable()->required(),
            Select::make('customer_type')->label('Klanttype')->options(collect(CustomerType::cases())->mapWithKeys(fn ($type) => [$type->value => $type->label()]))->required(),
            TextInput::make('name')->label('Naam')->required(),
            TextInput::make('company_name')->label('Bedrijfsnaam'),
            TextInput::make('email')->label('E-mail')->email()->required(),
            TextInput::make('phone')->label('Telefoon')->required(),
            DatePicker::make('event_date')->label('Evenementdatum')->required(),
            TimePicker::make('start_time')->label('Starttijd')->seconds(false)->required(),
            TimePicker::make('end_time')->label('Eindtijd')->seconds(false)->required(),
            TextInput::make('address')->label('Adres')->required(),
            TextInput::make('postal_code')->label('Postcode')->required(),
            TextInput::make('city')->label('Plaats')->required(),
            TextInput::make('children_count')->label('Aantal kinderen')->numeric(),
            TextInput::make('children_ages')->label('Leeftijd kinderen'),
            Select::make('status')->label('Status')->options(collect(BookingStatus::cases())->mapWithKeys(fn ($status) => [$status->value => $status->label()]))->required(),
            Textarea::make('message')->label('Bericht')->columnSpanFull(),
            Textarea::make('internal_note')->label('Interne notitie')->columnSpanFull(),
        ]);
    }
}
