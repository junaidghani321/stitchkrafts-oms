<?php

namespace App\Enums;

/**
 * The quote lifecycle — deliberately separate from OrderStatus.
 *
 * A quote answers "what would this cost?", so it never enters production: it
 * is priced, then accepted or declined. On acceptance a real Order is created
 * and takes over from there.
 *
 * Note the views keep `status` and the badge CSS class in two different maps
 * ($statusLabels and $statusStyles) because the quote vocabulary does not
 * match the shared badge names.
 */
enum QuoteStatus: string
{
    case Pending = 'pending';
    case Quoted = 'quoted';
    case Approved = 'approved';
    case Rejected = 'rejected';
    case Expired = 'expired';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pending',
            self::Quoted => 'Quoted',
            self::Approved => 'Approved',
            self::Rejected => 'Declined',
            self::Expired => 'Expired',
        };
    }

    /** Maps onto the badge classes style.css already ships. */
    public function badge(): string
    {
        return match ($this) {
            self::Pending => 'pending',
            self::Quoted => 'progress',
            self::Approved => 'done',
            self::Rejected => 'unpaid',
            self::Expired => 'unpaid',
        };
    }

    public function meta(): array
    {
        return ['label' => $this->label(), 'badge' => $this->badge()];
    }

    /** Still waiting on somebody. */
    public function isOpen(): bool
    {
        return in_array($this, [self::Pending, self::Quoted], true);
    }

    /** A customer can only accept or decline once a price exists. */
    public function isDecidable(): bool
    {
        return $this === self::Quoted;
    }

    public function isSettled(): bool
    {
        return in_array($this, [self::Approved, self::Rejected, self::Expired], true);
    }

    /** value => label, for the filter dropdown. */
    public static function options(): array
    {
        return array_reduce(
            self::cases(),
            fn (array $carry, self $status) => $carry + [$status->value => $status->label()],
            [],
        );
    }

    /** value => badge class, the views' $statusStyles map. */
    public static function styles(): array
    {
        return array_reduce(
            self::cases(),
            fn (array $carry, self $status) => $carry + [$status->value => $status->badge()],
            [],
        );
    }
}
