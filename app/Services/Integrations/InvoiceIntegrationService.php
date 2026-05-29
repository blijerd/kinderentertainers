<?php

namespace App\Services\Integrations;

use App\Enums\AccountingProvider;
use App\Enums\IntegrationProvider;
use App\Models\BookingRequest;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class InvoiceIntegrationService
{
    public function createInvoiceInstruction(BookingRequest $bookingRequest): BookingRequest
    {
        $bookingRequest->loadMissing('entertainer.integrations');

        $provider = $bookingRequest->invoice_provider
            ? AccountingProvider::tryFrom($bookingRequest->invoice_provider)
            : null;

        $reference = $bookingRequest->invoice_reference ?: 'INV-'.$bookingRequest->id.'-'.now()->format('Ymd');

        if (! $provider || in_array($provider, [AccountingProvider::Manual, AccountingProvider::Other], true)) {
            $bookingRequest->update([
                'invoice_status' => 'ready_for_entertainer',
                'invoice_reference' => $reference,
                'invoice_generated_at' => now(),
            ]);

            return $bookingRequest->refresh();
        }

        $integrationProvider = IntegrationProvider::tryFrom($provider->value);
        $integration = $integrationProvider
            ? $bookingRequest->entertainer?->integrations->first(fn ($integration): bool => $integration->provider === $integrationProvider)
            : null;

        if (! $integration?->enabled) {
            $bookingRequest->update([
                'invoice_status' => 'ready_for_entertainer',
                'invoice_reference' => $reference,
                'invoice_generated_at' => now(),
            ]);

            return $bookingRequest->refresh();
        }

        $payload = match ($provider) {
            AccountingProvider::Moneybird => $this->moneybirdInvoice($bookingRequest, $integration->credentials ?? [], $integration->settings ?? []),
            default => $this->genericExternalInvoice($bookingRequest, $provider),
        };

        $bookingRequest->update([
            'invoice_status' => 'sent_to_'.$provider->value,
            'invoice_reference' => $payload['reference'] ?? $reference,
            'invoice_external_id' => $payload['external_id'] ?? null,
            'invoice_url' => $payload['url'] ?? route('customer-portal.download', ['bookingRequest' => $bookingRequest, 'type' => 'invoice']),
            'invoice_generated_at' => now(),
        ]);

        return $bookingRequest->refresh();
    }

    /**
     * @param  array<string, mixed>  $credentials
     * @param  array<string, mixed>  $settings
     * @return array{reference?: string|null, external_id?: string|null, url?: string|null}
     */
    private function moneybirdInvoice(BookingRequest $bookingRequest, array $credentials, array $settings): array
    {
        $administrationId = (string) ($settings['administration_id'] ?? '');
        $response = Http::withToken((string) ($credentials['api_token'] ?? ''))
            ->acceptJson()
            ->post("https://moneybird.com/api/v2/{$administrationId}/sales_invoices.json", [
                'sales_invoice' => [
                    'reference' => 'Boeking '.$bookingRequest->id,
                    'contact' => [
                        'company_name' => $bookingRequest->company_name ?: $bookingRequest->name,
                        'firstname' => $bookingRequest->name,
                        'email' => $bookingRequest->email,
                    ],
                    'details_attributes' => [[
                        'description' => 'Kinderentertainment op '.$bookingRequest->event_date->format('d-m-Y'),
                        'price' => number_format(((int) $bookingRequest->quote_total_cents) / 100, 2, '.', ''),
                        'amount' => 1,
                    ]],
                    'workflow_id' => $settings['workflow_id'] ?? null,
                ],
            ]);

        if (! $response->successful()) {
            throw new RuntimeException('Moneybird factuur kon niet worden aangemaakt.');
        }

        return [
            'reference' => $response->json('invoice_id') ?? $response->json('reference'),
            'external_id' => $response->json('id'),
            'url' => $response->json('url'),
        ];
    }

    /**
     * @return array{reference?: string|null, external_id?: string|null, url?: string|null}
     */
    private function genericExternalInvoice(BookingRequest $bookingRequest, AccountingProvider $provider): array
    {
        return [
            'reference' => 'EXT-'.$provider->value.'-'.$bookingRequest->id,
            'external_id' => null,
            'url' => route('customer-portal.download', ['bookingRequest' => $bookingRequest, 'type' => 'invoice']),
        ];
    }
}
