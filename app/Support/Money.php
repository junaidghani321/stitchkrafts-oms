<?php

namespace App\Support;

/**
 * Money is handled as integer cents everywhere below the view layer.
 *
 * orders.price is decimal(10,2), which PHP hands back as a string. Casting
 * that to float and multiplying by 100 is how 49.99 quietly becomes 4998 —
 * so the conversion goes through bcmath and rounds explicitly.
 */
class Money
{
    /** "49.99" or 49.99 -> 4999 */
    public static function toCents(string|float|int|null $amount): int
    {
        if ($amount === null || $amount === '') {
            return 0;
        }

        return (int) round((float) bcmul((string) $amount, '100', 4));
    }

    /** 4999 -> 49.99 */
    public static function toDecimal(int $cents): float
    {
        return (float) bcdiv((string) $cents, '100', 2);
    }

    /** 4999 -> "49.99", for display alongside the existing "$ " prefix. */
    public static function format(int $cents): string
    {
        return number_format(self::toDecimal($cents), 2);
    }
}
