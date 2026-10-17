<?php

namespace App\Policies;

use App\Enums\QuoteStatus;
use App\Models\Quote;
use App\Models\User;

class QuotePolicy
{
    public function view(User $user, Quote $quote): bool
    {
        return match (true) {
            $user->isAdminOrManager() => true,
            $user->isCustomer() => $quote->customer_id === $user->id,
            // Employees have no part in quoting — a quote is a commercial
            // conversation between the customer and admin/manager. They pick
            // the work up once it has become an order.
            $user->isEmployee() => false,
            default => false,
        };
    }

    /** Only while nobody has priced it yet. */
    public function update(User $user, Quote $quote): bool
    {
        return match (true) {
            $user->isAdminOrManager() => ! $quote->isConverted(),
            $user->isCustomer() => $quote->customer_id === $user->id
                                         && $quote->status === QuoteStatus::Pending,
            default => false,
        };
    }

    /** Pricing is a commercial decision: admin and manager only. */
    public function price(User $user, Quote $quote): bool
    {
        return $user->isAdminOrManager()
            && ! $quote->isConverted()
            && $quote->status->isOpen();
    }

    /**
     * Accepting is the customer's call — it is their money. Admin and manager
     * can accept on their behalf, which is how phone orders get taken.
     */
    public function decide(User $user, Quote $quote): bool
    {
        // hasExpired() is checked as well as the status, not instead of it.
        // QuoteService::expireStale() only runs when a quote INDEX is loaded,
        // so a quote reached directly — bookmark, history, back button — can
        // still read `quoted` long after its valid_until passed. Without the
        // date check the customer could accept last month's price today.
        if (! $quote->status->isDecidable() || $quote->isConverted() || $quote->hasExpired()) {
            return false;
        }

        return $user->isAdminOrManager() || $quote->customer_id === $user->id;
    }

    public function delete(User $user, Quote $quote): bool
    {
        return $user->isAdmin() && ! $quote->isConverted();
    }
}
