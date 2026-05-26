<?php

namespace App\Filament\Resources\Entertainers\Tables;

use App\Models\Entertainer;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class EntertainersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->label('Naam')->searchable()->sortable(),
                TextColumn::make('region')->label('Regio')->searchable()->sortable(),
                TextColumn::make('skills.name')->label('Skills')->badge(),
                IconColumn::make('active')->label('Actief')->boolean(),
                IconColumn::make('featured')->label('Uitgelicht')->boolean(),
                IconColumn::make('profile_complete')->label('Compleet')->boolean(),
            ])
            ->filters([
                SelectFilter::make('region')
                    ->label('Regio')
                    ->options(fn (): array => Entertainer::query()->distinct()->orderBy('region')->pluck('region', 'region')->all()),
                SelectFilter::make('skill')
                    ->label('Skill')
                    ->relationship('skills', 'name')
                    ->multiple()
                    ->preload(),
                SelectFilter::make('active')
                    ->label('Status')
                    ->options([
                        '1' => 'Actief',
                        '0' => 'Inactief',
                    ]),
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
