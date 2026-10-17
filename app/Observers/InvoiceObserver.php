<?php

namespace App\Observers;

use App\Models\Invoice;

class InvoiceObserver
{
    /**
     * INV-2026-042 — year of issue plus the row's own id, zero-padded to
     * three digits to match the existing numbering.
     *
     * Derived from the primary key for the same reason order numbers are:
     * AUTO_INCREMENT is already an atomic counter, so there is no race and no
     * sequence table to maintain.
     */
    public function created(Invoice $invoice): void
    {
        if ($invoice->invoice_number === null) {
            $year = $invoice->issued_date?->format('Y') ?? now()->format('Y');

            $invoice->invoice_number = 'INV-'.$year.'-'.str_pad((string) $invoice->id, 3, '0', STR_PAD_LEFT);
            $invoice->saveQuietly();
        }
    }
}
