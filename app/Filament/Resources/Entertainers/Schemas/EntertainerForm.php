<?php

namespace App\Filament\Resources\Entertainers\Schemas;

use App\Enums\AccountingProvider;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class EntertainerForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('user_id')->label('Gebruiker')->relationship('user', 'name')->searchable()->required(),
                TextInput::make('name')->label('Naam')->required()->maxLength(255),
                TextInput::make('slug')->label('Slug')->required()->maxLength(255),
                FileUpload::make('profile_photo_path')->label('Profielfoto')->image()->disk('public')->directory('entertainers/profile-photos'),
                FileUpload::make('gallery_photo_paths')
                    ->label('Galerijfoto\'s')
                    ->image()
                    ->multiple()
                    ->reorderable()
                    ->disk('public')
                    ->directory('entertainers/gallery')
                    ->columnSpanFull(),
                Textarea::make('short_introduction')->label('Korte introductie')->required()->maxLength(240)->columnSpanFull(),
                Textarea::make('bio')->label('Uitgebreide bio')->rows(6)->columnSpanFull(),
                Textarea::make('profile_highlights')
                    ->label('Highlights')
                    ->helperText('Een highlight per regel.')
                    ->rows(4)
                    ->dehydrateStateUsing(fn ($state): array => collect(preg_split('/\r\n|\r|\n/', (string) $state))->map(fn (string $highlight): string => trim($highlight))->filter()->values()->all())
                    ->formatStateUsing(fn ($state): string => is_array($state) ? implode("\n", $state) : (string) $state)
                    ->columnSpanFull(),
                TextInput::make('audience_age_range')->label('Leeftijdsrange')->maxLength(255),
                Textarea::make('event_types')
                    ->label('Feesttypes')
                    ->helperText('Een type per regel.')
                    ->rows(3)
                    ->dehydrateStateUsing(fn ($state): array => collect(preg_split('/\r\n|\r|\n/', (string) $state))->map(fn (string $type): string => trim($type))->filter()->values()->all())
                    ->formatStateUsing(fn ($state): string => is_array($state) ? implode("\n", $state) : (string) $state),
                Textarea::make('languages')
                    ->label('Talen')
                    ->helperText('Een taal per regel.')
                    ->rows(3)
                    ->dehydrateStateUsing(fn ($state): array => collect(preg_split('/\r\n|\r|\n/', (string) $state))->map(fn (string $language): string => trim($language))->filter()->values()->all())
                    ->formatStateUsing(fn ($state): string => is_array($state) ? implode("\n", $state) : (string) $state),
                TextInput::make('rating')->label('Beoordeling')->numeric()->minValue(0)->maxValue(5)->step(0.1),
                TextInput::make('reviews_count')->label('Aantal beoordelingen')->numeric()->minValue(0)->required(),
                TextInput::make('performance_duration_minutes')->label('Duur optreden in minuten')->numeric()->minValue(1)->maxValue(1440),
                TextInput::make('setup_time_minutes')->label('Opbouwtijd in minuten')->numeric()->minValue(0)->maxValue(1440),
                TextInput::make('show_reel_url')->label('Showreel URL')->url()->maxLength(255),
                Textarea::make('practical_requirements')->label('Praktische eisen')->rows(4)->columnSpanFull(),
                TextInput::make('city')->label('Woonplaats')->required()->maxLength(255),
                TextInput::make('region')->label('Regio/provincie')->required()->maxLength(255),
                TextInput::make('working_radius_km')->label('Werkgebied in km')->numeric()->required(),
                Select::make('accounting_provider')
                    ->label('Boekhoudpakket')
                    ->options(collect(AccountingProvider::cases())->mapWithKeys(fn (AccountingProvider $provider): array => [$provider->value => $provider->label()])->all())
                    ->required(),
                Textarea::make('accounting_notes')->label('Facturatienotities')->rows(3)->columnSpanFull(),
                Select::make('skills')->label('Skills')->relationship('skills', 'name')->multiple()->preload(),
                Toggle::make('active')->label('Actief'),
                Toggle::make('featured')->label('Uitgelicht'),
                Toggle::make('profile_complete')->label('Profiel compleet'),
            ]);
    }
}
