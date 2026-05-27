<?php

namespace App\Filament\Resources\BookingRequests\RelationManagers;

use App\Actions\SelectBookingRequestMatch;
use App\Enums\BookingRequestMatchStatus;
use Filament\Actions\Action;
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
                TextColumn::make('price_indication_cents')
                    ->label('Prijsindicatie')
                    ->money('EUR', divideBy: 100)
                    ->placeholder('-'),
                TextColumn::make('response_message')->label('Bericht')->limit(60)->placeholder('-'),
                TextColumn::make('matched_at')->label('Gematcht')->dateTime('d-m-Y H:i')->sortable(),
                TextColumn::make('responded_at')->label('Gereageerd')->dateTime('d-m-Y H:i')->placeholder('-')->sortable(),
                TextColumn::make('selected_at')->label('Gekozen')->dateTime('d-m-Y H:i')->placeholder('-')->sortable(),
            ])
            ->recordActions([
                Action::make('select')
                    ->label('Kies match')
                    ->requiresConfirmation()
                    ->visible(fn ($record): bool => $record->responded_at !== null
                        && ! in_array($record->status, [
                            BookingRequestMatchStatus::Rejected,
                            BookingRequestMatchStatus::Accepted,
                            BookingRequestMatchStatus::Expired,
                        ], true))
                    ->action(fn ($record) => app(SelectBookingRequestMatch::class)->handle($record)),
            ])
            ->toolbarActions([
                BulkActionGroup::make([DeleteBulkAction::make()]),
            ]);
    }
}
