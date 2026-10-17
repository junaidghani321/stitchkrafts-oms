<?php

namespace App\Notifications;

use App\Models\Order;

/**
 * Sent to the customer when the work is finished, and to staff as a
 * "this one is through production" signal.
 */
class OrderCompleted extends OrderNotification
{
    public function __construct(
        private readonly Order $order,
        private readonly bool $forCustomer = true,
    ) {}

    protected function preferenceKey(object $notifiable): ?string
    {
        return 'order_completed';
    }

    protected function text(): string
    {
        return $this->forCustomer
            ? "Order {$this->order->order_number} is complete — files are ready"
            : "Order {$this->order->order_number} was completed";
    }

    protected function orderId(): ?int
    {
        return $this->order->id;
    }
}
