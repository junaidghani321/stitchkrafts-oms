<?php

namespace App\Policies;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\User;

/**
 * The middleware partitions the URL space by role; this partitions the
 * records. Both are needed — `user-access:user` stops a customer reaching
 * /admin/orders, but only this stops customer A opening customer B's order.
 */
class OrderPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Order $order): bool
    {
        return match (true) {
            $user->isAdminOrManager() => true,
            $user->isCustomer() => $order->customer_id === $user->id,
            $user->isEmployee() => $this->hasTaskOn($user, $order),
            default => false,
        };
    }

    /**
     * Customers may correct their own order only while nobody has started
     * work on it. Once it is assigned, changes go through a revision.
     */
    public function update(User $user, Order $order): bool
    {
        return match (true) {
            $user->isAdminOrManager() => true,
            $user->isCustomer() => $order->customer_id === $user->id
                                         && $order->status === OrderStatus::Pending,
            default => false,
        };
    }

    public function delete(User $user, Order $order): bool
    {
        return $user->isAdmin();
    }

    /**
     * Cancelling ends the order for good — OrderStatus::Cancelled has no
     * transitions out of it.
     *
     * The customer may call off their own order only while it is still
     * Pending, which is the same line update() draws: once somebody has been
     * assigned, stopping the job is a conversation, not a button. Admin and
     * manager may also cancel work already assigned.
     */
    public function cancel(User $user, Order $order): bool
    {
        if (! $order->status->isCancellable()) {
            return false;
        }

        return match (true) {
            $user->isAdminOrManager() => true,
            $user->isCustomer() => $order->customer_id === $user->id
                                         && $order->status === OrderStatus::Pending,
            default => false,
        };
    }

    /** Pricing is money: admin and manager only, never the customer. */
    public function price(User $user, Order $order): bool
    {
        return $user->isAdminOrManager();
    }

    public function updateStatus(User $user, Order $order): bool
    {
        return $user->isAdminOrManager();
    }

    public function assignTask(User $user, Order $order): bool
    {
        return $user->isAdminOrManager();
    }

    /**
     * Final approval. Admin and manager only, and only while the order is
     * actually waiting on it — an order already signed off must not be
     * approved twice, and one still being worked on has nothing to approve.
     */
    public function approve(User $user, Order $order): bool
    {
        return $user->isAdminOrManager()
            && $order->status === OrderStatus::PendingApproval;
    }

    /** Revisions only make sense once the work has been delivered. */
    public function requestRevision(User $user, Order $order): bool
    {
        if (! $order->status->isRevisable()) {
            return false;
        }

        return match (true) {
            $user->isAdminOrManager() => true,
            $user->isCustomer() => $order->customer_id === $user->id,
            default => false,
        };
    }

    /** Uploading finished files is the employee's job on their own tasks. */
    public function uploadDeliverable(User $user, Order $order): bool
    {
        return $user->isAdminOrManager() || $this->hasTaskOn($user, $order);
    }

    private function hasTaskOn(User $user, Order $order): bool
    {
        return $order->tasks()->where('employee_id', $user->id)->exists();
    }
}
