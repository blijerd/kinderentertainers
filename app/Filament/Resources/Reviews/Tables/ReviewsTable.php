<?php

namespace App\Filament\Resources\Reviews\Tables;

use App\Enums\ReviewStatus;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class ReviewsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('submitted_at')->label('Ingestuurd')->dateTime('d-m-Y H:i')->sortable()->placeholder('-'),
                TextColumn::make('entertainer.name')->label('Entertainer')->searchable()->sortable(),
                TextColumn::make('customer_name')->label('Klant')->searchable(),
                TextColumn::make('rating')->label('Score')->sortable()->formatStateUsing(fn ($state) => $state ? "{$state}/5" : '-'),
                TextColumn::make('title')->label('Titel')->searchable()->placeholder('-'),
                TextColumn::make('status')->label('Status')->badge()->formatStateUsing(fn ($state) => $state?->label() ?? $state),
                TextColumn::make('published_at')->label('Gepubliceerd')->dateTime('d-m-Y H:i')->sortable()->placeholder('-'),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Status')
                    ->options(collect(ReviewStatus::cases())->mapWithKeys(fn (ReviewStatus $status): array => [$status->value => $status->label()])->all()),
                SelectFilter::make('entertainer_id')
                    ->label('Entertainer')
                    ->relationship('entertainer', 'name')
                    ->searchable()
                    ->preload(),
            ])
            ->recordActions([EditAction::make()])
            ->toolbarActions([
                BulkActionGroup::make([DeleteBulkAction::make()]),
            ]);
    }
}
