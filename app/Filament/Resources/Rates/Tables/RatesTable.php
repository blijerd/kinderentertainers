<?php

namespace App\Filament\Resources\Rates\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class RatesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('entertainer.name')->label('Entertainer')->searchable()->sortable(),
                TextColumn::make('customer_type')->label('Doelgroep')->badge()->formatStateUsing(fn ($state) => $state?->label() ?? $state),
                TextColumn::make('starting_rate_cents')->label('Starttarief')->money('EUR', divideBy: 100),
                TextColumn::make('hourly_rate_cents')->label('Uurtarief')->money('EUR', divideBy: 100),
                TextColumn::make('minimum_hours')->label('Min. uren'),
                IconColumn::make('vat_included')->label('Incl. btw')->boolean(),
            ])
            ->filters([])
            ->recordActions([EditAction::make()])
            ->toolbarActions([
                BulkActionGroup::make([DeleteBulkAction::make()]),
            ]);
    }
}
