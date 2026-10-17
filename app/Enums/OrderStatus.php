<?php

namespace App\Enums;

/**
 * The detailed work pipeline.
 *
 * The existing views only know three badges — pending / progress / done — so
 * badge() maps this richer vocabulary down onto them. That is what lets the
 * Blade markup and style.css stay untouched.
 */
enum OrderStatus: string
{
    case Pending = 'pending';
    case Assigned = 'assigned';
    case InProgress = 'in_progress';
    case QualityCheck = 'quality_check';
    case Revision = 'revision';
    case PendingApproval = 'pending_approval';
    case Completed = 'completed';
    case Delivered = 'delivered';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pending',
            self::Assigned => 'Assigned',
            self::InProgress => 'In Progress',
            self::QualityCheck => 'Quality Check',
            self::Revision => 'In Revision',
            self::PendingApproval => 'Pending Approval',
            self::Completed => 'Completed',
            self::Delivered => 'Delivered',
            self::Cancelled => 'Cancelled',
        };
    }

    /**
     * The three-value vocabulary used by the user, admin and manager indexes.
     * Both Completed and Delivered map to `done`, which is what keeps the
     * existing `@if ($order['status'] === 'done')` revision guard working.
     *
     * PendingApproval is `progress` deliberately. The work is through
     * production but has not been signed off, so it is not `done` — the
     * customer must not be shown finished files that nobody approved, and the
     * revision guard above keys on `done`.
     */
    public function badge(): string
    {
        return match ($this) {
            self::Pending => 'pending',
            self::Assigned, self::InProgress, self::QualityCheck,
            self::Revision, self::PendingApproval => 'progress',
            self::Completed, self::Delivered => 'done',
            self::Cancelled => 'cancelled',
        };
    }

    /**
     * What the customer is told their order is doing.
     *
     * Only PendingApproval differs: to a customer, "Pending Approval" reads
     * as though the approval were theirs to give, when it is an internal
     * sign-off. Matches the wording on their dashboard card.
     */
    public function customerLabel(): string
    {
        return $this === self::PendingApproval ? 'Final Checks' : $this->label();
    }

    /**
     * The employee index has its own four-value map with no `pending`
     * and an extra `revision` — see employee/orders/index.blade.php.
     */
    public function employeeBadge(): string
    {
        return match ($this) {
            self::Revision => 'revision',
            self::Completed, self::Delivered => 'done',
            self::Cancelled => 'cancelled',
            default => 'progress',
        };
    }

    /** Revisions only make sense once the work has been delivered. */
    public function isRevisable(): bool
    {
        return in_array($this, [self::Completed, self::Delivered], true);
    }

    public function isOpen(): bool
    {
        return ! in_array($this, [self::Delivered, self::Cancelled], true);
    }

    public function canTransitionTo(self $next): bool
    {
        return in_array($next, $this->allowedTransitions(), true);
    }

    /** @return array<int, self> */
    public function allowedTransitions(): array
    {
        return match ($this) {
            self::Pending => [self::Assigned, self::Cancelled],
            // PendingApproval is reachable directly: the employee task list
            // offers "Mark as complete" without a separate start step, so
            // finishing work that was never formally started is the normal
            // path. Completed stays reachable for staff, who are the approvers
            // anyway — the gate is there to stop work reaching the customer
            // unseen, not to stop an admin closing an order by hand.
            self::Assigned => [self::InProgress, self::QualityCheck, self::PendingApproval, self::Completed, self::Pending, self::Cancelled],
            self::InProgress => [self::QualityCheck, self::PendingApproval, self::Completed, self::Revision],
            self::QualityCheck => [self::PendingApproval, self::Completed, self::Revision, self::InProgress],
            self::Revision => [self::InProgress, self::PendingApproval, self::Completed],
            // Approving ends at Completed. InProgress and Revision are the
            // way back when the submitted work is not good enough — there is
            // no dedicated reject action, the status buttons cover it.
            self::PendingApproval => [self::Completed, self::InProgress, self::Revision, self::Cancelled],
            self::Completed => [self::Delivered, self::Revision],
            self::Delivered => [self::Revision],
            self::Cancelled => [],
        };
    }

    /**
     * The filter options the customer/admin/manager index dropdowns offer,
     * keyed by badge() rather than by case — several statuses share a badge.
     */
    public static function badgeOptions(): array
    {
        return [
            'pending' => 'Pending',
            'progress' => 'In Progress',
            'done' => 'Completed',
            'cancelled' => 'Cancelled',
        ];
    }

    /**
     * Cancellable states. Kept next to allowedTransitions() so the two cannot
     * disagree about when cancelling is legal.
     */
    public function isCancellable(): bool
    {
        return in_array(self::Cancelled, $this->allowedTransitions(), true);
    }
}
