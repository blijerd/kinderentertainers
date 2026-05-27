<?php

namespace App\Filament\Resources\Reviews\Schemas;

use App\Enums\ReviewStatus;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class ReviewForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('booking_request_id')->label('Aanvraag')->relationship('bookingRequest', 'name')->searchable()->required(),
                Select::make('entertainer_id')->label('Entertainer')->relationship('entertainer', 'name')->searchable()->required(),
                TextInput::make('customer_name')->label('Klantnaam')->required()->maxLength(255),
                TextInput::make('customer_email')->label('Klant e-mail')->email()->required()->maxLength(255),
                Select::make('rating')->label('Beoordeling')->options([
                    5 => '5 van 5',
                    4 => '4 van 5',
                    3 => '3 van 5',
                    2 => '2 van 5',
                    1 => '1 van 5',
                ]),
                TextInput::make('title')->label('Titel')->maxLength(120),
                Textarea::make('body')->label('Review')->rows(6)->columnSpanFull(),
                Select::make('status')
                    ->label('Status')
                    ->options(collect(ReviewStatus::cases())->mapWithKeys(fn (ReviewStatus $status): array => [$status->value => $status->label()])->all())
                    ->required(),
                DateTimePicker::make('link_sent_at')->label('Link verzonden op'),
                DateTimePicker::make('submitted_at')->label('Ingestuurd op'),
                DateTimePicker::make('published_at')->label('Gepubliceerd op')->helperText('Alleen goedgekeurde reviews met publicatiedatum zijn zichtbaar op het profiel.'),
            ]);
    }
}
