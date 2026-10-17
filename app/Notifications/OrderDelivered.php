<?php

namespace App\Notifications;

use App\Models\Order;

/** Sent to the customer when the finished files are released to them. */
class OrderDelivered extends OrderNotification
{
    public function __construct(private readonly Order $order) {}

    protected function preferenceKey(object $notifiable): ?string
    {
        return 'order_completed';
    }

    protected function text(): string
    {
        return "Order {$this->order->order_number} was completed and delivered";
    }

    protected function orderId(): ?int
    {
        return $this->order->id;
    }
}
