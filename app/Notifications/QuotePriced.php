<?php

namespace App\Notifications;

use App\Models\Quote;

/** Sent to the customer: there is a price to accept or decline. */
class QuotePriced extends OrderNotification
{
    public function __construct(private readonly Quote $quote) {}

    protected function preferenceKey(object $notifiable): ?string
    {
        return 'quote_alert';
    }

    protected function text(): string
    {
        return "Quote {$this->quote->quote_number} has been priced at $ "
            .number_format((float) $this->quote->price, 2)
            .($this->quote->valid_until ? ' — valid until '.$this->quote->valid_until->format('d-m-Y') : '');
    }

    protected function orderId(): ?int
    {
        return null;
    }
}
