<?php

namespace App\Notifications;

use App\Models\Order;
use App\Models\User;

/**
 * Sent when an order is called off.
 *
 * Goes to both sides: staff need to stop work, and the customer needs written
 * confirmation that it really was cancelled. `forCustomer` only changes the
 * wording — a customer reads "your order", staff read who cancelled it.
 */
class OrderCancelled extends OrderNotification
{
    public function __construct(
        private readonly Order $order,
        private readonly ?User $cancelledBy = null,
        private readonly bool $forCustomer = false,
    ) {}

    protected function preferenceKey(object $notifiable): ?string
    {
        return 'order_cancelled';
    }

    protected function text(): string
    {
        if ($this->forCustomer) {
            return "Your order {$this->order->order_number} has been cancelled";
        }

        $who = $this->cancelledBy?->name;

        return "Order {$this->order->order_number} was cancelled"
               .($who ? " by {$who}" : '');
    }

    protected function orderId(): ?int
    {
        return $this->order->id;
    }
}
