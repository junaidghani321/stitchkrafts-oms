<?php

namespace App\Notifications;

use App\Models\OrderMessage;

/**
 * Sent to the customer ("your revision has been picked up") and to the
 * employee who now owns it.
 */
class RevisionAssigned extends OrderNotification
{
    public function __construct(
        private readonly OrderMessage $revision,
        private readonly bool $forCustomer = true,
    ) {}

    protected function preferenceKey(object $notifiable): ?string
    {
        return 'revision_update';
    }

    protected function text(): string
    {
        if ($this->forCustomer) {
            return "Your revision {$this->revision->revision_number} has been picked up";
        }

        $order = $this->revision->order?->order_number ?? 'an order';

        return "You have been assigned revision {$this->revision->revision_number} on {$order}";
    }

    protected function orderId(): ?int
    {
        return $this->revision->order_id;
    }
}
