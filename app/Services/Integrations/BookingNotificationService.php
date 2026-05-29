<?php

namespace App\Services\Integrations;

use App\Enums\IntegrationProvider;
use App\Models\BookingRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;

class BookingNotificationService
{
    public function sendReminder(BookingRequest $bookingRequest, string $subject, string $body): void
    {
        $bookingRequest->loadMissing('entertainer.integrations');

        $sent = false;

        if ($bookingRequest->email) {
            Mail::raw($body, function ($message) use ($bookingRequest, $subject): void {
                $message->to($bookingRequest->email)->subject($subject);
            });
            $sent = true;
        }

        foreach ($bookingRequest->entertainer?->integrations ?? [] as $integration) {
            if (! $integration->enabled) {
                continue;
            }

            if ($integration->provider === IntegrationProvider::Postmark) {
                $sent = $this->sendPostmark($integration->credentials ?? [], $integration->settings ?? [], $bookingRequest, $subject, $body) || $sent;
            }

            if ($integration->provider === IntegrationProvider::Pushover) {
                $sent = $this->sendPushover($integration->credentials ?? [], $subject, $body) || $sent;
            }
        }

        $bookingRequest->update([
            'last_notification_sent_at' => now(),
            'last_notification_status' => $sent ? 'sent' : 'no_channel',
        ]);
    }

    /**
     * @param  array<string, mixed>  $credentials
     * @param  array<string, mixed>  $settings
     */
    private function sendPostmark(array $credentials, array $settings, BookingRequest $bookingRequest, string $subject, string $body): bool
    {
        $response = Http::withHeaders([
            'X-Postmark-Server-Token' => (string) ($credentials['server_token'] ?? ''),
        ])->post('https://api.postmarkapp.com/email', [
            'From' => $settings['from_email'] ?? config('mail.from.address'),
            'To' => $bookingRequest->entertainer?->user?->email ?? $bookingRequest->email,
            'Subject' => $subject,
            'TextBody' => $body,
            'MessageStream' => $settings['message_stream'] ?? 'outbound',
        ]);

        return $response->successful();
    }

    /**
     * @param  array<string, mixed>  $credentials
     */
    private function sendPushover(array $credentials, string $subject, string $body): bool
    {
        $response = Http::asForm()->post('https://api.pushover.net/1/messages.json', [
            'token' => $credentials['app_token'] ?? null,
            'user' => $credentials['user_key'] ?? null,
            'title' => $subject,
            'message' => $body,
        ]);

        return $response->successful();
    }
}
