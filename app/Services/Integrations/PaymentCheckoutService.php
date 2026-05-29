<?php

namespace App\Services\Integrations;

use App\Enums\IntegrationProvider;
use App\Enums\PaymentProvider;
use App\Models\BookingRequest;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class PaymentCheckoutService
{
    public function createCheckout(BookingRequest $bookingRequest): BookingRequest
    {
        $bookingRequest->loadMissing('entertainer.integrations');

        $provider = $bookingRequest->payment_provider
            ? PaymentProvider::tryFrom($bookingRequest->payment_provider)
            : null;

        if (! $provider || $provider === PaymentProvider::Manual || $provider === PaymentProvider::Other || ! $bookingRequest->deposit_cents) {
            return $bookingRequest;
        }

        $integrationProvider = IntegrationProvider::tryFrom($provider->value);
        $integration = $integrationProvider
            ? $bookingRequest->entertainer?->integrations->first(fn ($integration): bool => $integration->provider === $integrationProvider)
            : null;

        if (! $integration?->enabled) {
            return $bookingRequest;
        }

        $payload = match ($provider) {
            PaymentProvider::Mollie => $this->mollieCheckout($bookingRequest, $integration->credentials ?? []),
            PaymentProvider::Stripe => $this->stripeCheckout($bookingRequest, $integration->credentials ?? []),
            PaymentProvider::PayPal => $this->paypalCheckout($bookingRequest, $integration->credentials ?? []),
            PaymentProvider::PayNl => $this->payNlCheckout($bookingRequest, $integration->credentials ?? [], $integration->settings ?? []),
            PaymentProvider::Rabobank => $this->rabobankCheckout($bookingRequest, $integration->credentials ?? [], $integration->settings ?? []),
            default => null,
        };

        if (! $payload) {
            return $bookingRequest;
        }

        $bookingRequest->update([
            'payment_external_id' => $payload['id'] ?? null,
            'payment_checkout_url' => $payload['url'] ?? null,
            'payment_checkout_created_at' => now(),
        ]);

        return $bookingRequest->refresh();
    }

    public function markWebhookPayment(PaymentProvider $provider, string $externalId, ?string $status = null): ?BookingRequest
    {
        $bookingRequest = BookingRequest::query()
            ->where('payment_provider', $provider->value)
            ->where('payment_external_id', $externalId)
            ->first();

        if (! $bookingRequest) {
            return null;
        }

        $status = $this->verifiedStatus($bookingRequest, $provider, $externalId, $status);

        if (in_array($status, ['paid', 'succeeded', 'complete', 'completed'], true)) {
            $bookingRequest->update([
                'paid_cents' => max((int) $bookingRequest->paid_cents, (int) $bookingRequest->deposit_cents),
                'payment_status' => 'paid',
            ]);
        } elseif (in_array($status, ['refunded', 'partially_refunded'], true)) {
            $bookingRequest->update([
                'payment_status' => $status,
                'paid_cents' => $status === 'refunded' ? 0 : (int) $bookingRequest->paid_cents,
            ]);
        } elseif (in_array($status, ['chargeback', 'charged_back', 'dispute', 'disputed'], true)) {
            $bookingRequest->update(['payment_status' => 'chargeback']);
        } elseif (in_array($status, ['failed', 'canceled', 'cancelled', 'expired'], true)) {
            $bookingRequest->update(['payment_status' => $status]);
        }

        return $bookingRequest->refresh();
    }

    /**
     * @param  array<string, mixed>  $credentials
     * @return array{id: string|null, url: string|null}
     */
    private function mollieCheckout(BookingRequest $bookingRequest, array $credentials): array
    {
        $response = Http::withToken((string) ($credentials['api_key'] ?? ''))
            ->acceptJson()
            ->post('https://api.mollie.com/v2/payments', [
                'amount' => [
                    'currency' => 'EUR',
                    'value' => number_format(((int) $bookingRequest->deposit_cents) / 100, 2, '.', ''),
                ],
                'description' => 'Aanbetaling boeking '.$bookingRequest->id,
                'redirectUrl' => route('booking-quotes.show', $bookingRequest->quote_acceptance_token),
                'webhookUrl' => route('webhooks.payments', PaymentProvider::Mollie->value),
                'metadata' => ['booking_request_id' => $bookingRequest->id],
            ]);

        $this->throwIfFailed($response->successful(), 'Mollie checkout kon niet worden aangemaakt.');
        $json = $response->json();

        return ['id' => $json['id'] ?? null, 'url' => $json['_links']['checkout']['href'] ?? null];
    }

    /**
     * @param  array<string, mixed>  $credentials
     * @return array{id: string|null, url: string|null}
     */
    private function stripeCheckout(BookingRequest $bookingRequest, array $credentials): array
    {
        $response = Http::asForm()
            ->withToken((string) ($credentials['secret_key'] ?? ''))
            ->post('https://api.stripe.com/v1/checkout/sessions', [
                'mode' => 'payment',
                'success_url' => route('booking-quotes.show', $bookingRequest->quote_acceptance_token),
                'cancel_url' => route('booking-quotes.show', $bookingRequest->quote_acceptance_token),
                'client_reference_id' => (string) $bookingRequest->id,
                'line_items[0][price_data][currency]' => 'eur',
                'line_items[0][price_data][product_data][name]' => 'Aanbetaling boeking '.$bookingRequest->id,
                'line_items[0][price_data][unit_amount]' => (int) $bookingRequest->deposit_cents,
                'line_items[0][quantity]' => 1,
            ]);

        $this->throwIfFailed($response->successful(), 'Stripe checkout kon niet worden aangemaakt.');
        $json = $response->json();

        return ['id' => $json['id'] ?? null, 'url' => $json['url'] ?? null];
    }

    /**
     * @param  array<string, mixed>  $credentials
     * @return array{id: string|null, url: string|null}
     */
    private function paypalCheckout(BookingRequest $bookingRequest, array $credentials): array
    {
        $tokenResponse = Http::asForm()
            ->withBasicAuth((string) ($credentials['client_id'] ?? ''), (string) ($credentials['client_secret'] ?? ''))
            ->post('https://api-m.paypal.com/v1/oauth2/token', ['grant_type' => 'client_credentials']);

        $this->throwIfFailed($tokenResponse->successful(), 'PayPal token kon niet worden opgehaald.');

        $response = Http::withToken((string) $tokenResponse->json('access_token'))
            ->acceptJson()
            ->post('https://api-m.paypal.com/v2/checkout/orders', [
                'intent' => 'CAPTURE',
                'purchase_units' => [[
                    'reference_id' => (string) $bookingRequest->id,
                    'amount' => [
                        'currency_code' => 'EUR',
                        'value' => number_format(((int) $bookingRequest->deposit_cents) / 100, 2, '.', ''),
                    ],
                ]],
                'application_context' => [
                    'return_url' => route('booking-quotes.show', $bookingRequest->quote_acceptance_token),
                    'cancel_url' => route('booking-quotes.show', $bookingRequest->quote_acceptance_token),
                ],
            ]);

        $this->throwIfFailed($response->successful(), 'PayPal checkout kon niet worden aangemaakt.');
        $json = $response->json();
        $approveUrl = collect($json['links'] ?? [])->firstWhere('rel', 'approve')['href'] ?? null;

        return ['id' => $json['id'] ?? null, 'url' => $approveUrl];
    }

    /**
     * @param  array<string, mixed>  $credentials
     * @param  array<string, mixed>  $settings
     * @return array{id: string|null, url: string|null}
     */
    private function payNlCheckout(BookingRequest $bookingRequest, array $credentials, array $settings): array
    {
        $response = Http::withToken((string) ($credentials['api_token'] ?? ''))
            ->acceptJson()
            ->post('https://rest.pay.nl/v2/transactions', [
                'serviceId' => $settings['service_id'] ?? null,
                'amount' => ['value' => (int) $bookingRequest->deposit_cents, 'currency' => 'EUR'],
                'description' => 'Aanbetaling boeking '.$bookingRequest->id,
                'returnUrl' => route('booking-quotes.show', $bookingRequest->quote_acceptance_token),
                'exchangeUrl' => route('webhooks.payments', PaymentProvider::PayNl->value),
                'reference' => (string) $bookingRequest->id,
            ]);

        $this->throwIfFailed($response->successful(), 'Pay.nl checkout kon niet worden aangemaakt.');
        $json = $response->json();

        return ['id' => $json['id'] ?? $json['transactionId'] ?? null, 'url' => $json['paymentUrl'] ?? $json['links']['checkout'] ?? null];
    }

    /**
     * @param  array<string, mixed>  $credentials
     * @param  array<string, mixed>  $settings
     * @return array{id: string|null, url: string|null}
     */
    private function rabobankCheckout(BookingRequest $bookingRequest, array $credentials, array $settings): array
    {
        $response = Http::withToken((string) ($credentials['refresh_token'] ?? ''))
            ->acceptJson()
            ->post('https://api.rabobank.nl/openapi/smartpay/payment-requests', [
                'merchantId' => $settings['merchant_id'] ?? $credentials['merchant_id'] ?? null,
                'amount' => ['currency' => 'EUR', 'value' => number_format(((int) $bookingRequest->deposit_cents) / 100, 2, '.', '')],
                'description' => 'Aanbetaling boeking '.$bookingRequest->id,
                'reference' => (string) $bookingRequest->id,
                'redirectUrl' => route('booking-quotes.show', $bookingRequest->quote_acceptance_token),
                'webhookUrl' => route('webhooks.payments', PaymentProvider::Rabobank->value),
            ]);

        $this->throwIfFailed($response->successful(), 'Rabo Smart Pay betaalverzoek kon niet worden aangemaakt.');
        $json = $response->json();

        return ['id' => $json['id'] ?? $json['paymentRequestId'] ?? null, 'url' => $json['url'] ?? $json['paymentUrl'] ?? null];
    }

    private function throwIfFailed(bool $successful, string $message): void
    {
        if (! $successful) {
            throw new RuntimeException($message);
        }
    }

    private function verifiedStatus(BookingRequest $bookingRequest, PaymentProvider $provider, string $externalId, ?string $fallbackStatus): ?string
    {
        $bookingRequest->loadMissing('entertainer.integrations');
        $integrationProvider = IntegrationProvider::tryFrom($provider->value);
        $integration = $integrationProvider
            ? $bookingRequest->entertainer?->integrations->first(fn ($integration): bool => $integration->provider === $integrationProvider)
            : null;

        if (! $integration?->enabled) {
            return $fallbackStatus ? strtolower($fallbackStatus) : null;
        }

        $credentials = $integration->credentials ?? [];

        $status = match ($provider) {
            PaymentProvider::Mollie => Http::withToken((string) ($credentials['api_key'] ?? ''))
                ->get('https://api.mollie.com/v2/payments/'.$externalId)
                ->json('status'),
            PaymentProvider::Stripe => Http::withToken((string) ($credentials['secret_key'] ?? ''))
                ->get('https://api.stripe.com/v1/checkout/sessions/'.$externalId)
                ->json('payment_status'),
            PaymentProvider::PayPal => $this->paypalOrderStatus($externalId, $credentials),
            default => $fallbackStatus,
        };

        return is_string($status) ? strtolower($status) : ($fallbackStatus ? strtolower($fallbackStatus) : null);
    }

    /**
     * @param  array<string, mixed>  $credentials
     */
    private function paypalOrderStatus(string $externalId, array $credentials): ?string
    {
        $tokenResponse = Http::asForm()
            ->withBasicAuth((string) ($credentials['client_id'] ?? ''), (string) ($credentials['client_secret'] ?? ''))
            ->post('https://api-m.paypal.com/v1/oauth2/token', ['grant_type' => 'client_credentials']);

        if (! $tokenResponse->successful()) {
            return null;
        }

        return Http::withToken((string) $tokenResponse->json('access_token'))
            ->get('https://api-m.paypal.com/v2/checkout/orders/'.$externalId)
            ->json('status');
    }
}
