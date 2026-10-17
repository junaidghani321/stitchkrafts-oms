<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

#[Fillable(['order_id', 'user_id', 'event', 'description', 'subject_type', 'subject_id', 'properties'])]
class OrderActivity extends Model
{
    /*
     | Event names, so the timeline and any future filtering agree on spelling.
     */
    public const ORDER_CREATED = 'order.created';

    public const ORDER_UPDATED = 'order.updated';

    public const ORDER_PRICED = 'order.priced';

    public const ORDER_STATUS_CHANGED = 'order.status_changed';

    public const ORDER_DELETED = 'order.deleted';

    public const ORDER_CANCELLED = 'order.cancelled';

    public const ORDER_DELIVERED = 'order.delivered';

    public const ORDER_SUBMITTED = 'order.submitted';

    public const ORDER_APPROVED = 'order.approved';

    public const TASK_ASSIGNED = 'task.assigned';

    public const TASK_STARTED = 'task.started';

    public const TASK_EXTENDED = 'task.extended';

    public const TASK_COMPLETED = 'task.completed';

    public const REVISION_REQUESTED = 'revision.requested';

    public const REVISION_ASSIGNED = 'revision.assigned';

    public const REVISION_COMPLETED = 'revision.completed';

    public const REVISION_REJECTED = 'revision.rejected';

    public const FILE_UPLOADED = 'file.uploaded';

    public const FILE_REPLACED = 'file.replaced';

    protected function casts(): array
    {
        return [
            'properties' => 'array',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function user(): BelongsTo
    {
        // Audit rows must keep naming who acted.
        return $this->belongsTo(User::class)->withTrashed();
    }

    public function subject(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Shop-floor business the customer has no part in.
     *
     * The timeline is rendered on every role's order page, and these three
     * events would tell a customer that a manager corrected their file and
     * what was said while signing it off. Deliberately limited to the
     * approval events — every other event's visibility is left exactly as it
     * was.
     */
    public function isInternal(): bool
    {
        return in_array($this->event, [
            self::ORDER_SUBMITTED,
            self::ORDER_APPROVED,
            self::FILE_REPLACED,
        ], true);
    }

    /**
     * The tone class the .oms-timeline markup expects: is-good | is-bad | ''.
     */
    public function tone(): string
    {
        return match ($this->event) {
            self::TASK_COMPLETED, self::ORDER_DELIVERED,
            self::REVISION_COMPLETED, self::ORDER_APPROVED => 'is-good',
            self::REVISION_REQUESTED, self::REVISION_REJECTED, self::TASK_EXTENDED => 'is-bad',
            default => '',
        };
    }
}
