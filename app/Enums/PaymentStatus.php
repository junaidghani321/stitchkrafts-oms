<?php

namespace App\Enums;

/**
 * Tracks where the money is, independently of the work pipeline.
 * Billing is out of scope this phase — this exists so it slots in later
 * without another migration.
 */
enum PaymentStatus: string
{
    case Unpaid = 'unpaid';
    case Partial = 'partial';
    case Paid = 'paid';
    case Refunded = 'refunded';

    public function label(): string
    {
        return match ($this) {
            self::Unpaid => 'Unpaid',
            self::Partial => 'Partially Paid',
            self::Paid => 'Paid',
            self::Refunded => 'Refunded',
        };
    }

    /** style.css ships oms-badge--paid and oms-badge--unpaid. */
    public function badge(): string
    {
        return match ($this) {
            self::Unpaid => 'unpaid',
            self::Partial => 'pending',
            self::Paid => 'paid',
            self::Refunded => 'pending',
        };
    }
}
