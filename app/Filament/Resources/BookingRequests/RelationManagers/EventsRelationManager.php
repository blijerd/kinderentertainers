<?php

namespace App\Filament\Resources\BookingRequests\RelationManagers;

use App\Enums\BookingRequestEventType;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class EventsRelationManager extends RelationManager
{
    protected static string $relationship = 'events';

    protected static ?string $title = 'Aanvraaglog';

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('body')
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('created_at')->label('Tijd')->dateTime('d-m-Y H:i')->sortable(),
                TextColumn::make('type')->label('Type')->badge()->formatStateUsing(fn ($state) => $state?->label() ?? $state),
                TextColumn::make('actor_name')->label('Afzender')->placeholder('-')->searchable(),
                TextColumn::make('body')->label('Inhoud')->limit(100)->wrap()->placeholder('-')->searchable(),
                TextColumn::make('old_status')->label('Van')->formatStateUsing(fn ($state) => $state?->label() ?? $state)->placeholder('-'),
                TextColumn::make('new_status')->label('Naar')->formatStateUsing(fn ($state) => $state?->label() ?? $state)->placeholder('-'),
                IconColumn::make('visible_to_entertainer')->label('Dashboard')->boolean(),
            ])
            ->headerActions([
                CreateAction::make()
                    ->label('Logregel toevoegen')
                    ->modalHeading('Logregel toevoegen')
                    ->form([
                        Select::make('type')
                            ->label('Type')
                            ->options([
                                BookingRequestEventType::CustomerMessage->value => BookingRequestEventType::CustomerMessage->label(),
                                BookingRequestEventType::InternalNote->value => BookingRequestEventType::InternalNote->label(),
                                BookingRequestEventType::EntertainerResponse->value => BookingRequestEventType::EntertainerResponse->label(),
                                BookingRequestEventType::System->value => BookingRequestEventType::System->label(),
                            ])
                            ->default(BookingRequestEventType::InternalNote->value)
                            ->required(),
                        Textarea::make('body')->label('Inhoud')->required()->rows(4)->maxLength(5000),
                        Select::make('visible_to_entertainer')
                            ->label('Zichtbaar in entertainer-dashboard')
                            ->options([
                                true => 'Ja',
                                false => 'Nee',
                            ])
                            ->default(false)
                            ->required(),
                    ])
                    ->mutateFormDataUsing(fn (array $data): array => [
                        ...$data,
                        'actor_type' => 'admin',
                        'actor_name' => auth()->user()?->name,
                        'user_id' => auth()->id(),
                    ]),
            ])
            ->toolbarActions([
                BulkActionGroup::make([DeleteBulkAction::make()]),
            ]);
    }
}
