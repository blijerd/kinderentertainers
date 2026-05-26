<?php

namespace App\Filament\Resources\BookingRequests\Tables;

use App\Enums\BookingStatus;
use App\Models\Skill;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class BookingRequestsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('created_at')->label('Ontvangen')->dateTime('d-m-Y H:i')->sortable(),
                TextColumn::make('request_type')
                    ->label('Type')
                    ->state(fn ($record) => $record->isGeneral() ? 'Algemeen' : 'Specifiek')
                    ->badge(),
                TextColumn::make('skill.name')->label('Skill')->searchable()->sortable()->placeholder('-'),
                TextColumn::make('entertainer.name')->label('Entertainer')->searchable()->sortable()->placeholder('-'),
                TextColumn::make('matches_count')->label('Matches')->counts('matches')->sortable(),
                TextColumn::make('name')->label('Klant')->searchable(),
                TextColumn::make('event_date')->label('Datum')->date('d-m-Y')->sortable(),
                TextColumn::make('city')->label('Plaats')->searchable(),
                TextColumn::make('status')->label('Status')->badge()->formatStateUsing(fn ($state) => $state?->label() ?? $state),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Status')
                    ->options(collect(BookingStatus::cases())->mapWithKeys(fn ($status) => [$status->value => $status->label()])->all()),
                SelectFilter::make('entertainer_id')
                    ->label('Entertainer')
                    ->relationship('entertainer', 'name')
                    ->searchable()
                    ->preload(),
                SelectFilter::make('skill')
                    ->label('Skill')
                    ->options(fn (): array => Skill::query()->orderBy('name')->pluck('name', 'name')->all())
                    ->query(fn (Builder $query, array $data): Builder => $query
                        ->when($data['value'] ?? null, fn (Builder $query, string $skill): Builder => $query->whereJsonContains('desired_skills', $skill))),
                Filter::make('event_date')
                    ->label('Datum')
                    ->schema([
                        DatePicker::make('event_date')->label('Evenementdatum'),
                    ])
                    ->query(fn (Builder $query, array $data): Builder => $query
                        ->when($data['event_date'] ?? null, fn (Builder $query, string $date): Builder => $query->whereDate('event_date', $date))),
            ])
            ->recordActions([EditAction::make()])
            ->toolbarActions([
                BulkActionGroup::make([DeleteBulkAction::make()]),
            ]);
    }
}
