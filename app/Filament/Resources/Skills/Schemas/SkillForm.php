<?php

namespace App\Filament\Resources\Skills\Schemas;

use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class SkillForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')->label('Naam')->required()->maxLength(255),
                TextInput::make('slug')->label('Slug')->required()->maxLength(255),
                Textarea::make('description')->label('Beschrijving')->columnSpanFull(),
                TextInput::make('icon')->label('Icoon')->helperText('Optionele icon-naam voor latere frontend-weergave.'),
                Toggle::make('active')->label('Actief')->default(true),
            ]);
    }
}
