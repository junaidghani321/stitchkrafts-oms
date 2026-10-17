<?php

namespace App\Notifications;

use App\Models\Quote;

/** Sent to staff when a customer declines a quote. */
class QuoteRejected extends OrderNotification
{
    public function __construct(private readonly Quote $quote) {}

    protected function preferenceKey(object $notifiable): ?string
    {
        return 'quote_alert';
    }

    protected function text(): string
    {
        return "Quote {$this->quote->quote_number} was declined";
    }

    protected function orderId(): ?int
    {
        return null;
    }
}
