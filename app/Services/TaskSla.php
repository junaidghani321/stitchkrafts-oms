<?php

namespace App\Services;

use App\Enums\TaskSlaStatus;
use Illuminate\Support\Carbon;

/**
 * The derived SLA picture for a single task.
 *
 * Field names mirror the variables the existing task views already build, so
 * the Blade maths can be swapped for these one view at a time.
 */
readonly class TaskSla
{
    public function __construct(
        /** Minutes allowed — duration_hours * 60. */
        public int $allowed,
        public Carbon $assignedAt,
        public Carbon $deadline,
        /** Minutes since assignment. */
        public int $assignedAgo,
        /** Minutes actually taken, or null while still running. */
        public ?int $completedAfter,
        public TaskSlaStatus $status,
        /** 0-100, capped. */
        public int $percent,
        /** Minutes remaining; negative once over the allowance. */
        public int $delta,
    ) {}

    public function isFinished(): bool
    {
        return $this->status->isFinished();
    }

    public function isOverdue(): bool
    {
        return $this->status === TaskSlaStatus::Overdue;
    }

    /** True when the work ran past its allowance, finished or not. */
    public function isBreached(): bool
    {
        return $this->delta < 0;
    }

    /** "3h 05m" — same format as the views' $asDuration closure. */
    public function formattedDelta(): string
    {
        return self::asDuration($this->delta);
    }

    public function formattedAllowed(): string
    {
        return self::asDuration($this->allowed);
    }

    /** What oms-timer.js ticks against. */
    public function deadlineTimestamp(): int
    {
        return $this->deadline->getTimestamp();
    }

    /**
     * The exact helper the task views define inline:
     * intdiv(m, 60).'h '.str_pad(m % 60, 2, '0', STR_PAD_LEFT).'m'
     */
    public static function asDuration(int $minutes): string
    {
        $minutes = (int) abs($minutes);

        return intdiv($minutes, 60).'h '.str_pad((string) ($minutes % 60), 2, '0', STR_PAD_LEFT).'m';
    }

    /** The row shape the existing Blade SLA block expects. */
    public function toArray(): array
    {
        return [
            'allowed' => $this->allowed,
            'assigned' => $this->assignedAt,
            'deadline' => $this->deadline,
            'status' => $this->status->value,
            'percent' => $this->percent,
            'delta' => $this->delta,
        ];
    }
}
