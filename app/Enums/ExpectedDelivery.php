<?php

namespace App\Enums;

/**
 * Patch only — DAY-based production turnaround.
 * Kept separate from TimeFrame because the semantics differ by two orders of
 * magnitude; merging them would let a report group 3-hour and 10-day work
 * under the same "rush" label.
 */
enum ExpectedDelivery: string
{
    case Normal = 'normal';
    case Rush = 'rush';
    case SuperRush = 'super_rush';

    public function label(): string
    {
        return match ($this) {
            self::Normal => 'Normal (10 to 15 days)',
            self::Rush => 'Rush (5 to 7 days)',
            self::SuperRush => 'Super Rush (2 to 4 days)',
        };
    }

    /** The outer bound of the promise — what due_at is built from. */
    public function slaDays(): int
    {
        return match ($this) {
            self::Normal => 15,
            self::Rush => 7,
            self::SuperRush => 4,
        };
    }

    public static function options(): array
    {
        return array_reduce(
            self::cases(),
            fn (array $carry, self $delivery) => $carry + [$delivery->value => $delivery->label()],
            [],
        );
    }
}
