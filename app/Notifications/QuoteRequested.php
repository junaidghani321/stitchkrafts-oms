<?php

namespace App\Notifications;

use App\Models\Quote;

/** Sent to admins and managers so a price enquiry does not sit unanswered. */
class QuoteRequested extends OrderNotification
{
    public function __construct(private readonly Quote $quote) {}

    protected function preferenceKey(object $notifiable): ?string
    {
        return 'quote_alert';
    }

    protected function text(): string
    {
        return "Quote {$this->quote->quote_number} was requested by "
            .($this->quote->customer?->name ?? 'a customer').' — awaiting pricing';
    }

    protected function orderId(): ?int
    {
        return null;
    }
}
