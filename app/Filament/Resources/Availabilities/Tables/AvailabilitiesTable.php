<?php

namespace App\Filament\Resources\Availabilities\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class AvailabilitiesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('entertainer.name')->label('Entertainer')->searchable()->sortable(),
                TextColumn::make('date')->label('Datum')->date('d-m-Y')->sortable(),
                TextColumn::make('start_time')->label('Start')->time('H:i'),
                TextColumn::make('end_time')->label('Einde')->time('H:i'),
                TextColumn::make('status')->label('Status')->badge()->formatStateUsing(fn ($state) => $state?->label() ?? $state),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
