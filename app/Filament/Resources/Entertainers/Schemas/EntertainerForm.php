<?php

namespace App\Filament\Resources\Entertainers\Schemas;

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
                FileUpload::make('profile_photo_path')->label('Profielfoto')->image()->directory('entertainers'),
                Textarea::make('short_introduction')->label('Korte introductie')->required()->maxLength(240)->columnSpanFull(),
                Textarea::make('bio')->label('Uitgebreide bio')->rows(6)->columnSpanFull(),
                TextInput::make('city')->label('Woonplaats')->required()->maxLength(255),
                TextInput::make('region')->label('Regio/provincie')->required()->maxLength(255),
                TextInput::make('working_radius_km')->label('Werkgebied in km')->numeric()->required(),
                Select::make('skills')->label('Skills')->relationship('skills', 'name')->multiple()->preload(),
                Toggle::make('active')->label('Actief'),
                Toggle::make('featured')->label('Uitgelicht'),
                Toggle::make('profile_complete')->label('Profiel compleet'),
            ]);
    }
}
