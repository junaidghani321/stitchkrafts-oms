<?php

namespace App\Models;

use App\Enums\DeliveryStatus;
use App\Enums\ExpectedDelivery;
use App\Enums\MessageKind;
use App\Enums\OrderStatus;
use App\Enums\OrderType;
use App\Enums\PaymentStatus;
use App\Enums\TimeFrame;
use App\Observers\OrderObserver;
use App\Policies\OrderPolicy;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * `order_number`, `status`, `price` and the delivery/payment columns are all
 * excluded from fillable — they are set by the observer or by explicit,
 * audited service calls, never by a mass-assigned payload.
 */
#[Fillable([
    'type', 'customer_id', 'created_by_id', 'design_name', 'instructions', 'due_at',
    'time_frame', 'required_format',
    'height', 'width', 'colors',
    'unit', 'fabric', 'placement', 'puff_3d', 'applique', 'trims', 'patch_cost',
    'color_type', 'printing_process',
    'expected_delivery', 'quantity', 'patch_type', 'patch_background', 'backing_type', 'patch_border',
])]
#[ObservedBy(OrderObserver::class)]
#[UsePolicy(OrderPolicy::class)]
class Order extends Model
{
    use HasFactory, SoftDeletes;

    protected function casts(): array
    {
        return [
            'type' => OrderType::class,
            'status' => OrderStatus::class,
            'payment_status' => PaymentStatus::class,
            'delivery_status' => DeliveryStatus::class,
            'time_frame' => TimeFrame::class,
            'expected_delivery' => ExpectedDelivery::class,
            'price' => 'decimal:2',
            'height' => 'decimal:2',
            'width' => 'decimal:2',
            'colors' => 'integer',
            'quantity' => 'integer',
            'puff_3d' => 'boolean',
            'applique' => 'boolean',
            'trims' => 'boolean',
            'patch_cost' => 'boolean',
            'due_at' => 'datetime',
            'priced_at' => 'datetime',
            'completed_at' => 'datetime',
            'delivered_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'approved_at' => 'datetime',
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function customer(): BelongsTo
    {
        // withTrashed: who placed this order is a historical fact and must
        // survive the account being deleted.
        return $this->belongsTo(User::class, 'customer_id')->withTrashed();
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_id')->withTrashed();
    }

    public function pricedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'priced_by_id')->withTrashed();
    }

    public function cancelledBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cancelled_by_id')->withTrashed();
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by_id')->withTrashed();
    }

    public function files(): MorphMany
    {
        return $this->morphMany(OrderFile::class, 'attachable');
    }

    /** What the customer supplied. */
    public function artwork(): MorphMany
    {
        return $this->files()->where('category', OrderFile::CATEGORY_ARTWORK);
    }

    /**
     * Everything handed in against the order itself, approved or not.
     *
     * Not the whole picture: work produced for a revision hangs off the
     * message it was handed in with, not off the order — see
     * revisionDeliverables(). Anything that must cover both routes (the
     * approval panel, the release on sign-off) wants
     * deliverablesAwaitingApproval().
     */
    public function deliverables(): MorphMany
    {
        return $this->files()->where('category', OrderFile::CATEGORY_DELIVERABLE);
    }

    /**
     * Work handed in through the thread rather than attached to the order:
     * the output of a revision, and any file an employee posts in the
     * conversation — see ConversationService::post().
     *
     * Through the thread, because that is where the files actually live: the
     * revision round is a message, and RevisionService posts the employee's
     * output as its reply. Without this relation an order cannot answer "what
     * is waiting on my approval?" for anything but a first round.
     */
    public function revisionDeliverables(): HasManyThrough
    {
        return $this->hasManyThrough(
            OrderFile::class,
            OrderMessage::class,
            firstKey: 'order_id',
            secondKey: 'attachable_id',
            localKey: 'id',
            secondLocalKey: 'id',
        )
            ->where('order_files.attachable_type', (new OrderMessage)->getMorphClass())
            ->where('order_files.category', OrderFile::CATEGORY_REVISION_DELIVERABLE);
    }

    /**
     * Everything handed in on this order, by either route, that an approver
     * has not signed off yet.
     *
     * This is what the approval panel offers for replacement and what
     * approving releases. Already-approved files are deliberately absent: the
     * customer has them, and correcting one of those is a revision, not an
     * edit.
     *
     * @return Collection<int, OrderFile>
     */
    public function deliverablesAwaitingApproval(): Collection
    {
        return $this->deliverables
            ->concat($this->revisionDeliverables)
            ->filter(fn (OrderFile $file) => $file->isAwaitingApproval())
            ->sortBy('created_at')
            ->values();
    }

    /**
     * What the customer gets: only what an approver has signed off. An
     * employee's upload stays invisible to them until then.
     */
    public function approvedDeliverables(): MorphMany
    {
        return $this->deliverables()->whereNotNull('approved_at');
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(OrderTask::class);
    }

    public function latestTask(): HasOne
    {
        return $this->hasOne(OrderTask::class)->latestOfMany('assigned_at');
    }

    public function activeTask(): HasOne
    {
        return $this->hasOne(OrderTask::class)
            ->whereIn('status', ['assigned', 'in_progress'])
            ->latestOfMany('assigned_at');
    }

    /**
     * The revision facet of the thread. Kept as its own relation for the
     * settle interlock and withCount(); render the order page's card by
     * filtering the already-loaded messages instead of eager-loading both.
     */
    public function revisions(): HasMany
    {
        return $this->hasMany(OrderMessage::class)->where('kind', MessageKind::Revision->value);
    }

    /**
     * The invoice line this order sits on, if it has been billed.
     * whereDoesntHave('invoiceItem') is what drives the "not yet invoiced"
     * list on the billing screen.
     */
    public function invoiceItem(): HasOne
    {
        return $this->hasOne(InvoiceItem::class);
    }

    public function invoice(): HasOneThrough
    {
        return $this->hasOneThrough(Invoice::class, InvoiceItem::class, 'order_id', 'id', 'id', 'invoice_id');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(OrderMessage::class);
    }

    public function activities(): HasMany
    {
        return $this->hasMany(OrderActivity::class);
    }

    /*
    |--------------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------------
    */

    public function scopeForCustomer(Builder $query, User|int $customer): Builder
    {
        return $query->where('customer_id', $customer instanceof User ? $customer->id : $customer);
    }

    /** Orders an employee has ever been given a task on. */
    public function scopeAssignedTo(Builder $query, User|int $employee): Builder
    {
        $id = $employee instanceof User ? $employee->id : $employee;

        return $query->whereHas('tasks', fn (Builder $task) => $task->where('employee_id', $id));
    }

    public function scopeOfType(Builder $query, OrderType|string $type): Builder
    {
        return $query->where('type', $type instanceof OrderType ? $type->value : $type);
    }

    /**
     * Excludes cancelled work. Used where a cancelled order would be an
     * actively wrong choice — the assign-task pickers and the dashboard
     * counts — NOT by the order indexes, which show cancelled rows so that a
     * cancelled order does not simply vanish from the customer's list.
     */
    public function scopeNotCancelled(Builder $query): Builder
    {
        return $query->where('status', '!=', OrderStatus::Cancelled->value);
    }

    /** Orders a revision can be requested against. */
    public function scopeRevisable(Builder $query): Builder
    {
        return $query->whereIn('status', [
            OrderStatus::Completed->value,
            OrderStatus::Delivered->value,
        ]);
    }

    /**
     * The shared index scope. oms-table.js paginates client-side, so this
     * returns a full collection — never a paginator — with a row cap as the
     * safety valve.
     */
    public function scopeForIndex(Builder $query): Builder
    {
        $days = config('orders.index.recent_days');

        return $query
            ->when($days, fn (Builder $q) => $q->where('orders.created_at', '>=', now()->subDays($days)))
            ->latest('orders.created_at')
            ->limit(config('orders.index.max_rows', 1000));
    }

    /*
    |--------------------------------------------------------------------------
    | Accessors
    |--------------------------------------------------------------------------
    */

    /** pending | progress | done — what the user/admin/manager views render. */
    protected function badgeStatus(): Attribute
    {
        return Attribute::get(fn () => $this->status->badge());
    }

    /** progress | revision | done — the employee view's own vocabulary. */
    protected function employeeBadgeStatus(): Attribute
    {
        return Attribute::get(fn () => $this->status->employeeBadge());
    }

    /**
     * Never null. The views call number_format() on this and compare it to 0
     * for the greyed-out styling; passing null would raise a deprecation on
     * every un-priced order.
     */
    protected function displayPrice(): Attribute
    {
        return Attribute::get(fn () => (float) ($this->price ?? 0));
    }

    public function isPriced(): bool
    {
        return $this->price !== null;
    }

    public function isCancelled(): bool
    {
        return $this->status === OrderStatus::Cancelled;
    }

    /** The SLA tier, whichever column holds it for this type. */
    public function turnaround(): TimeFrame|ExpectedDelivery|null
    {
        return $this->type === OrderType::Patch
            ? $this->expected_delivery
            : $this->time_frame;
    }

    /** Where due_at comes from — the two SLA tiers converge here. */
    public function computeDueAt(): ?Carbon
    {
        $base = $this->created_at ?? now();

        return match (true) {
            $this->expected_delivery !== null => $base->copy()->addDays($this->expected_delivery->slaDays()),
            $this->time_frame !== null => $base->copy()->addHours($this->time_frame->slaHours()),
            default => null,
        };
    }
}
