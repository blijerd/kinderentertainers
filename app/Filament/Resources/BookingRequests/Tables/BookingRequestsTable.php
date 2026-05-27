<?php

namespace App\Filament\Resources\BookingRequests\Tables;

use App\Enums\BookingStatus;
use App\Models\Entertainer;
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
                TextColumn::make('matches.status')
                    ->label('Matchstatussen')
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state?->label() ?? $state)
                    ->placeholder('-'),
                TextColumn::make('name')->label('Klant')->searchable(),
                TextColumn::make('event_date')->label('Datum')->date('d-m-Y')->sortable(),
                TextColumn::make('city')->label('Plaats')->searchable(),
                TextColumn::make('price_indication_min_cents')
                    ->label('Indicatie')
                    ->state(function ($record): string {
                        if (! $record->price_indication_min_cents) {
                            return '-';
                        }

                        $min = number_format($record->price_indication_min_cents / 100, 0, ',', '.');
                        $max = number_format(($record->price_indication_max_cents ?? $record->price_indication_min_cents) / 100, 0, ',', '.');

                        return $min === $max ? "€ {$min}" : "€ {$min} - € {$max}";
                    }),
                TextColumn::make('status')->label('Status')->badge()->formatStateUsing(fn ($state) => $state?->label() ?? $state),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Status')
                    ->options(collect(BookingStatus::cases())->mapWithKeys(fn ($status) => [$status->value => $status->label()])->all()),
                SelectFilter::make('request_type')
                    ->label('Aanvraagtype')
                    ->options([
                        'specific' => 'Specifiek',
                        'general' => 'Algemeen',
                    ])
                    ->query(fn (Builder $query, array $data): Builder => $query
                        ->when($data['value'] ?? null, fn (Builder $query, string $type): Builder => $type === 'general'
                            ? $query->whereNull('entertainer_id')->whereNotNull('skill_id')
                            : $query->whereNotNull('entertainer_id'))),
                SelectFilter::make('entertainer_id')
                    ->label('Specifieke entertainer')
                    ->relationship('entertainer', 'name')
                    ->searchable()
                    ->preload(),
                SelectFilter::make('skill_id')
                    ->label('Skill')
                    ->relationship('skill', 'name')
                    ->searchable()
                    ->preload(),
                SelectFilter::make('match_entertainer')
                    ->label('Match entertainer')
                    ->options(fn (): array => Entertainer::query()->orderBy('name')->pluck('name', 'id')->all())
                    ->searchable()
                    ->query(fn (Builder $query, array $data): Builder => $query
                        ->when($data['value'] ?? null, fn (Builder $query, string $entertainerId): Builder => $query
                            ->whereHas('matches', fn (Builder $matches): Builder => $matches->where('entertainer_id', $entertainerId)))),
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
