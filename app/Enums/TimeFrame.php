<?php

namespace App\Enums;

/**
 * Digitizing and vector only — HOUR-based turnaround.
 * Patch uses ExpectedDelivery, which is day-based. Same backing values,
 * deliberately different enums: "Normal" means 24 hours here and 15 days there.
 */
enum TimeFrame: string
{
    case Normal = 'normal';
    case Rush = 'rush';
    case SuperRush = 'super_rush';

    public function label(): string
    {
        return match ($this) {
            self::Normal => 'Normal (12 to 24 hours)',
            self::Rush => 'Rush (4 to 8 hours)',
            self::SuperRush => 'Super Rush (1 to 3 hours)',
        };
    }

    /** The outer bound of the promise — what due_at is built from. */
    public function slaHours(): int
    {
        return match ($this) {
            self::Normal => 24,
            self::Rush => 8,
            self::SuperRush => 3,
        };
    }

    public static function options(): array
    {
        return array_reduce(
            self::cases(),
            fn (array $carry, self $frame) => $carry + [$frame->value => $frame->label()],
            [],
        );
    }
}
