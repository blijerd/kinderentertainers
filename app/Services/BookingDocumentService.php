<?php

namespace App\Services;

use App\Models\BookingRequest;

class BookingDocumentService
{
    public function pdf(BookingRequest $bookingRequest, string $type): string
    {
        $title = match ($type) {
            'quote' => 'Offerte',
            'confirmation' => 'Boekingsbevestiging',
            'invoice' => 'Factuurinstructie',
            'cancellation' => 'Annuleringsbewijs',
            default => 'Boekingsdocument',
        };

        $lines = [
            $title.' '.$bookingRequest->id,
            'Klant: '.$bookingRequest->name,
            'Entertainer: '.($bookingRequest->entertainer?->name ?? 'Nog te kiezen'),
            'Evenement: '.$bookingRequest->event_date->format('d-m-Y').' '.$bookingRequest->start_time->format('H:i').'-'.$bookingRequest->end_time->format('H:i'),
            'Locatie: '.$bookingRequest->address.', '.$bookingRequest->postal_code.' '.$bookingRequest->city,
            'Totaal: '.($bookingRequest->quote_total_cents !== null ? 'EUR '.number_format($bookingRequest->quote_total_cents / 100, 2, ',', '.') : '-'),
            'Aanbetaling: '.($bookingRequest->deposit_cents !== null ? 'EUR '.number_format($bookingRequest->deposit_cents / 100, 2, ',', '.') : '-'),
            'Betaald: EUR '.number_format(((int) $bookingRequest->paid_cents) / 100, 2, ',', '.'),
            'Betaalstatus: '.($bookingRequest->payment_status ?: '-'),
            'Betaalprovider: '.($bookingRequest->payment_provider ?: '-'),
            'Betaallink: '.($bookingRequest->payment_checkout_url ?: '-'),
            'Factuur: entertainer factureert zelf',
            'Factuurstatus: '.($bookingRequest->invoice_status ?: '-'),
            'Factuurprovider: '.($bookingRequest->invoice_provider ?: '-'),
            'Factuurreferentie: '.($bookingRequest->invoice_reference ?: '-'),
            'Externe factuur-ID: '.($bookingRequest->invoice_external_id ?: '-'),
            'Betaalreferentie: '.($bookingRequest->payment_reference ?: '-'),
            'Contant betalen: '.($bookingRequest->cash_payment_allowed ? 'toegestaan' : 'niet toegestaan'),
            'Akkoord op: '.($bookingRequest->quote_accepted_at?->format('d-m-Y H:i') ?? '-'),
            'Akkoord door: '.($bookingRequest->quote_acceptance_name ?: '-'),
            'Voorwaardenversie: '.($bookingRequest->quote_terms_version ?: '-'),
            'Overeenkomst hash: '.($bookingRequest->agreement_hash ?: '-'),
        ];

        return $this->minimalPdf($title, $lines);
    }

    /**
     * Generate a compact single-page PDF without an external dependency.
     *
     * @param  array<int, string>  $lines
     */
    private function minimalPdf(string $title, array $lines): string
    {
        $content = "BT\n/F1 18 Tf\n50 790 Td\n(".$this->escape($title).") Tj\n/F1 10 Tf\n0 -28 Td\n";

        foreach ($lines as $line) {
            $content .= '('.$this->escape($line).") Tj\n0 -16 Td\n";
        }

        $content .= "ET\n";
        $objects = [
            "1 0 obj\n<< /Type /Catalog /Pages 2 0 R >>\nendobj\n",
            "2 0 obj\n<< /Type /Pages /Kids [3 0 R] /Count 1 >>\nendobj\n",
            "3 0 obj\n<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] /Resources << /Font << /F1 4 0 R >> >> /Contents 5 0 R >>\nendobj\n",
            "4 0 obj\n<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>\nendobj\n",
            "5 0 obj\n<< /Length ".strlen($content)." >>\nstream\n{$content}endstream\nendobj\n",
        ];

        $pdf = "%PDF-1.4\n";
        $offsets = [0];

        foreach ($objects as $object) {
            $offsets[] = strlen($pdf);
            $pdf .= $object;
        }

        $xref = strlen($pdf);
        $pdf .= "xref\n0 ".(count($objects) + 1)."\n0000000000 65535 f \n";

        foreach (array_slice($offsets, 1) as $offset) {
            $pdf .= str_pad((string) $offset, 10, '0', STR_PAD_LEFT)." 00000 n \n";
        }

        return $pdf."trailer\n<< /Size ".(count($objects) + 1)." /Root 1 0 R >>\nstartxref\n{$xref}\n%%EOF";
    }

    private function escape(string $value): string
    {
        return str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $value);
    }
}
