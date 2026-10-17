<?php

namespace App\Notifications;

use App\Models\Order;
use App\Models\User;

/**
 * Sent to admins and managers when an employee submits the last outstanding
 * task on an order. Nothing reaches the customer until one of them approves
 * it, so this is the message that has to be acted on — it carries the
 * approver's own link to the order.
 */
class OrderAwaitingApproval extends OrderNotification
{
    public function __construct(
        private readonly Order $order,
        private readonly ?User $submittedBy = null,
    ) {}

    protected function preferenceKey(object $notifiable): ?string
    {
        return 'order_approval';
    }

    protected function text(): string
    {
        $who = $this->submittedBy?->name ?? 'An employee';

        return "{$who} submitted {$this->order->order_number} for final approval";
    }

    protected function actionLabel(): string
    {
        return 'Review and Approve';
    }

    protected function orderId(): ?int
    {
        return $this->order->id;
    }
}
