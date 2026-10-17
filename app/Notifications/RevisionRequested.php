<?php

namespace App\Notifications;

use App\Models\OrderMessage;

/** Sent to admins and managers so a revision does not sit unassigned. */
class RevisionRequested extends OrderNotification
{
    public function __construct(private readonly OrderMessage $revision) {}

    protected function preferenceKey(object $notifiable): ?string
    {
        return 'revision_update';
    }

    protected function text(): string
    {
        $order = $this->revision->order?->order_number ?? 'an order';

        return "{$this->revision->revision_number} was requested on {$order}"
            .($this->revision->priority->value === 'urgent' ? ' (urgent)' : '');
    }

    protected function orderId(): ?int
    {
        return $this->revision->order_id;
    }
}
