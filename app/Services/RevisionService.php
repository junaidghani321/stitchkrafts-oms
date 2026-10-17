<?php

namespace App\Services;

use App\Enums\MessageKind;
use App\Enums\OrderStatus;
use App\Enums\Priority;
use App\Enums\RevisionReason;
use App\Enums\RevisionStatus;
use App\Enums\TaskType;
use App\Models\Order;
use App\Models\OrderActivity;
use App\Models\OrderFile;
use App\Models\OrderMessage;
use App\Models\OrderTask;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

/**
 * The revision lifecycle. A revision is a kind = revision order message, so
 * the request, the work note and the rejection all land in the one thread the
 * order is discussed in; ConversationService does the writing.
 *
 * Notifications fire outside the transaction, following OrderApprovalService:
 * an SMTP timeout must not undo the work.
 *
 * TaskAssignmentService is deliberately not a constructor dependency: assign()
 * needs it, and its complete() needs closeFor() back here, so injecting both
 * ways is a cycle the container throws on. Resolved with app() at the point of
 * use, as StaffOrderController and DashboardService already do.
 */
class RevisionService
{
    public function __construct(
        private readonly ConversationService $conversations,
        private readonly OrderNotifier $notifier,
    ) {}

    /**
     * Raise a revision against an order and push the order back into the
     * Revision state.
     *
     * @param  array{reason:string, priority?:string, due_date?:?string, comments:string, source?:string}  $data
     * @param  array<int, UploadedFile>  $files
     */
    public function request(Order $order, array $data, array $files, User $requestedBy): OrderMessage
    {
        $revision = DB::transaction(function () use ($order, $data, $files, $requestedBy) {
            $revision = $this->conversations->post(
                $order,
                $requestedBy,
                $data['comments'],
                $files,
                [
                    'kind' => MessageKind::Revision,
                    'revision_number' => $this->nextNumberFor($order),
                    'source' => $data['source'] ?? OrderMessage::SOURCE_CUSTOMER,
                    'reason' => RevisionReason::from($data['reason']),
                    'priority' => Priority::from($data['priority'] ?? Priority::Normal->value),
                    'due_date' => $data['due_date'] ?? null,
                    'status' => RevisionStatus::Pending,
                ],
                OrderFile::CATEGORY_REVISION_REQUEST,
            );

            // Delivered -> Revision is a legal edge; the guard is here so a
            // second revision on an already-in-revision order is a no-op.
            if ($order->status->canTransitionTo(OrderStatus::Revision)) {
                $order->forceFill(['status' => OrderStatus::Revision])->save();
            }

            $this->log($revision, $requestedBy, OrderActivity::REVISION_REQUESTED,
                "Revision {$revision->revision_number} was requested",
                ['reason' => $revision->reason->value, 'priority' => $revision->priority->value]);

            return $revision;
        });

        $this->notifier->revisionRequested($revision);

        return $revision;
    }

    /**
     * Assignment is just a task with task_type = revision, so revisions and
     * orders share one SLA engine and one "who is working on this".
     *
     * @param  array{employee_id:int, duration_hours:int, priority?:string, instructions?:?string, notify?:bool}  $data
     */
    public function assign(OrderMessage $revision, array $data, User $assignedBy): OrderMessage
    {
        DB::transaction(function () use ($revision, $data, $assignedBy) {
            app(TaskAssignmentService::class)->assign(
                $revision->order,
                $data + [
                    'task_type' => TaskType::Revision->value,
                    'order_message_id' => $revision->id,
                ],
                $assignedBy,
            );

            $this->log($revision, $assignedBy, OrderActivity::REVISION_ASSIGNED,
                "Revision {$revision->revision_number} assigned");
        });

        $this->notifier->revisionAssigned($revision, User::find($data['employee_id']));

        return $revision->refresh();
    }

    /**
     * Close a revision directly. The usual path is the employee completing the
     * revision task, which TaskAssignmentService::complete() routes through
     * closeFor() — this covers an admin closing one out by hand.
     *
     * @param  array<int, UploadedFile>  $files
     */
    public function complete(OrderMessage $revision, ?string $note, array $files, User $actor): OrderMessage
    {
        /*
         | An employee closing out their own revision is handing work in, not
         | finishing an order — it meets the same approval gate a completed
         | task does. Staff closing one out by hand ARE the approval, so for
         | them nothing changes.
         */
        $forApproval = $actor->isEmployee();

        $submitted = DB::transaction(function () use ($revision, $note, $files, $actor, $forApproval) {
            $this->settle($revision, $note, $files, $actor);

            $submitted = $this->settleOrder(
                $revision,
                $forApproval ? OrderStatus::PendingApproval : OrderStatus::Completed,
            ) && $forApproval;

            $this->log($revision, $actor, OrderActivity::REVISION_COMPLETED,
                "Revision {$revision->revision_number} was completed");

            if ($submitted) {
                $this->log($revision, $actor, OrderActivity::ORDER_SUBMITTED,
                    'Revision work submitted for final approval');
            }

            return $submitted;
        });

        if ($submitted) {
            // The customer hears nothing until it is signed off.
            $this->notifier->orderAwaitingApproval($revision->order, $actor);
        } else {
            $this->notifier->revisionCompleted($revision);
        }

        return $revision;
    }

    /**
     * Called by TaskAssignmentService::complete() when a revision task closes.
     * Separate from complete() because that path has already written its own
     * task activity row and decided the order's next status.
     *
     * The caller notifies: it alone knows whether the order was submitted for
     * approval, which displaces the per-revision mail.
     *
     * @param  array<int, UploadedFile>  $files
     */
    public function closeFor(OrderTask $task, ?string $note, array $files, User $actor): ?OrderMessage
    {
        $revision = $task->revision;

        if ($revision === null) {
            return null;
        }

        $this->settle($revision, $note, $files, $actor, $task->completed_at);

        $this->log($revision, $actor, OrderActivity::REVISION_COMPLETED,
            "Revision {$revision->revision_number} was completed");

        return $revision;
    }

    public function reject(OrderMessage $revision, string $reason, User $actor): OrderMessage
    {
        DB::transaction(function () use ($revision, $reason, $actor) {
            // The reason is a reply in the thread, not a column, so it reads
            // where the request does and its author is on the record.
            $this->conversations->post(
                $revision->order,
                $actor,
                $reason,
                facet: ['parent_id' => $revision->id],
            );

            $revision->forceFill(['status' => RevisionStatus::Rejected])->save();

            // Rejecting restores the order to where it was before the revision
            // was raised. An admin did it, so there is nothing to approve.
            $this->settleOrder($revision, OrderStatus::Completed);

            $this->log($revision, $actor, OrderActivity::REVISION_REJECTED,
                "Revision {$revision->revision_number} was rejected");
        });

        $this->notifier->revisionRejected($revision);

        return $revision;
    }

    /**
     * The shared close: the note and any produced files become a reply in the
     * thread, parented to the revision, and the revision goes to Completed.
     * The reply is what gives those files a download link.
     *
     * @param  array<int, UploadedFile>  $files
     */
    private function settle(
        OrderMessage $revision,
        ?string $note,
        array $files,
        User $actor,
        ?\DateTimeInterface $completedAt = null,
    ): void {
        $this->conversations->post(
            $revision->order,
            $actor,
            $note ?: 'Revision work submitted.',
            $files,
            ['parent_id' => $revision->id],
            OrderFile::CATEGORY_REVISION_DELIVERABLE,
        );

        $revision->forceFill([
            'status' => RevisionStatus::Completed,
            'completed_at' => $completedAt ?? now(),
        ])->save();
    }

    /**
     * Once no revision is still open, the order leaves Revision for $next —
     * Completed when staff closed the work out, PendingApproval when an
     * employee submitted it. See complete().
     *
     * Returns whether the order actually moved: an order with another revision
     * still open stays put, and so does one whose current status has no legal
     * edge to $next.
     */
    private function settleOrder(OrderMessage $revision, OrderStatus $next): bool
    {
        $order = $revision->order;

        if ($order->messages()->revisions()->open()->exists() || ! $order->status->canTransitionTo($next)) {
            return false;
        }

        $order->forceFill(['status' => $next->value])->save();

        return true;
    }

    /**
     * Per-order numbering: ADDO-361-R1, ADDO-361-R2. Counted inside the
     * caller's transaction, so contention is per-order and the unique index on
     * revision_number is the backstop. Deliberately not derived from the
     * order_messages key, which every comment would gap.
     */
    private function nextNumberFor(Order $order): string
    {
        $n = $order->messages()->revisions()->count() + 1;

        return "{$order->order_number}-R{$n}";
    }

    /** @param  array<string, mixed>  $properties */
    private function log(OrderMessage $revision, User $actor, string $event, string $description, array $properties = []): void
    {
        OrderActivity::create([
            'order_id' => $revision->order_id,
            'user_id' => $actor->id,
            'event' => $event,
            'description' => $description,
            'subject_type' => $revision->getMorphClass(),
            'subject_id' => $revision->id,
            'properties' => $properties === [] ? null : $properties,
        ]);
    }
}
