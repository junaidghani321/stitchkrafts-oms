<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\OrderActivity;
use App\Models\OrderFile;
use App\Models\OrderMessage;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

/**
 * Final approval: the gate between "the employee says it is finished" and
 * "the customer is told their files are ready".
 *
 * An employee marking their last task complete parks the order at
 * PendingApproval (see TaskAssignmentService::complete). Until an admin or
 * manager signs it off here, the customer hears nothing and the order is not
 * Completed — so nothing can be invoiced or delivered off the back of work
 * nobody has looked at.
 *
 * The approver may correct the deliverable on the way through, which is what
 * replaceDeliverable() is for.
 */
class OrderApprovalService
{
    public function __construct(
        private readonly OrderFileService $files,
        private readonly OrderNotifier $notifier,
    ) {}

    /**
     * Sign the work off. The order becomes Completed and the customer is told,
     * which is exactly what used to happen the moment the employee clicked
     * "Mark as complete".
     */
    public function approve(Order $order, User $approver, ?string $note = null): Order
    {
        DB::transaction(function () use ($order, $approver, $note) {
            $was = $order->status;
            $approvedAt = now();

            $order->forceFill([
                'status' => OrderStatus::Completed->value,
                'completed_at' => $order->completed_at ?? $approvedAt,
                'approved_by_id' => $approver->id,
                'approved_at' => $approvedAt,
                'approval_note' => $note,
            ])->save();

            /*
             | Releasing the files to the customer IS the approval. Until this
             | runs they are handed in but invisible to them; see
             | Order::approvedDeliverables().
             |
             | Scoped to the still-unapproved ones so a re-approval after a
             | revision does not restamp files the customer already has, and
             | so their original approval timestamps stay truthful.
             */
            $this->releaseFiles($order, $approvedAt);

            OrderActivity::create([
                'order_id' => $order->id,
                'user_id' => $approver->id,
                'event' => OrderActivity::ORDER_APPROVED,
                'description' => 'Final files approved'.($note ? " — {$note}" : ''),
                'properties' => [
                    'old' => $was->value,
                    'new' => OrderStatus::Completed->value,
                    'note' => $note,
                ],
            ]);
        });

        // Outside the transaction: an SMTP timeout must not undo the approval.
        $this->notifier->orderCompleted($order->loadMissing('customer'), $approver);

        return $order;
    }

    /**
     * Stamp everything this order is waiting on as approved.
     *
     * Covers both the deliverables hanging off the order and the revision
     * deliverables hanging off the message they were handed in with, since an
     * order can reach approval by either route.
     */
    private function releaseFiles(Order $order, \DateTimeInterface $approvedAt): void
    {
        OrderFile::query()
            ->awaitingApproval()
            ->where(function (Builder $outer) use ($order) {
                // Attached to the order itself...
                $outer->where(function (Builder $q) use ($order) {
                    $q->where('attachable_type', $order->getMorphClass())
                        ->where('attachable_id', $order->id);
                })
                    // ...or to one of its messages, which is where a revision
                    // deliverable lands. Both arms are nested so the OR cannot
                    // leak across the type check.
                    ->orWhere(function (Builder $q) use ($order) {
                        $q->where('attachable_type', (new OrderMessage)->getMorphClass())
                            ->whereIn('attachable_id', $order->messages()->select('id'));
                    });
            })
            ->update(['approved_at' => $approvedAt]);
    }

    /**
     * Swap one of the submitted files for the approver's own version.
     *
     * The employee's original is deleted, bytes included — a file an approver
     * rejected should not stay downloadable. The activity row written here is
     * what preserves the record of what was handed in.
     */
    public function replaceDeliverable(OrderFile $file, UploadedFile $upload, User $actor): OrderFile
    {
        return DB::transaction(function () use ($file, $upload, $actor) {
            $was = $file->original_name;
            $replacement = $this->files->replace($file, $upload, $actor);
            $order = $replacement->order();

            if ($order !== null) {
                OrderActivity::create([
                    'order_id' => $order->id,
                    'user_id' => $actor->id,
                    'event' => OrderActivity::FILE_REPLACED,
                    'description' => "Final file {$was} replaced with {$replacement->original_name}",
                    'subject_type' => $replacement->getMorphClass(),
                    'subject_id' => $replacement->id,
                    'properties' => [
                        'superseded_file_id' => $file->id,
                        'superseded_name' => $was,
                        'replacement_name' => $replacement->original_name,
                    ],
                ]);
            }

            return $replacement;
        });
    }
}
