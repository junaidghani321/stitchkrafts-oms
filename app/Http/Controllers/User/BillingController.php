<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\Order;
use App\Services\BillingService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

/**
 * The customer's own billing page. Read-only apart from paying — generating
 * invoices is an admin act.
 */
class BillingController extends Controller
{
    public function __construct(private readonly BillingService $billing) {}

    public function index(Request $request): View
    {
        $customerId = $request->user()->id;

        $invoices = Invoice::forCustomer($customerId)->forIndex()->get();

        return view('user.billing.index', [
            'totals' => $this->billing->totals($customerId),
            'invoices' => $this->presentInvoices($invoices),
            'ungenerated' => $this->presentUninvoiced($this->billing->uninvoicedOrders($customerId)),
        ]);
    }

    public function show(Invoice $invoice): View
    {
        $this->authorize('view', $invoice);

        return view('user.billing.show', [
            'invoice' => $invoice->load(['customer', 'items.order', 'payments']),
            'role' => 'user',
        ]);
    }

    /** keys: no, date, payable, status, paid_date (no customer column here) */
    private function presentInvoices($invoices)
    {
        return $invoices->map(fn (Invoice $invoice) => [
            'id' => $invoice->id,
            'no' => $invoice->invoice_number,
            'date' => $invoice->issued_date->format('d-m-Y'),
            'payable' => $invoice->total,
            'status' => $invoice->payment_status->badge(),
            'paid_date' => $invoice->paid_at?->format('d-m-Y'),
        ]);
    }

    /** keys: no, order_date, sent_date, design, price */
    private function presentUninvoiced($orders)
    {
        return $orders->map(fn (Order $order) => [
            'id' => $order->id,
            'no' => $order->order_number,
            'order_date' => $order->created_at->format('d-m-Y'),
            'sent_date' => ($order->delivered_at ?? $order->completed_at)?->format('d-m-Y') ?? '—',
            'design' => $order->design_name,
            'price' => $order->display_price,
        ]);
    }
}
