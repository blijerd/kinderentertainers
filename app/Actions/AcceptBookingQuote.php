<?php

namespace App\Actions;

use App\Enums\BookingRequestEventType;
use App\Enums\BookingStatus;
use App\Models\BookingRequest;
use App\Services\SelfBillingIntegrationService;
use Illuminate\Support\Arr;
use RuntimeException;

class AcceptBookingQuote
{
    public function __construct(private readonly SelfBillingIntegrationService $selfBilling) {}

    /**
     * @param  array{acceptance_name?: string|null, ip?: string|null, user_agent?: string|null}  $audit
     */
    public function handle(BookingRequest $bookingRequest, array $audit = []): BookingRequest
    {
        $bookingRequest->loadMissing('entertainer');

        $entertainer = $bookingRequest->entertainer;
        $termsBody = (string) $bookingRequest->quote_terms_body;
        $agreementHash = hash('sha256', implode('|', [
            $bookingRequest->id,
            $bookingRequest->quote_total_cents,
            $bookingRequest->quote_terms_version,
            $termsBody,
            now()->toIso8601String(),
        ]));

        $bookingRequest->fill([
            'quote_accepted_at' => now(),
            'quote_acceptance_name' => Arr::get($audit, 'acceptance_name') ?: $bookingRequest->name,
            'quote_acceptance_ip' => Arr::get($audit, 'ip'),
            'quote_acceptance_user_agent' => Arr::get($audit, 'user_agent'),
            'agreement_accepted_at' => now(),
            'agreement_version' => $bookingRequest->quote_terms_version,
            'agreement_hash' => $agreementHash,
            'payment_status' => $bookingRequest->deposit_cents ? 'deposit_due' : 'not_required',
            'invoice_status' => 'to_be_invoiced_by_entertainer',
            'invoice_provider' => $entertainer?->accounting_provider?->value,
            'payment_provider' => $entertainer?->payment_provider?->value,
            'cash_payment_allowed' => (bool) $entertainer?->cash_payment_enabled,
            'payment_instruction_sent_at' => null,
        ]);
        $bookingRequest->forceFill([
            'status' => BookingStatus::Confirmed,
        ])->save();

        $bookingRequest->events()->create([
            'type' => BookingRequestEventType::System,
            'actor_type' => 'system',
            'actor_name' => 'Platform',
            'body' => 'Offerte geaccepteerd. De entertainer factureert zelf en handelt betaling rechtstreeks met de klant af.',
            'visible_to_entertainer' => true,
            'visible_to_customer' => true,
        ]);

        try {
            return $this->selfBilling->prepareAfterAcceptance($bookingRequest->refresh());
        } catch (RuntimeException $exception) {
            $bookingRequest->refresh()->update([
                'invoice_status' => str_starts_with($exception->getMessage(), 'Moneybird')
                    ? 'invoice_setup_failed'
                    : $bookingRequest->invoice_status,
                'payment_status' => str_contains(strtolower($exception->getMessage()), 'checkout')
                    || str_contains(strtolower($exception->getMessage()), 'betaalverzoek')
                        ? 'payment_setup_failed'
                        : $bookingRequest->payment_status,
            ]);

            $bookingRequest->events()->create([
                'type' => BookingRequestEventType::System,
                'actor_type' => 'system',
                'actor_name' => 'Platform',
                'body' => 'Integratie-afhandeling na akkoord is mislukt: '.$exception->getMessage(),
                'visible_to_entertainer' => true,
                'visible_to_customer' => false,
            ]);

            return $bookingRequest->refresh();
        }
    }
}
