<?php

namespace App\Filament\Resources\BookingRequests;

use App\Filament\Resources\BookingRequests\Pages\CreateBookingRequest;
use App\Filament\Resources\BookingRequests\Pages\EditBookingRequest;
use App\Filament\Resources\BookingRequests\Pages\ListBookingRequests;
use App\Filament\Resources\BookingRequests\RelationManagers\EventsRelationManager;
use App\Filament\Resources\BookingRequests\RelationManagers\MatchesRelationManager;
use App\Filament\Resources\BookingRequests\Schemas\BookingRequestForm;
use App\Filament\Resources\BookingRequests\Tables\BookingRequestsTable;
use App\Models\BookingRequest;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class BookingRequestResource extends Resource
{
    protected static ?string $model = BookingRequest::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $modelLabel = 'aanvraag';

    protected static ?string $pluralModelLabel = 'aanvragen';

    protected static ?string $navigationLabel = 'Aanvragen';

    public static function form(Schema $schema): Schema
    {
        return BookingRequestForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return BookingRequestsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            EventsRelationManager::class,
            MatchesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListBookingRequests::route('/'),
            'create' => CreateBookingRequest::route('/create'),
            'edit' => EditBookingRequest::route('/{record}/edit'),
        ];
    }
}
