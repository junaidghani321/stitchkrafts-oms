<?php

namespace App\Notifications;

use App\Models\Order;

/** Sent to the customer once an admin or manager sets the price. */
class OrderPriced extends OrderNotification
{
    public function __construct(private readonly Order $order) {}

    protected function preferenceKey(object $notifiable): ?string
    {
        return 'order_priced';
    }

    protected function text(): string
    {
        return "Order {$this->order->order_number} has been priced at $ ".number_format((float) $this->order->price, 2);
    }

    protected function orderId(): ?int
    {
        return $this->order->id;
    }
}
