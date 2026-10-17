<?php

namespace App\Enums;

/**
 * Deliberately NOT merged with OrderStatus.
 *
 * Revisions use `in_progress` / `completed` while orders use `progress` / `done`.
 * The two look mergeable, but oms-table.js matches data-status by exact string
 * equality, and these values are persisted in order_messages.status.
 */
enum RevisionStatus: string
{
    case Pending = 'pending';
    case InProgress = 'in_progress';
    case Completed = 'completed';
    case Rejected = 'rejected';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pending',
            self::InProgress => 'In Progress',
            self::Completed => 'Completed',
            self::Rejected => 'Rejected',
        };
    }

    public function badge(): string
    {
        return match ($this) {
            self::Pending => 'pending',
            self::InProgress => 'progress',
            self::Completed => 'done',
            self::Rejected => 'unpaid',
        };
    }

    public function isOpen(): bool
    {
        return in_array($this, [self::Pending, self::InProgress], true);
    }
}
