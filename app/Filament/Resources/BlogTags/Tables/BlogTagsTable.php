<?php

namespace App\Filament\Resources\BlogTags\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class BlogTagsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->label('Naam')->searchable()->sortable(),
                TextColumn::make('slug')->label('Slug')->searchable(),
                TextColumn::make('posts_count')->label('Artikelen')->counts('posts')->sortable(),
                IconColumn::make('noindex')->label('Noindex')->boolean(),
                TextColumn::make('updated_at')->label('Bijgewerkt')->dateTime()->sortable(),
            ])
            ->defaultSort('name')
            ->filters([
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
