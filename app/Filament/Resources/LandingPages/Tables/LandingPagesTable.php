<?php

namespace App\Filament\Resources\LandingPages\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class LandingPagesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')->label('Titel')->searchable()->sortable(),
                TextColumn::make('slug')->label('Slug')->searchable(),
                IconColumn::make('is_published')->label('Gepubliceerd')->boolean(),
                IconColumn::make('noindex')->label('Noindex')->boolean(),
                TextColumn::make('published_at')->label('Publiceren vanaf')->dateTime()->sortable(),
                TextColumn::make('updated_at')->label('Bijgewerkt')->dateTime()->sortable(),
            ])
            ->filters([
                SelectFilter::make('is_published')
                    ->label('Publicatiestatus')
                    ->options([
                        '1' => 'Gepubliceerd',
                        '0' => 'Concept',
                    ]),
                SelectFilter::make('noindex')
                    ->label('Indexeerbaarheid')
                    ->options([
                        '0' => 'Indexeerbaar',
                        '1' => 'Noindex',
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
