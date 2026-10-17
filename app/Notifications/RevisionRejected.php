<?php

namespace App\Notifications;

use App\Models\OrderMessage;

/** Sent to the customer when a revision request is turned down. */
class RevisionRejected extends OrderNotification
{
    public function __construct(private readonly OrderMessage $revision) {}

    protected function preferenceKey(object $notifiable): ?string
    {
        return 'revision_update';
    }

    protected function text(): string
    {
        return "Revision {$this->revision->revision_number} was not accepted";
    }

    protected function orderId(): ?int
    {
        return $this->revision->order_id;
    }
}
