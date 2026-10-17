<?php

namespace App\Enums;

/**
 * Derived, never stored. Mirrors $statusMeta in the task views verbatim:
 *
 *   'running' => ['label' => 'In Progress',       'badge' => 'progress', 'bar' => 'warn'],
 *   'overdue' => ['label' => 'Overdue',           'badge' => 'unpaid',   'bar' => 'bad'],
 *   'on_time' => ['label' => 'Completed On Time', 'badge' => 'done',     'bar' => 'good'],
 *   'late'    => ['label' => 'Completed Late',    'badge' => 'unpaid',   'bar' => 'bad'],
 *
 * Storing this would go stale the moment a deadline passes, and there is no
 * guaranteed queue worker to refresh it.
 */
enum TaskSlaStatus: string
{
    case Running = 'running';
    case Overdue = 'overdue';
    case OnTime = 'on_time';
    case Late = 'late';

    public function label(): string
    {
        return match ($this) {
            self::Running => 'In Progress',
            self::Overdue => 'Overdue',
            self::OnTime => 'Completed On Time',
            self::Late => 'Completed Late',
        };
    }

    public function badge(): string
    {
        return match ($this) {
            self::Running => 'progress',
            self::Overdue => 'unpaid',
            self::OnTime => 'done',
            self::Late => 'unpaid',
        };
    }

    public function bar(): string
    {
        return match ($this) {
            self::Running => 'warn',
            self::Overdue => 'bad',
            self::OnTime => 'good',
            self::Late => 'bad',
        };
    }

    public function isFinished(): bool
    {
        return in_array($this, [self::OnTime, self::Late], true);
    }

    public function isActive(): bool
    {
        return ! $this->isFinished();
    }

    /** The shape the views' $statusMeta array expects. */
    public function meta(): array
    {
        return ['label' => $this->label(), 'badge' => $this->badge(), 'bar' => $this->bar()];
    }

    /** value => meta, for building the filter dropdown. */
    public static function metaMap(): array
    {
        return array_reduce(
            self::cases(),
            fn (array $carry, self $status) => $carry + [$status->value => $status->meta()],
            [],
        );
    }
}
