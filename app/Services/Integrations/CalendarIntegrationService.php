<?php

namespace App\Services\Integrations;

use App\Enums\BookingStatus;
use App\Enums\IntegrationProvider;
use App\Models\BookingRequest;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class CalendarIntegrationService
{
    public function syncBooking(BookingRequest $bookingRequest): BookingRequest
    {
        $bookingRequest->loadMissing('entertainer.integrations', 'skill');

        if (! $bookingRequest->entertainer) {
            return $bookingRequest;
        }

        $integration = $bookingRequest->entertainer->integrations
            ->where('enabled', true)
            ->first(fn ($integration): bool => in_array($integration->provider, [
                IntegrationProvider::GoogleCalendar,
                IntegrationProvider::OutlookCalendar,
            ], true));

        if (! $integration) {
            $bookingRequest->update([
                'calendar_synced_at' => now(),
                'calendar_sync_status' => 'no_calendar_integration',
            ]);

            return $bookingRequest->refresh();
        }

        if ($bookingRequest->status !== BookingStatus::Confirmed) {
            return $this->deleteBooking($bookingRequest, $integration);
        }

        $payload = match ($integration->provider) {
            IntegrationProvider::GoogleCalendar => $this->syncGoogle($bookingRequest, $integration->credentials ?? [], $integration->settings ?? []),
            IntegrationProvider::OutlookCalendar => $this->syncOutlook($bookingRequest, $integration->credentials ?? [], $integration->settings ?? []),
            default => null,
        };

        $bookingRequest->update([
            'calendar_external_id' => $payload['id'] ?? null,
            'calendar_synced_at' => now(),
            'calendar_sync_status' => $payload ? 'synced_'.$integration->provider->value : 'sync_failed',
        ]);

        return $bookingRequest->refresh();
    }

    /**
     * @param  array<string, mixed>  $credentials
     * @param  array<string, mixed>  $settings
     * @return array{id?: string|null}
     */
    private function syncGoogle(BookingRequest $bookingRequest, array $credentials, array $settings): array
    {
        $token = $this->googleAccessToken($credentials, $settings);
        $calendarId = rawurlencode((string) ($settings['calendar_id'] ?? 'primary'));
        $url = "https://www.googleapis.com/calendar/v3/calendars/{$calendarId}/events";

        $request = Http::withToken($token)->acceptJson();
        $response = $bookingRequest->calendar_external_id
            ? $request->patch($url.'/'.rawurlencode($bookingRequest->calendar_external_id), $this->googlePayload($bookingRequest))
            : $request->post($url, $this->googlePayload($bookingRequest));

        if (! $response->successful()) {
            throw new RuntimeException('Google Calendar event kon niet worden aangemaakt.');
        }

        return ['id' => $response->json('id')];
    }

    /**
     * @param  array<string, mixed>  $credentials
     * @param  array<string, mixed>  $settings
     * @return array{id?: string|null}
     */
    private function syncOutlook(BookingRequest $bookingRequest, array $credentials, array $settings): array
    {
        $token = $this->outlookAccessToken($credentials, $settings);
        $calendarId = $settings['calendar_id'] ?? null;
        $baseUrl = $calendarId
            ? 'https://graph.microsoft.com/v1.0/me/calendars/'.rawurlencode((string) $calendarId).'/events'
            : 'https://graph.microsoft.com/v1.0/me/events';

        $request = Http::withToken($token)->acceptJson();
        $response = $bookingRequest->calendar_external_id
            ? $request->patch('https://graph.microsoft.com/v1.0/me/events/'.rawurlencode($bookingRequest->calendar_external_id), $this->outlookPayload($bookingRequest))
            : $request->post($baseUrl, $this->outlookPayload($bookingRequest));

        if (! $response->successful()) {
            throw new RuntimeException('Outlook Calendar event kon niet worden aangemaakt.');
        }

        return ['id' => $response->json('id')];
    }

    /**
     * @param  array<string, mixed>  $credentials
     * @param  array<string, mixed>  $settings
     */
    private function googleAccessToken(array $credentials, array $settings): string
    {
        $response = Http::asForm()->post('https://oauth2.googleapis.com/token', [
            'client_id' => $settings['client_id'] ?? null,
            'client_secret' => $credentials['client_secret'] ?? null,
            'refresh_token' => $credentials['refresh_token'] ?? null,
            'grant_type' => 'refresh_token',
        ]);

        if (! $response->successful()) {
            throw new RuntimeException('Google access token kon niet worden opgehaald.');
        }

        return (string) $response->json('access_token');
    }

    /**
     * @param  array<string, mixed>  $credentials
     * @param  array<string, mixed>  $settings
     */
    private function outlookAccessToken(array $credentials, array $settings): string
    {
        $tenant = $settings['tenant_id'] ?? 'common';
        $response = Http::asForm()->post("https://login.microsoftonline.com/{$tenant}/oauth2/v2.0/token", [
            'client_id' => $settings['client_id'] ?? null,
            'client_secret' => $credentials['client_secret'] ?? null,
            'refresh_token' => $credentials['refresh_token'] ?? null,
            'grant_type' => 'refresh_token',
            'scope' => 'offline_access Calendars.ReadWrite',
        ]);

        if (! $response->successful()) {
            throw new RuntimeException('Outlook access token kon niet worden opgehaald.');
        }

        return (string) $response->json('access_token');
    }

    /**
     * @return array<string, mixed>
     */
    private function googlePayload(BookingRequest $bookingRequest): array
    {
        return [
            'summary' => 'Boeking: '.($bookingRequest->skill?->name ?? 'Kinderentertainment').' voor '.$bookingRequest->name,
            'location' => $bookingRequest->address.', '.$bookingRequest->postal_code.' '.$bookingRequest->city,
            'description' => $bookingRequest->message ?: 'Boeking via Kinderentertainers.nl',
            'start' => ['dateTime' => $this->dateTime($bookingRequest, 'start_time'), 'timeZone' => config('app.timezone')],
            'end' => ['dateTime' => $this->dateTime($bookingRequest, 'end_time'), 'timeZone' => config('app.timezone')],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function outlookPayload(BookingRequest $bookingRequest): array
    {
        return [
            'subject' => 'Boeking: '.($bookingRequest->skill?->name ?? 'Kinderentertainment').' voor '.$bookingRequest->name,
            'body' => ['contentType' => 'Text', 'content' => $bookingRequest->message ?: 'Boeking via Kinderentertainers.nl'],
            'start' => ['dateTime' => $this->dateTime($bookingRequest, 'start_time'), 'timeZone' => config('app.timezone')],
            'end' => ['dateTime' => $this->dateTime($bookingRequest, 'end_time'), 'timeZone' => config('app.timezone')],
            'location' => ['displayName' => $bookingRequest->address.', '.$bookingRequest->postal_code.' '.$bookingRequest->city],
        ];
    }

    private function dateTime(BookingRequest $bookingRequest, string $field): string
    {
        return Carbon::parse($bookingRequest->event_date->toDateString().' '.$bookingRequest->{$field}->format('H:i'))->toIso8601String();
    }

    private function deleteBooking(BookingRequest $bookingRequest, mixed $integration): BookingRequest
    {
        if (! $bookingRequest->calendar_external_id) {
            $bookingRequest->update([
                'calendar_synced_at' => now(),
                'calendar_sync_status' => 'calendar_delete_not_needed',
            ]);

            return $bookingRequest->refresh();
        }

        match ($integration->provider) {
            IntegrationProvider::GoogleCalendar => $this->deleteGoogle($bookingRequest, $integration->credentials ?? [], $integration->settings ?? []),
            IntegrationProvider::OutlookCalendar => $this->deleteOutlook($bookingRequest, $integration->credentials ?? [], $integration->settings ?? []),
            default => null,
        };

        $bookingRequest->update([
            'calendar_synced_at' => now(),
            'calendar_sync_status' => 'deleted_'.$integration->provider->value,
        ]);

        return $bookingRequest->refresh();
    }

    /**
     * @param  array<string, mixed>  $credentials
     * @param  array<string, mixed>  $settings
     */
    private function deleteGoogle(BookingRequest $bookingRequest, array $credentials, array $settings): void
    {
        $token = $this->googleAccessToken($credentials, $settings);
        $calendarId = rawurlencode((string) ($settings['calendar_id'] ?? 'primary'));

        $response = Http::withToken($token)
            ->delete("https://www.googleapis.com/calendar/v3/calendars/{$calendarId}/events/".rawurlencode($bookingRequest->calendar_external_id));

        if (! in_array($response->status(), [200, 204, 404, 410], true)) {
            throw new RuntimeException('Google Calendar event kon niet worden verwijderd.');
        }
    }

    /**
     * @param  array<string, mixed>  $credentials
     * @param  array<string, mixed>  $settings
     */
    private function deleteOutlook(BookingRequest $bookingRequest, array $credentials, array $settings): void
    {
        $token = $this->outlookAccessToken($credentials, $settings);

        $response = Http::withToken($token)
            ->delete('https://graph.microsoft.com/v1.0/me/events/'.rawurlencode($bookingRequest->calendar_external_id));

        if (! in_array($response->status(), [200, 202, 204, 404, 410], true)) {
            throw new RuntimeException('Outlook Calendar event kon niet worden verwijderd.');
        }
    }
}
