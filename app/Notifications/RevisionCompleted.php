<?php

namespace App\Notifications;

use App\Models\OrderMessage;

/** Sent to the customer when their requested change has been made. */
class RevisionCompleted extends OrderNotification
{
    public function __construct(private readonly OrderMessage $revision) {}

    protected function preferenceKey(object $notifiable): ?string
    {
        return 'revision_update';
    }

    protected function text(): string
    {
        $order = $this->revision->order?->order_number ?? 'your order';

        return "Revision {$this->revision->revision_number} on {$order} has been completed";
    }

    protected function orderId(): ?int
    {
        return $this->revision->order_id;
    }
}
