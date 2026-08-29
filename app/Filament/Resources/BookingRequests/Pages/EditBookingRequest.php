<?php

namespace App\Filament\Resources\BookingRequests\Pages;

use App\Actions\TransitionBookingRequestStatus;
use App\Actions\UpdateBookingRequestDetails;
use App\Enums\BookingStatus;
use App\Filament\Resources\BookingRequests\BookingRequestResource;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Forms\Components\Textarea;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditBookingRequest extends EditRecord
{
    protected static string $resource = BookingRequestResource::class;

    /**
     * @param  array<string, mixed>  $data
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        unset($data['status']);

        return $data;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        return app(UpdateBookingRequestDetails::class)->handle($record, $data);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('confirm')
                ->label('Bevestigen')
                ->visible(fn (): bool => ! in_array($this->record->status, [BookingStatus::Confirmed, BookingStatus::Rejected, BookingStatus::Cancelled], true))
                ->requiresConfirmation()
                ->action(fn () => app(TransitionBookingRequestStatus::class)->handle($this->record, BookingStatus::Confirmed)),
            Action::make('reject')
                ->label('Afwijzen')
                ->color('danger')
                ->visible(fn (): bool => ! in_array($this->record->status, [BookingStatus::Rejected, BookingStatus::Cancelled], true))
                ->requiresConfirmation()
                ->action(fn () => app(TransitionBookingRequestStatus::class)->handle($this->record, BookingStatus::Rejected)),
            Action::make('cancel')
                ->label('Annuleren')
                ->color('warning')
                ->visible(fn (): bool => $this->record->status !== BookingStatus::Cancelled)
                ->form([
                    Textarea::make('cancellation_reason')->label('Reden')->required()->maxLength(5000),
                ])
                ->action(fn (array $data) => app(TransitionBookingRequestStatus::class)->handle(
                    $this->record,
                    BookingStatus::Cancelled,
                    $data['cancellation_reason'],
                    'admin',
                )),
            DeleteAction::make(),
        ];
    }
}
