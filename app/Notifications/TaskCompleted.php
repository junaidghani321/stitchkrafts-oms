<?php

namespace App\Notifications;

use App\Models\OrderTask;

/** Sent to admins and managers when an employee finishes a task. */
class TaskCompleted extends OrderNotification
{
    public function __construct(private readonly OrderTask $task) {}

    protected function preferenceKey(object $notifiable): ?string
    {
        return 'task_update';
    }

    protected function text(): string
    {
        $who = $this->task->employee?->name ?? 'An employee';
        $order = $this->task->order?->order_number ?? 'an order';
        $sla = $this->task->sla();

        return "{$who} completed {$order} "
            .($sla->isBreached()
                ? "{$sla->formattedDelta()} over the allowed time"
                : "{$sla->formattedDelta()} inside the allowed time");
    }

    protected function orderId(): ?int
    {
        return $this->task->order_id;
    }
}
