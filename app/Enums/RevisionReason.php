<?php

namespace App\Enums;

/** The Reason select in #revisionModal, verbatim. */
enum RevisionReason: string
{
    case Size = 'size';
    case Colors = 'colors';
    case Stitch = 'stitch';
    case Format = 'format';
    case Artwork = 'artwork';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Size => 'Size or dimensions',
            self::Colors => 'Colour changes',
            self::Stitch => 'Stitch or density issue',
            self::Format => 'Wrong file format',
            self::Artwork => 'Artwork correction',
            self::Other => 'Other',
        };
    }

    public static function options(): array
    {
        return array_reduce(
            self::cases(),
            fn (array $carry, self $reason) => $carry + [$reason->value => $reason->label()],
            [],
        );
    }
}
