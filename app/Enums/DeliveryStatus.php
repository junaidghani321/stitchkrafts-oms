<?php

namespace App\Enums;

/**
 * Tracks whether finished files actually reached the customer — separate from
 * whether the work is done (status) and whether it is paid (payment_status).
 */
enum DeliveryStatus: string
{
    case Pending = 'pending';
    case Ready = 'ready';
    case Delivered = 'delivered';
    case Returned = 'returned';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Not Delivered',
            self::Ready => 'Ready to Send',
            self::Delivered => 'Delivered',
            self::Returned => 'Returned',
        };
    }

    public function badge(): string
    {
        return match ($this) {
            self::Pending => 'pending',
            self::Ready => 'progress',
            self::Delivered => 'done',
            self::Returned => 'unpaid',
        };
    }
}
