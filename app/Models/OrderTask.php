<?php

namespace App\Models;

use App\Enums\Priority;
use App\Enums\TaskStatus;
use App\Enums\TaskType;
use App\Policies\OrderTaskPolicy;
use App\Services\TaskSla;
use App\Services\TaskSlaCalculator;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'order_id', 'order_message_id', 'employee_id', 'assigned_by_id', 'task_type',
    'duration_hours', 'priority', 'instructions', 'assigned_at', 'deadline_at', 'notify',
])]
#[UsePolicy(OrderTaskPolicy::class)]
class OrderTask extends Model
{
    protected function casts(): array
    {
        return [
            'task_type' => TaskType::class,
            'status' => TaskStatus::class,
            'priority' => Priority::class,
            'duration_hours' => 'integer',
            'assigned_at' => 'datetime',
            'deadline_at' => 'datetime',
            'completed_at' => 'datetime',
            'notify' => 'boolean',
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /** The kind = revision message this task carries out, if any. */
    public function revision(): BelongsTo
    {
        return $this->belongsTo(OrderMessage::class, 'order_message_id');
    }

    public function employee(): BelongsTo
    {
        // Who did the work stays on the record even if they leave.
        return $this->belongsTo(User::class, 'employee_id')->withTrashed();
    }

    public function assignedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by_id')->withTrashed();
    }

    /*
    |--------------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------------
    */

    public function scopeForEmployee(Builder $query, User|int $employee): Builder
    {
        return $query->where('employee_id', $employee instanceof User ? $employee->id : $employee);
    }

    public function scopeOpen(Builder $query): Builder
    {
        return $query->whereIn('status', [TaskStatus::Assigned->value, TaskStatus::InProgress->value]);
    }

    public function scopeFinished(Builder $query): Builder
    {
        return $query->where('status', TaskStatus::Completed->value);
    }

    /**
     * Exclude tasks whose parent order has been deleted.
     *
     * Orders are soft-deleted, which does not cascade, so without this a
     * deleted order leaves its tasks sitting in the queue with a dash where
     * the order number should be. whereHas() applies the Order model's
     * soft-delete scope, so trashed parents drop out automatically.
     */
    public function scopeForLiveOrder(Builder $query): Builder
    {
        return $query->whereHas('order');
    }

    /*
    |--------------------------------------------------------------------------
    | SLA
    |--------------------------------------------------------------------------
    |
    | The views work in the units the mock data used: minutes since assignment,
    | hours allowed, minutes taken. These accessors speak that language so the
    | existing Blade maths keeps working unchanged.
    |
    */

    /** Minutes since the task was handed over — the views' `assigned_ago`. */
    protected function assignedAgoMinutes(): Attribute
    {
        return Attribute::get(fn () => (int) $this->assigned_at->diffInMinutes(now()));
    }

    /** Minutes actually taken, or null while still running — `completed_after`. */
    protected function completedAfterMinutes(): Attribute
    {
        return Attribute::get(
            fn () => $this->completed_at
                ? (int) $this->assigned_at->diffInMinutes($this->completed_at)
                : null,
        );
    }

    /** The derived running/overdue/on_time/late state. Never stored. */
    public function sla(): TaskSla
    {
        return app(TaskSlaCalculator::class)->for($this);
    }

    public function isOpen(): bool
    {
        return $this->status->isOpen();
    }
}
