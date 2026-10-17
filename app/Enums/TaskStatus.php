<?php

namespace App\Enums;

/**
 * The task LIFECYCLE — a completely different axis from TaskSlaStatus.
 *
 * This is what is stored in order_tasks.status. Whether the task is on time
 * is derived, never stored: see TaskSlaStatus and TaskSlaCalculator.
 */
enum TaskStatus: string
{
    case Assigned = 'assigned';
    case InProgress = 'in_progress';
    case Completed = 'completed';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Assigned => 'Assigned',
            self::InProgress => 'In Progress',
            self::Completed => 'Completed',
            self::Cancelled => 'Cancelled',
        };
    }

    public function isOpen(): bool
    {
        return in_array($this, [self::Assigned, self::InProgress], true);
    }
}
