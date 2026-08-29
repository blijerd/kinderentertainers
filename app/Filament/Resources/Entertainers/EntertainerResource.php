<?php

namespace App\Filament\Resources\Entertainers;

use App\Filament\Resources\Entertainers\Pages\CreateEntertainer;
use App\Filament\Resources\Entertainers\Pages\EditEntertainer;
use App\Filament\Resources\Entertainers\Pages\ListEntertainers;
use App\Filament\Resources\Entertainers\Schemas\EntertainerForm;
use App\Filament\Resources\Entertainers\Tables\EntertainersTable;
use App\Models\Entertainer;
use App\Support\Filament\Concerns\ResolvesPublicRecordRouteBinding;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class EntertainerResource extends Resource
{
    use ResolvesPublicRecordRouteBinding;

    protected static ?string $model = Entertainer::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $modelLabel = 'entertainer';

    protected static ?string $pluralModelLabel = 'entertainers';

    protected static ?string $navigationLabel = 'Entertainers';

    public static function form(Schema $schema): Schema
    {
        return EntertainerForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return EntertainersTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListEntertainers::route('/'),
            'create' => CreateEntertainer::route('/create'),
            'edit' => EditEntertainer::route('/{record}/edit'),
        ];
    }
}
