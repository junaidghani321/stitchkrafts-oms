<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\Invoice;
use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use App\Support\Money;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class BillingService
{
    /**
     * Delivered work that has not been billed yet.
     *
     * "Not billed" is derived from the absence of an invoice_items row rather
     * than a flag on the order — one fact, one place, and it cannot drift out
     * of step with the invoices themselves.
     */
    public function uninvoicedOrders(?int $customerId = null): Collection
    {
        return Order::query()
            ->whereIn('status', [OrderStatus::Completed->value, OrderStatus::Delivered->value])
            ->whereNotNull('price')
            ->where('price', '>', 0)
            ->whereDoesntHave('invoiceItem')
            ->when($customerId, fn (Builder $q) => $q->where('customer_id', $customerId))
            ->with('customer:id,name,type')
            ->orderBy('customer_id')
            ->latest('completed_at')
            ->get();
    }

    /**
     * Bill the given orders.
     *
     * Orders are grouped by customer and one invoice is raised per customer —
     * selecting a mixed batch on the admin screen is normal, and silently
     * putting two customers on one bill would be worse than either erroring
     * or splitting.
     *
     * @param  Collection<int, Order>  $orders
     * @return Collection<int, Invoice>
     */
    public function generateInvoices(Collection $orders, User $actor): Collection
    {
        return DB::transaction(function () use ($orders, $actor) {
            return $orders
                ->groupBy('customer_id')
                ->map(fn (Collection $forCustomer, $customerId) => $this->createInvoice((int) $customerId, $forCustomer, $actor))
                ->filter()
                ->values();
        });
    }

    private function createInvoice(int $customerId, Collection $orders, User $actor): ?Invoice
    {
        if ($orders->isEmpty()) {
            return null;
        }

        $invoice = Invoice::create([
            'customer_id' => $customerId,
            'created_by_id' => $actor->id,
            'issued_date' => today(),
            // 14 days is a plain default; there is no agreed-terms field yet.
            'due_date' => today()->addDays(14),
            'currency' => config('billing.currency', 'usd'),
        ]);

        $total = 0;

        foreach ($orders as $order) {
            $cents = Money::toCents($order->price);

            $invoice->items()->create([
                // Copied, not read through the relation: an invoice records
                // what was agreed at the time, so later edits to the order
                // must not rewrite the bill.
                'order_id' => $order->id,
                'description' => $order->order_number.' — '.$order->design_name,
                'amount_cents' => $cents,
            ]);

            $total += $cents;
        }

        $invoice->forceFill([
            'subtotal_cents' => $total,
            'total_cents' => $total,
        ])->save();

        return $invoice->refresh();
    }

    /**
     * Record money received.
     *
     * Handles partial payments: the invoice only flips to paid once the sum
     * of successful payments covers the total.
     */
    public function recordPayment(
        Invoice $invoice,
        int $amountCents,
        string $method = Payment::METHOD_MANUAL,
        ?User $actor = null,
        array $attributes = [],
    ): Payment {
        return DB::transaction(function () use ($invoice, $amountCents, $method, $actor, $attributes) {
            $payment = $invoice->payments()->create($attributes + [
                'recorded_by_id' => $actor?->id,
                'method' => $method,
                'status' => Payment::STATUS_SUCCEEDED,
                'amount_cents' => $amountCents,
                'currency' => $invoice->currency,
                'paid_at' => now(),
            ]);

            $this->settle($invoice);

            return $payment;
        });
    }

    /**
     * Recompute an invoice's paid total and status from its payments, and
     * push the result down onto the orders it covers.
     *
     * Derived rather than incremented, so a replayed webhook or a corrected
     * payment row can never leave the invoice showing a total that its own
     * payments do not add up to.
     */
    public function settle(Invoice $invoice): Invoice
    {
        $paid = (int) $invoice->successfulPayments()->sum('amount_cents');

        $status = match (true) {
            $paid <= 0 => PaymentStatus::Unpaid,
            $paid >= $invoice->total_cents => PaymentStatus::Paid,
            default => PaymentStatus::Partial,
        };

        $invoice->forceFill([
            'paid_cents' => $paid,
            'payment_status' => $status,
            'paid_at' => $status === PaymentStatus::Paid ? ($invoice->paid_at ?? now()) : null,
        ])->save();

        // The orders on this invoice inherit its payment state, which is what
        // the order screens and the dashboards read.
        Order::whereIn('id', $invoice->items()->pluck('order_id'))
            ->update(['payment_status' => $status->value]);

        return $invoice;
    }

    /**
     * The three figures at the top of the billing screen.
     *
     * Scoped to one customer for their own page, unscoped for admin.
     */
    public function totals(?int $customerId = null): array
    {
        $base = fn () => Invoice::query()->when($customerId, fn ($q) => $q->where('customer_id', $customerId));

        $billed = (int) (clone $base())->sum('total_cents');
        $paid = (int) (clone $base())->sum('paid_cents');

        return [
            'business' => Money::toDecimal($billed),
            'paid' => Money::toDecimal($paid),
            // What is actually owed, not simply billed-minus-paid across
            // refunded rows.
            'payable' => Money::toDecimal(max(0, $billed - $paid)),
        ];
    }
}
