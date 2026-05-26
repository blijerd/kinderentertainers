<?php

namespace App\Filament\Resources\Availabilities\Tables;

use App\Enums\AvailabilityStatus;
use Filament\Forms\Components\DatePicker;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

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
                SelectFilter::make('status')
                    ->label('Status')
                    ->options(collect(AvailabilityStatus::cases())->mapWithKeys(fn ($status) => [$status->value => $status->label()])->all()),
                SelectFilter::make('entertainer_id')
                    ->label('Entertainer')
                    ->relationship('entertainer', 'name')
                    ->searchable()
                    ->preload(),
                Filter::make('date')
                    ->label('Datum')
                    ->schema([
                        DatePicker::make('date')->label('Datum'),
                    ])
                    ->query(fn (Builder $query, array $data): Builder => $query
                        ->when($data['date'] ?? null, fn (Builder $query, string $date): Builder => $query->whereDate('date', $date))),
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
