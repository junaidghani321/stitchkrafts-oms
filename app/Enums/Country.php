<?php

namespace App\Enums;

/**
 * The countries this application serves.
 *
 * Backed by the ISO 3166-1 alpha-2 code so the `user_details.country` char(2)
 * column stores the same two letters the UI and exports already show. Note
 * `uk` rather than the ISO-correct `gb`: it is the code the business uses.
 */
enum Country: string
{
    case Australia = 'au';
    case UnitedKingdom = 'uk';
    case UnitedStates = 'us';

    public function label(): string
    {
        return match ($this) {
            self::Australia => 'Australia',
            self::UnitedKingdom => 'United Kingdom',
            self::UnitedStates => 'United States',
        };
    }

    /** value => label, for the country select. */
    public static function options(): array
    {
        return [
            self::Australia->value => self::Australia->label(),
            self::UnitedKingdom->value => self::UnitedKingdom->label(),
            self::UnitedStates->value => self::UnitedStates->label(),
        ];
    }

    /** The accepted values, for `Rule::in(...)` and the migration's check constraint. */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
