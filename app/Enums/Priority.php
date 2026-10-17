<?php

namespace App\Enums;

/** Shared by tasks and revisions — matches $priorityMeta in the views. */
enum Priority: string
{
    case Normal = 'normal';
    case High = 'high';
    case Urgent = 'urgent';

    public function label(): string
    {
        return match ($this) {
            self::Normal => 'Normal',
            self::High => 'High',
            self::Urgent => 'Urgent',
        };
    }

    public function badge(): string
    {
        return match ($this) {
            self::Normal => 'pending',
            self::High => 'progress',
            self::Urgent => 'urgent',
        };
    }

    public function meta(): array
    {
        return ['label' => $this->label(), 'badge' => $this->badge()];
    }

    public static function metaMap(): array
    {
        return array_reduce(
            self::cases(),
            fn (array $carry, self $priority) => $carry + [$priority->value => $priority->meta()],
            [],
        );
    }

    public static function options(): array
    {
        return array_reduce(
            self::cases(),
            fn (array $carry, self $priority) => $carry + [$priority->value => $priority->label()],
            [],
        );
    }
}
