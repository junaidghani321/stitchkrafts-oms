<?php

namespace App\Policies;

use App\Models\Invoice;
use App\Models\User;

/**
 * Billing is admin and customer only.
 *
 * Managers are deliberately excluded from money — that separation is already
 * explicit in the sidebar and is preserved here.
 */
class InvoicePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin() || $user->isCustomer();
    }

    public function view(User $user, Invoice $invoice): bool
    {
        return match (true) {
            $user->isAdmin() => true,
            $user->isCustomer() => $invoice->customer_id === $user->id,
            default => false,
        };
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    /** Recording an offline payment is an admin act. */
    public function recordPayment(User $user, Invoice $invoice): bool
    {
        return $user->isAdmin() && $invoice->isPayable();
    }

    /** A paid invoice is a financial record — it does not get deleted. */
    public function delete(User $user, Invoice $invoice): bool
    {
        return $user->isAdmin() && ! $invoice->isPaid() && $invoice->paid_cents === 0;
    }
}
