<?php

namespace App\Services;

use App\Enums\PaymentProvider;
use App\Models\BookingRequest;
use App\Services\Integrations\InvoiceIntegrationService;
use App\Services\Integrations\PaymentCheckoutService;
use Illuminate\Support\Str;

class SelfBillingIntegrationService
{
    public function __construct(
        private readonly InvoiceIntegrationService $invoices,
        private readonly PaymentCheckoutService $payments,
    ) {}

    public function prepareAfterAcceptance(BookingRequest $bookingRequest): BookingRequest
    {
        $bookingRequest->loadMissing('entertainer');

        $invoiceReference = 'INV-'.$bookingRequest->id.'-'.now()->format('Ymd');
        $paymentReference = 'PAY-'.$bookingRequest->id.'-'.Str::upper(Str::random(6));
        $paymentProvider = $bookingRequest->payment_provider
            ? PaymentProvider::tryFrom($bookingRequest->payment_provider)
            : null;

        $bookingRequest->update([
            'invoice_status' => 'ready_for_entertainer',
            'invoice_reference' => $invoiceReference,
            'invoice_url' => route('customer-portal.download', ['bookingRequest' => $bookingRequest, 'type' => 'invoice']),
            'invoice_generated_at' => now(),
            'payment_reference' => $paymentReference,
            'payment_instruction_sent_at' => now(),
        ]);

        $bookingRequest = $this->invoices->createInvoiceInstruction($bookingRequest->refresh());

        return $this->payments->createCheckout($bookingRequest);
    }
}
