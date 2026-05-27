<?php

namespace App\Filament\Resources\LegalDocumentVersions;

use App\Filament\Resources\LegalDocumentVersions\Pages\CreateLegalDocumentVersion;
use App\Filament\Resources\LegalDocumentVersions\Pages\EditLegalDocumentVersion;
use App\Filament\Resources\LegalDocumentVersions\Pages\ListLegalDocumentVersions;
use App\Filament\Resources\LegalDocumentVersions\Schemas\LegalDocumentVersionForm;
use App\Filament\Resources\LegalDocumentVersions\Tables\LegalDocumentVersionsTable;
use App\Models\LegalDocumentVersion;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class LegalDocumentVersionResource extends Resource
{
    protected static ?string $model = LegalDocumentVersion::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    protected static ?string $modelLabel = 'juridische documentversie';

    protected static ?string $pluralModelLabel = 'juridische documentversies';

    protected static ?string $navigationLabel = 'Legal documenten';

    public static function form(Schema $schema): Schema
    {
        return LegalDocumentVersionForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return LegalDocumentVersionsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListLegalDocumentVersions::route('/'),
            'create' => CreateLegalDocumentVersion::route('/create'),
            'edit' => EditLegalDocumentVersion::route('/{record}/edit'),
        ];
    }
}
