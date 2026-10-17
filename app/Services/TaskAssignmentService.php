<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Enums\Priority;
use App\Enums\RevisionStatus;
use App\Enums\TaskStatus;
use App\Enums\TaskType;
use App\Models\Order;
use App\Models\OrderActivity;
use App\Models\OrderFile;
use App\Models\OrderTask;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

/**
 * Assigning, extending and completing a task each touch order_tasks, the
 * parent order's status and the activity log together. Keeping that in one
 * transactional place is what stops the three controllers drifting apart.
 */
class TaskAssignmentService
{
    public function __construct(
        private readonly OrderFileService $files,
        private readonly OrderNotifier $notifier,
    ) {}

    /**
     * `task_type` is optional and normally absent: the type of work follows
     * the order it is work on. See taskTypeFor().
     *
     * @param  array{employee_id:int, task_type?:string, duration_hours:int, priority?:string, instructions?:?string, notify?:bool, order_message_id?:?int}  $data
     */
    public function assign(Order $order, array $data, User $assignedBy): OrderTask
    {
        return DB::transaction(function () use ($order, $data, $assignedBy) {
            $assignedAt = now();
            $duration = max(1, (int) $data['duration_hours']);
            $taskType = $this->taskTypeFor($order, $data);

            $task = OrderTask::create([
                'order_id' => $order->id,
                'order_message_id' => $data['order_message_id'] ?? null,
                'employee_id' => $data['employee_id'],
                'assigned_by_id' => $assignedBy->id,
                'task_type' => $taskType,
                'duration_hours' => $duration,
                'priority' => Priority::from($data['priority'] ?? Priority::Normal->value),
                'instructions' => $data['instructions'] ?? null,
                'assigned_at' => $assignedAt,
                'deadline_at' => $assignedAt->copy()->addHours($duration),
                'notify' => (bool) ($data['notify'] ?? true),
            ]);

            // Assigning moves a fresh order to Assigned — not to InProgress,
            // which is the employee's to declare when they actually start.
            // An order already under way (or in revision) is left alone so a
            // second task cannot drag it backwards.
            if ($order->status === OrderStatus::Pending) {
                $this->moveOrderTo($order, OrderStatus::Assigned, $assignedBy);
            }

            // A revision task picks the revision up off the pending pile.
            if ($task->order_message_id !== null) {
                // forceFill: `status` is deliberately not mass-assignable.
                $task->revision?->forceFill(['status' => RevisionStatus::InProgress])->save();
            }

            $this->log($order, $assignedBy, OrderActivity::TASK_ASSIGNED,
                "Task assigned to {$task->employee->name} ({$duration}h allowed)", $task,
                ['task_type' => $taskType->value, 'duration_hours' => $duration]);

            // Respects the "notify the employee" switch on the assign dialog.
            $this->notifier->taskAssigned($task);

            return $task;
        });
    }

    /**
     * The type of work is a property of the order, not a choice the assigner
     * makes: a digitizing order gets a digitizing task. That is why the assign
     * dialog only reports it and AssignTaskRequest no longer accepts it.
     *
     * Two callers still name a type: RevisionService passes `revision`, and
     * anything carrying an order_message_id is revision work whatever the
     * parent order's type says.
     *
     * @param  array{task_type?:string, order_message_id?:?int}  $data
     */
    private function taskTypeFor(Order $order, array $data): TaskType
    {
        if (isset($data['task_type'])) {
            return TaskType::from($data['task_type']);
        }

        if (($data['order_message_id'] ?? null) !== null) {
            return TaskType::Revision;
        }

        return TaskType::fromOrderType($order->type);
    }

    /**
     * Push the deadline out. duration_hours stays the honest record of the
     * total allowance, and deadline_at is recomputed from it so the views'
     * assigned_at + duration arithmetic keeps agreeing with the stored value.
     */
    public function extend(OrderTask $task, int $additionalHours, User $actor): OrderTask
    {
        return DB::transaction(function () use ($task, $additionalHours, $actor) {
            $was = $task->duration_hours;

            $task->duration_hours = $was + max(1, $additionalHours);
            $task->deadline_at = $task->assigned_at->copy()->addHours($task->duration_hours);
            $task->save();

            $this->log($task->order, $actor, OrderActivity::TASK_EXTENDED,
                "Task deadline extended from {$was}h to {$task->duration_hours}h", $task,
                ['old' => $was, 'new' => $task->duration_hours]);

            return $task;
        });
    }

    public function start(OrderTask $task, User $actor): OrderTask
    {
        return DB::transaction(function () use ($task, $actor) {
            $task->forceFill(['status' => TaskStatus::InProgress])->save();

            $this->moveOrderTo($task->order, $task->task_type->orderStatusOnStart(), $actor);

            $this->log($task->order, $actor, OrderActivity::TASK_STARTED, 'Work started on the task', $task);

            return $task;
        });
    }

    /**
     * @param  array<int, UploadedFile>  $files
     */
    public function complete(OrderTask $task, ?string $note, array $files, User $actor): OrderTask
    {
        return DB::transaction(function () use ($task, $note, $files, $actor) {
            $completedAt = now();

            $task->forceFill([
                'status' => TaskStatus::Completed,
                'completed_at' => $completedAt,
                'completion_note' => $note,
            ])->save();

            /*
             | Closing a revision task closes the revision with it. The note
             | and the produced files become a reply in the order thread,
             | parented to the revision — which is what gives those files a
             | download link. RevisionService owns that so the hand-close path
             | and this one cannot drift.
             */
            $revision = $task->order_message_id !== null
                ? app(RevisionService::class)->closeFor($task, $note, $files, $actor)
                : null;

            if ($revision === null && $files !== []) {
                $this->files->store($files, $task->order, OrderFile::CATEGORY_DELIVERABLE, $actor);
            }

            /*
             | The work is only submitted once nothing else is outstanding on
             | the order — and submitted is not finished. It parks at
             | PendingApproval for an admin or manager to sign off, which is
             | what tells the customer their files are ready. See
             | OrderApprovalService.
             |
             | An unassigned revision still counts as outstanding, so a second
             | revision raised but not yet given to anybody holds the order in
             | Revision rather than letting this task's completion carry it
             | out. Mirrors RevisionService::settleOrder().
             */
            $submitted = ! $task->order->tasks()->open()->exists()
                && ! $task->order->messages()->revisions()->open()->exists()
                && $this->moveOrderTo($task->order, OrderStatus::PendingApproval, $actor);

            if ($submitted) {
                $this->log($task->order, $actor, OrderActivity::ORDER_SUBMITTED,
                    'Work submitted for final approval', $task);
            }

            /*
             | Staff hear about this once. "Approve this order" is the more
             | useful message of the two, so it displaces the per-task one.
             |
             | A closed revision tells the customer directly. Without this the
             | customer is never told their revision was done: the reply the
             | note became is posted with announce: false, precisely so it does
             | not mail them twice.
             */
            match (true) {
                $submitted => $this->notifier->orderAwaitingApproval($task->order, $actor),
                $revision !== null => $this->notifier->revisionCompleted($revision),
                default => $this->notifier->taskCompleted($task),
            };

            $sla = $task->sla();

            $this->log($task->order, $actor, OrderActivity::TASK_COMPLETED,
                $sla->isBreached()
                    ? "Task completed {$sla->formattedDelta()} over the allowed time"
                    : "Task completed {$sla->formattedDelta()} inside the allowed time",
                $task,
                ['sla' => $sla->status->value, 'minutes_taken' => $sla->completedAfter]);

            return $task;
        });
    }

    /**
     * Guarded status move — an illegal transition is ignored rather than
     * throwing, so a completed order is never dragged backwards by a stray
     * task event.
     *
     * Returns whether the order actually moved, which is what callers key
     * their notifications off: no move, no announcement.
     */
    private function moveOrderTo(Order $order, OrderStatus $next, User $actor, $completedAt = null): bool
    {
        if ($order->status === $next || ! $order->status->canTransitionTo($next)) {
            return false;
        }

        $was = $order->status;

        $order->status = $next;

        if ($next === OrderStatus::Completed) {
            $order->completed_at = $completedAt ?? now();
        }

        $order->save();

        $this->log($order, $actor, OrderActivity::ORDER_STATUS_CHANGED,
            "Order moved from {$was->label()} to {$next->label()}", null,
            ['old' => $was->value, 'new' => $next->value]);

        return true;
    }

    private function log(Order $order, User $actor, string $event, string $description, $subject = null, array $properties = []): void
    {
        OrderActivity::create([
            'order_id' => $order->id,
            'user_id' => $actor->id,
            'event' => $event,
            'description' => $description,
            'subject_type' => $subject?->getMorphClass(),
            'subject_id' => $subject?->id,
            'properties' => $properties ?: null,
        ]);
    }
}
