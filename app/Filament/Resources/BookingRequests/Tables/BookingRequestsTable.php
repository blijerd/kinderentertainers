<?php

namespace App\Filament\Resources\BookingRequests\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class BookingRequestsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('created_at')->label('Ontvangen')->dateTime('d-m-Y H:i')->sortable(),
                TextColumn::make('entertainer.name')->label('Entertainer')->searchable()->sortable(),
                TextColumn::make('name')->label('Klant')->searchable(),
                TextColumn::make('event_date')->label('Datum')->date('d-m-Y')->sortable(),
                TextColumn::make('city')->label('Plaats')->searchable(),
                TextColumn::make('status')->label('Status')->badge()->formatStateUsing(fn ($state) => $state?->label() ?? $state),
            ])
            ->filters([])
            ->recordActions([EditAction::make()])
            ->toolbarActions([
                BulkActionGroup::make([DeleteBulkAction::make()]),
            ]);
    }
}
