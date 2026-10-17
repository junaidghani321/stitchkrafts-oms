<?php

namespace App\Notifications;

use App\Models\OrderTask;

/**
 * Sent to the employee the work was handed to.
 *
 * Honours the "Notify the employee as soon as the task is assigned" switch on
 * the assign dialog — the caller checks $task->notify before sending.
 */
class TaskAssigned extends OrderNotification
{
    public function __construct(private readonly OrderTask $task) {}

    protected function preferenceKey(object $notifiable): ?string
    {
        return 'task_assigned';
    }

    protected function text(): string
    {
        $order = $this->task->order?->order_number ?? 'an order';
        $design = $this->task->order?->design_name;

        return "You have been assigned {$order}".($design ? " — {$design}" : '')
            ." ({$this->task->duration_hours}h allowed)";
    }

    protected function orderId(): ?int
    {
        return $this->task->order_id;
    }
}
