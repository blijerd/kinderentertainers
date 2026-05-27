<?php

namespace App\Filament\Resources\LegalDocumentVersions\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class LegalDocumentVersionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('legal_document_id')
                    ->label('Document')
                    ->relationship('legalDocument', 'title')
                    ->required()
                    ->preload(),
                TextInput::make('version_label')->label('Versie')->required()->maxLength(255),
                DateTimePicker::make('published_at')->label('Gepubliceerd op')->seconds(false),
                DateTimePicker::make('replaced_at')->label('Vervangen op')->seconds(false),
                Textarea::make('body')
                    ->label('Inhoud')
                    ->helperText('Markdown wordt op de publieke pagina naar HTML gerenderd.')
                    ->rows(18)
                    ->required()
                    ->columnSpanFull(),
            ]);
    }
}
