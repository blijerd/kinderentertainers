<?php

namespace App\Http\Controllers\Webhooks;

use App\Enums\PaymentProvider;
use App\Http\Controllers\Controller;
use App\Services\Integrations\PaymentCheckoutService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class PaymentWebhookController extends Controller
{
    public function __invoke(Request $request, string $provider, PaymentCheckoutService $payments): Response
    {
        $paymentProvider = PaymentProvider::tryFrom($provider);

        abort_unless($paymentProvider, Response::HTTP_NOT_FOUND);

        $externalId = $request->input('id')
            ?? $request->input('payment_id')
            ?? $request->input('resource.id')
            ?? $request->input('data.object.id')
            ?? $request->input('transactionId');

        abort_unless(is_string($externalId) && $externalId !== '', Response::HTTP_UNPROCESSABLE_ENTITY);

        $payments->markWebhookPayment($paymentProvider, $externalId, $request->input('status') ?? $request->input('eventType'));

        return response('', Response::HTTP_NO_CONTENT);
    }
}
