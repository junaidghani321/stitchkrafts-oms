<?php

namespace App\Notifications;

use App\Models\Order;

/**
 * Sent to admins and managers when a customer places an order, and to the
 * customer as their own confirmation.
 */
class OrderPlaced extends OrderNotification
{
    public function __construct(
        private readonly Order $order,
        private readonly bool $forCustomer = false,
    ) {}

    protected function preferenceKey(object $notifiable): ?string
    {
        // Same class, two audiences: the customer's own confirmation and
        // the staff alert are separate switches.
        return $this->forCustomer ? 'order_placed' : 'new_order_alert';
    }

    protected function text(): string
    {
        return $this->forCustomer
            ? "Order {$this->order->order_number} was received and queued"
            : "Order {$this->order->order_number} was placed by ".($this->order->customer?->name ?? 'a customer');
    }

    protected function orderId(): ?int
    {
        return $this->order->id;
    }
}
