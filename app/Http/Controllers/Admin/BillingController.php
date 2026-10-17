<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\Order;
use App\Models\Payment;
use App\Services\BillingService;
use App\Support\Money;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class BillingController extends Controller
{
    public function __construct(private readonly BillingService $billing) {}

    public function index(): View
    {
        $this->authorize('viewAny', Invoice::class);

        $invoices = Invoice::with('customer:id,name,type')->forIndex()->get();

        return view('admin.billing.index', [
            'totals' => $this->billing->totals(),
            'invoices' => $this->presentInvoices($invoices),
            'ungenerated' => $this->presentUninvoiced($this->billing->uninvoicedOrders()),
        ]);
    }

    /**
     * Bill the ticked orders. With none ticked, bill everything outstanding —
     * that is what the "Generate Invoice" tile does.
     */
    public function generate(Request $request): RedirectResponse
    {
        $this->authorize('create', Invoice::class);

        $ids = array_filter((array) $request->input('orders', []));

        $orders = $ids === []
            ? $this->billing->uninvoicedOrders()
            : $this->billing->uninvoicedOrders()->whereIn('id', $ids);

        if ($orders->isEmpty()) {
            return back()->with('status', 'There is nothing left to invoice.');
        }

        $invoices = $this->billing->generateInvoices($orders, $request->user());

        return back()->with('status', $invoices->count() === 1
            ? "Invoice {$invoices->first()->invoice_number} raised for ".$orders->count().' order(s).'
            : $invoices->count().' invoices raised across '.$orders->count().' orders.');
    }

    public function show(Invoice $invoice): View
    {
        $this->authorize('view', $invoice);

        return view('admin.billing.show', [
            'invoice' => $invoice->load(['customer', 'items.order', 'payments.recordedBy', 'createdBy']),
            'role' => 'admin',
        ]);
    }

    /** Record money taken outside the system — bank transfer, cash, cheque. */
    public function recordPayment(Request $request, Invoice $invoice): RedirectResponse
    {
        $this->authorize('recordPayment', $invoice);

        $data = $request->validate([
            'amount' => ['required', 'numeric', 'min:0.01', 'max:99999999'],
            'reference' => ['nullable', 'string', 'max:100'],
        ]);

        $cents = Money::toCents($data['amount']);

        // Never let a manual entry overshoot the balance — that would make the
        // invoice show more paid than it was ever worth.
        if ($cents > $invoice->outstanding_cents) {
            return back()->withErrors([
                'amount' => 'That is more than the outstanding balance of $ '.Money::format($invoice->outstanding_cents).'.',
            ]);
        }

        $this->billing->recordPayment($invoice, $cents, Payment::METHOD_MANUAL, $request->user(), [
            'reference' => $data['reference'] ?? null,
        ]);

        return back()->with('status', 'Payment recorded against '.$invoice->invoice_number.'.');
    }

    /*
    |--------------------------------------------------------------------------
    | Row shapes the existing Blade expects
    |--------------------------------------------------------------------------
    */

    /** keys: no, customer, date, payable, status, paid_date */
    private function presentInvoices($invoices)
    {
        return $invoices->map(fn (Invoice $invoice) => [
            'id' => $invoice->id,
            'no' => $invoice->invoice_number,
            'customer' => $invoice->customer?->name ?? '—',
            'date' => $invoice->issued_date->format('d-m-Y'),
            'payable' => $invoice->total,
            // The view uses the raw value as a CSS class and ucfirst()s it as
            // the label, so it must stay one of the badge names.
            'status' => $invoice->payment_status->badge(),
            'paid_date' => $invoice->paid_at?->format('d-m-Y'),
        ]);
    }

    /** keys: no, customer, order_date, sent_date, design, price */
    private function presentUninvoiced($orders)
    {
        return $orders->map(fn (Order $order) => [
            'id' => $order->id,
            'no' => $order->order_number,
            'customer' => $order->customer?->name ?? '—',
            'order_date' => $order->created_at->format('d-m-Y'),
            'sent_date' => ($order->delivered_at ?? $order->completed_at)?->format('d-m-Y') ?? '—',
            'design' => $order->design_name,
            'price' => $order->display_price,
        ]);
    }
}
