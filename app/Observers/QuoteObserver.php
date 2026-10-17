<?php

namespace App\Observers;

use App\Models\Quote;

class QuoteObserver
{
    /**
     * Quote numbers use the type-specific quote prefix (ADDQ / ADVQ / ADPQ)
     * over the row's own auto-increment id — the same race-free approach as
     * order numbers, on a separate sequence.
     */
    public function created(Quote $quote): void
    {
        if ($quote->quote_number === null) {
            $quote->quote_number = $quote->type->quotePrefix().'-'.$quote->id;
            $quote->saveQuietly();
        }
    }
}
