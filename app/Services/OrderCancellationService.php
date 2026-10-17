<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Enums\TaskStatus;
use App\Models\Order;
use App\Models\OrderActivity;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Ending an order.
 *
 * One place rather than a branch in each controller: cancelling is not just a
 * status write. Any open task has to be stood down too, or the employee keeps
 * a live job in their queue for work nobody wants any more — and the SLA
 * clock on that task keeps running against them.
 */
class OrderCancellationService
{
    public function cancel(Order $order, User $actor, ?string $reason = null): Order
    {
        return DB::transaction(function () use ($order, $actor, $reason) {
            $was = $order->status;

            $order->forceFill([
                'status' => OrderStatus::Cancelled->value,
                'cancelled_by_id' => $actor->id,
                'cancelled_at' => now(),
                'cancellation_reason' => $reason,
            ])->save();

            // Stand down anything still open. Completed tasks are left alone:
            // the work was really done and the employee's record should say so.
            $standDown = $order->tasks()->open()->get();

            foreach ($standDown as $task) {
                $task->forceFill(['status' => TaskStatus::Cancelled->value])->save();
            }

            OrderActivity::create([
                'order_id' => $order->id,
                'user_id' => $actor->id,
                'event' => OrderActivity::ORDER_CANCELLED,
                'description' => "Order cancelled from {$was->label()}"
                                 .($reason ? " — {$reason}" : ''),
                'properties' => [
                    'old' => $was->value,
                    'new' => OrderStatus::Cancelled->value,
                    'reason' => $reason,
                    'tasks_cancelled' => $standDown->pluck('id')->all(),
                ],
            ]);

            return $order;
        });
    }
}
