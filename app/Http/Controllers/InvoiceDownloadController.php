<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;

/**
 * The only way to get an invoice as a PDF.
 *
 * Sits outside every role-prefixed group for the same reason as
 * FileDownloadController: `user-access` matches a single role, so a route
 * inside one group would be unreachable by the others. InvoicePolicy@view
 * already answers "may this person see this invoice" — admin sees all, a
 * customer sees only their own — so it is reused verbatim here.
 */
class InvoiceDownloadController extends Controller
{
    public function __invoke(Invoice $invoice): Response
    {
        $this->authorize('view', $invoice);

        $invoice->load(['customer', 'items.order', 'payments.recordedBy', 'createdBy']);

        $pdf = Pdf::loadView('billing.pdf.invoice', [
            'invoice' => $invoice,
        ])->setPaper('a4');

        // The invoice number is the filename people will look for in their
        // downloads folder months later.
        return $pdf->download($invoice->invoice_number.'.pdf');
    }
}
