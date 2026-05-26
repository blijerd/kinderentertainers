<?php

namespace App\Filament\Resources\BookingRequests\RelationManagers;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class MatchesRelationManager extends RelationManager
{
    protected static string $relationship = 'matches';

    protected static ?string $title = 'Gevonden matches';

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('entertainer.name')
            ->columns([
                TextColumn::make('entertainer.name')->label('Entertainer')->searchable()->sortable(),
                TextColumn::make('status')->label('Status')->badge()->formatStateUsing(fn ($state) => $state?->label() ?? $state),
                TextColumn::make('matched_at')->label('Gematcht')->dateTime('d-m-Y H:i')->sortable(),
                TextColumn::make('responded_at')->label('Gereageerd')->dateTime('d-m-Y H:i')->placeholder('-')->sortable(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([DeleteBulkAction::make()]),
            ]);
    }
}
