<?php

namespace App\Enums;

enum OrderType: string
{
    case Digitizing = 'digitizing';
    case Vector = 'vector';
    case Patch = 'patch';

    /** Matches $typeLabels in every index view verbatim. */
    public function label(): string
    {
        return match ($this) {
            self::Digitizing => 'Digitizing',
            self::Vector => 'Vector',
            self::Patch => 'Patch',
        };
    }

    /** ADDO-442 / ADVO-388 / ADPO-377 */
    public function orderPrefix(): string
    {
        return match ($this) {
            self::Digitizing => 'ADDO',
            self::Vector => 'ADVO',
            self::Patch => 'ADPO',
        };
    }

    /** ADDQ-118 / ADVQ- / ADPQ- — the hook for the Quotes phase. */
    public function quotePrefix(): string
    {
        return match ($this) {
            self::Digitizing => 'ADDQ',
            self::Vector => 'ADVQ',
            self::Patch => 'ADPQ',
        };
    }

    /**
     * Digitizing and vector have disjoint format lists; patch has none.
     */
    public function formatOptions(): array
    {
        return config("orders.formats.{$this->value}", []);
    }

    /** value => label, for the type filter dropdowns. */
    public static function options(): array
    {
        return array_reduce(
            self::cases(),
            fn (array $carry, self $type) => $carry + [$type->value => $type->label()],
            [],
        );
    }
}
