<?php

namespace App\Models;

use App\Enums\MessageKind;
use App\Enums\Priority;
use App\Enums\RevisionReason;
use App\Enums\RevisionStatus;
use App\Policies\OrderMessagePolicy;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * A row in an order's thread: when kind = revision, a request for change
 * carrying its own work state.
 *
 * The conversation feature that this table was built for is gone; revisions
 * stayed, because a revision has always been one of these rows.
 *
 * A revision carries no assignee or deadline of its own: that lives on the
 * OrderTask with task_type = revision, so revisions and orders share one SLA
 * engine. Nor does it carry a resolution note or rejection reason — those are
 * replies pointing back through parent_id, which is what gives the files
 * handed in against a revision somewhere to hang.
 *
 * `kind` is deliberately NOT mass-assignable from a request. See MessageKind.
 */
#[Fillable(['order_id', 'user_id', 'body'])]
#[UsePolicy(OrderMessagePolicy::class)]
class OrderMessage extends Model
{
    public const SOURCE_CUSTOMER = 'customer';

    public const SOURCE_QUALITY_CHECK = 'quality_check';

    protected function casts(): array
    {
        return [
            'kind' => MessageKind::class,
            'reason' => RevisionReason::class,
            'priority' => Priority::class,
            'status' => RevisionStatus::class,
            'due_date' => 'date',
            'completed_at' => 'datetime',
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

    /** withTrashed: who said it stays on the record. */
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id')->withTrashed();
    }

    /** The revision this message resolves or rejects, when it is a reply. */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    /** The replies that settled this revision. */
    public function replies(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    /** Reuses the same polymorphic file table as everything else. */
    public function files(): MorphMany
    {
        return $this->morphMany(OrderFile::class, 'attachable');
    }

    /**
     * What the author attached. Includes revision_request so a revision's own
     * files render through the thread loop that already exists.
     */
    public function attachments(): MorphMany
    {
        return $this->files()->whereIn('category', [
            OrderFile::CATEGORY_MESSAGE,
            OrderFile::CATEGORY_REVISION_REQUEST,
        ]);
    }

    /**
     * Work handed in on this message, rendered separately from the author's
     * own attachments: the output of a revision, and anything an employee
     * attached to the thread, which ConversationService::post() records the
     * same way because it is submitted work too.
     *
     * Customer-gated, unlike attachments() — see OrderFile::CUSTOMER_GATED.
     */
    public function deliverables(): MorphMany
    {
        return $this->files()->where('category', OrderFile::CATEGORY_REVISION_DELIVERABLE);
    }

    /** Only ever populated for kind = revision. */
    public function tasks(): HasMany
    {
        return $this->hasMany(OrderTask::class, 'order_message_id');
    }

    public function latestTask(): HasOne
    {
        return $this->hasOne(OrderTask::class, 'order_message_id')->latestOfMany('assigned_at');
    }

    /*
    |--------------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------------
    */

    public function scopeRevisions(Builder $query): Builder
    {
        return $query->where('kind', MessageKind::Revision->value);
    }

    public function scopeComments(Builder $query): Builder
    {
        return $query->where('kind', MessageKind::Comment->value);
    }

    /**
     * Chain after revisions(): a comment's status is NULL, and `NULL IN (…)`
     * is never true, but relying on that accident hides the intent.
     */
    public function scopeOpen(Builder $query): Builder
    {
        return $query->whereIn('status', [
            RevisionStatus::Pending->value,
            RevisionStatus::InProgress->value,
        ]);
    }

    /** Exclude messages whose parent order has been deleted. */
    public function scopeForLiveOrder(Builder $query): Builder
    {
        return $query->whereHas('order');
    }

    public function scopeForCustomer(Builder $query, User|int $customer): Builder
    {
        $id = $customer instanceof User ? $customer->id : $customer;

        return $query->whereHas('order', fn (Builder $order) => $order->where('customer_id', $id));
    }

    public function scopeForEmployee(Builder $query, User|int $employee): Builder
    {
        $id = $employee instanceof User ? $employee->id : $employee;

        return $query->whereHas('tasks', fn (Builder $task) => $task->where('employee_id', $id));
    }

    /**
     * The thread as this person is allowed to read it.
     *
     * A revision raised against an order is the floor manager's to triage
     * first: until an admin or manager assigns it, no employee is to see it,
     * even one already working the order. Assigning is what hands it over —
     * from then on the employee it was given to reads the revision and
     * everything said under it.
     *
     * Replies travel with their revision, because a reply carries the
     * revision's context: "Re: REV-3" under a revision the reader cannot see
     * is both a leak and nonsense.
     *
     * Everyone else — the customer whose order it is, admins and managers —
     * reads the whole thread, so the scope is a no-op for them.
     */
    public function scopeVisibleTo(Builder $query, ?User $user): Builder
    {
        if ($user === null || ! $user->isEmployee()) {
            return $query;
        }

        // Has, or has held, the task: a reassignment does not retract what
        // somebody was already shown. Mirrors OrderPolicy::hasTaskOn().
        $assignedToMe = OrderTask::query()
            ->whereNotNull('order_message_id')
            ->where('employee_id', $user->id)
            ->select('order_message_id');

        return $query->where(function (Builder $visible) use ($assignedToMe) {
            $visible
                // An ordinary comment, which belongs to no revision.
                ->where(fn (Builder $comment) => $comment
                    ->whereNull('parent_id')
                    ->where('kind', '!=', MessageKind::Revision->value))
                // A revision handed to me...
                ->orWhereIn('id', $assignedToMe)
                // ...and what has been said under it.
                ->orWhereIn('parent_id', $assignedToMe);
        });
    }

    /*
    |--------------------------------------------------------------------------
    | Accessors
    |--------------------------------------------------------------------------
    */

    /** The admin/manager "Assigned To" column. */
    protected function assigneeName(): Attribute
    {
        return Attribute::get(fn () => $this->latestTask?->employee?->name ?? '—');
    }

    /** The "Requested by" column: Customer or Quality Check. */
    protected function byLabel(): Attribute
    {
        return Attribute::get(
            fn () => $this->source === self::SOURCE_CUSTOMER ? 'Customer' : 'Quality Check',
        );
    }

    public function isRevision(): bool
    {
        return $this->kind === MessageKind::Revision;
    }

    /**
     * The single-record form of scopeVisibleTo(), for the policies: a
     * guessed message or file id must not get an employee past the gate the
     * thread query applies.
     */
    public function isVisibleTo(User $user): bool
    {
        if (! $user->isEmployee()) {
            return true;
        }

        $revisionId = $this->isRevision() ? $this->id : $this->parent_id;

        if ($revisionId === null) {
            return true;
        }

        return OrderTask::query()
            ->where('order_message_id', $revisionId)
            ->where('employee_id', $user->id)
            ->exists();
    }

    /** False for a comment, which has no status at all. */
    public function isOpen(): bool
    {
        return $this->status?->isOpen() ?? false;
    }
}
