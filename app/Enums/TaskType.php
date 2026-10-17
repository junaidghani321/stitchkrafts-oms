<?php

namespace App\Enums;

/**
 * The five options in #assignTaskModal. The first three mirror OrderType;
 * the last two are work that is not itself an order type.
 */
enum TaskType: string
{
    case Digitizing = 'digitizing';
    case Vector = 'vector';
    case Patch = 'patch';
    case Revision = 'revision';
    case QualityCheck = 'quality_check';

    public function label(): string
    {
        return match ($this) {
            self::Digitizing => 'Digitizing',
            self::Vector => 'Vector',
            self::Patch => 'Patch',
            self::Revision => 'Revision',
            self::QualityCheck => 'Quality Check',
        };
    }

    public static function fromOrderType(OrderType $type): self
    {
        return match ($type) {
            OrderType::Digitizing => self::Digitizing,
            OrderType::Vector => self::Vector,
            OrderType::Patch => self::Patch,
        };
    }

    /** The order status a task of this type drives the order into. */
    public function orderStatusOnStart(): OrderStatus
    {
        return match ($this) {
            self::Revision => OrderStatus::Revision,
            self::QualityCheck => OrderStatus::QualityCheck,
            default => OrderStatus::InProgress,
        };
    }

    public static function options(): array
    {
        return array_reduce(
            self::cases(),
            fn (array $carry, self $type) => $carry + [$type->value => $type->label()],
            [],
        );
    }
}
