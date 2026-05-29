<?php

namespace App\Services;

use App\Enums\IntegrationProvider;
use App\Models\EntertainerIntegration;
use Illuminate\Support\Facades\Http;

class IntegrationHealthService
{
    /**
     * @return array{status: string, message: string}
     */
    public function check(EntertainerIntegration $integration): array
    {
        if (! $integration->enabled) {
            return ['status' => 'disabled', 'message' => 'Integratie staat uit.'];
        }

        $missing = $this->missingRequiredFields($integration);

        if ($missing !== []) {
            return ['status' => 'configuration_required', 'message' => 'Ontbrekende velden: '.implode(', ', $missing).'.'];
        }

        if (! config('services.integrations.live_health_checks')) {
            return ['status' => 'ready', 'message' => $integration->provider->label().' is klaar voor verwerking door de entertainer.'];
        }

        return $this->liveCheck($integration);
    }

    /**
     * @return array<int, string>
     */
    private function missingRequiredFields(EntertainerIntegration $integration): array
    {
        $credentials = $integration->credentials ?? [];
        $settings = $integration->settings ?? [];

        return collect($this->requiredFields($integration->provider))
            ->filter(function (string $field) use ($credentials, $settings): bool {
                return blank($credentials[$field] ?? $settings[$field] ?? null);
            })
            ->values()
            ->all();
    }

    /**
     * @return array<int, string>
     */
    private function requiredFields(IntegrationProvider $provider): array
    {
        return match ($provider) {
            IntegrationProvider::Moneybird => ['api_token', 'administration_id'],
            IntegrationProvider::Mollie => ['api_key'],
            IntegrationProvider::Stripe => ['secret_key'],
            IntegrationProvider::PayPal => ['client_id', 'client_secret'],
            IntegrationProvider::PayNl => ['api_token', 'service_id'],
            IntegrationProvider::Rabobank => ['refresh_token', 'merchant_id'],
            IntegrationProvider::Postmark => ['server_token', 'from_email'],
            IntegrationProvider::Pushover => ['app_token', 'user_key'],
            IntegrationProvider::GoogleCalendar,
            IntegrationProvider::OutlookCalendar => ['client_id', 'client_secret', 'refresh_token', 'calendar_id'],
            default => ['administration_id'],
        };
    }

    /**
     * @return array{status: string, message: string}
     */
    private function liveCheck(EntertainerIntegration $integration): array
    {
        $credentials = $integration->credentials ?? [];
        $settings = $integration->settings ?? [];

        $successful = match ($integration->provider) {
            IntegrationProvider::Mollie => Http::withToken((string) ($credentials['api_key'] ?? ''))->get('https://api.mollie.com/v2/profiles')->successful(),
            IntegrationProvider::Stripe => Http::withToken((string) ($credentials['secret_key'] ?? ''))->get('https://api.stripe.com/v1/account')->successful(),
            IntegrationProvider::Moneybird => Http::withToken((string) ($credentials['api_token'] ?? ''))->get('https://moneybird.com/api/v2/'.($settings['administration_id'] ?? '').'/administration.json')->successful(),
            IntegrationProvider::Postmark => Http::withHeaders(['X-Postmark-Server-Token' => (string) ($credentials['server_token'] ?? '')])->get('https://api.postmarkapp.com/server')->successful(),
            IntegrationProvider::Pushover => Http::asForm()->post('https://api.pushover.net/1/users/validate.json', [
                'token' => $credentials['app_token'] ?? null,
                'user' => $credentials['user_key'] ?? null,
            ])->successful(),
            default => true,
        };

        return $successful
            ? ['status' => 'ready', 'message' => $integration->provider->label().' is live bereikbaar.']
            : ['status' => 'connection_failed', 'message' => $integration->provider->label().' kon niet worden gevalideerd.'];
    }
}
