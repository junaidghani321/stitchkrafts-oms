<?php

namespace App\Notifications;

use App\Models\Order;
use App\Models\Quote;

/**
 * Sent to staff when a quote is accepted — the resulting order needs picking
 * up — and to the customer as confirmation.
 */
class QuoteApproved extends OrderNotification
{
    public function __construct(
        private readonly Quote $quote,
        private readonly Order $order,
        private readonly bool $forCustomer = false,
    ) {}

    protected function preferenceKey(object $notifiable): ?string
    {
        return 'quote_alert';
    }

    protected function text(): string
    {
        return $this->forCustomer
            ? "Quote {$this->quote->quote_number} accepted — order {$this->order->order_number} has been created"
            : "Quote {$this->quote->quote_number} was accepted and became order {$this->order->order_number}";
    }

    /** Points at the new order — that is where the work now lives. */
    protected function orderId(): ?int
    {
        return $this->order->id;
    }
}
