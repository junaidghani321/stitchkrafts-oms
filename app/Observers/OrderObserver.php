<?php

namespace App\Observers;

use App\Models\Order;
use App\Models\OrderActivity;

class OrderObserver
{
    /**
     * Stamp the order number from the row's own primary key.
     *
     * MySQL's AUTO_INCREMENT is already an atomic, lock-free counter, so
     * deriving the number from it is race-free by construction — no sequence
     * table, no SELECT ... FOR UPDATE, no dependency on a queue worker.
     *
     * The cost is one extra UPDATE per order. Gaps are possible when a
     * transaction rolls back; if gapless numbering is ever needed for
     * accounting, this is the place to revisit.
     */
    public function created(Order $order): void
    {
        if ($order->order_number === null) {
            $order->order_number = $order->type->orderPrefix().'-'.$order->id;
            $order->saveQuietly();
        }

        OrderActivity::create([
            'order_id' => $order->id,
            'user_id' => $order->created_by_id ?? $order->customer_id,
            'event' => OrderActivity::ORDER_CREATED,
            'description' => "Order {$order->order_number} was placed",
            'properties' => ['type' => $order->type->value],
        ]);
    }
}
