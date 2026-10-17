<?php

namespace App\Services;

use App\Enums\UserType;
use App\Models\Order;
use App\Models\OrderMessage;
use App\Models\OrderTask;
use App\Models\Quote;
use App\Models\User;
use App\Notifications\OrderAwaitingApproval;
use App\Notifications\OrderCancelled;
use App\Notifications\OrderCompleted;
use App\Notifications\OrderDelivered;
use App\Notifications\OrderPlaced;
use App\Notifications\OrderPriced;
use App\Notifications\QuoteApproved;
use App\Notifications\QuotePriced;
use App\Notifications\QuoteRejected;
use App\Notifications\QuoteRequested;
use App\Notifications\RevisionAssigned;
use App\Notifications\RevisionCompleted;
use App\Notifications\RevisionRejected;
use App\Notifications\RevisionRequested;
use App\Notifications\TaskAssigned;
use App\Notifications\TaskCompleted;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Notification;

/**
 * One place that answers "who should hear about this?".
 *
 * Without this the audience rules end up duplicated across the order, task and
 * revision services and drift apart — and "the customer got told twice, the
 * manager not at all" is the kind of bug nobody reports, they just stop
 * trusting the bell.
 *
 * Every send is best-effort: a notification failure must never roll back the
 * business action that triggered it.
 */
class OrderNotifier
{
    /** Admins and managers — the people who run the floor. */
    private function staff(): Collection
    {
        return User::staff()
            ->active()
            ->whereIn('type', [UserType::Admin->value, UserType::Manager->value])
            ->get();
    }

    public function orderPlaced(Order $order, User $placedBy): void
    {
        $this->send($this->staff(), new OrderPlaced($order));

        // If an admin placed it on the customer's behalf, the customer still
        // gets their confirmation; if the customer placed it themselves they
        // do not need telling twice.
        if ($order->customer && $order->customer_id !== $placedBy->id) {
            $this->send($order->customer, new OrderPlaced($order, forCustomer: true));
        }
    }

    public function orderPriced(Order $order): void
    {
        $this->send($order->customer, new OrderPriced($order));
    }

    /**
     * Work is through production.
     *
     * Two audiences with different wording: the customer hears "your files are
     * ready", the floor hears "this one is done". $completedBy is excluded from
     * the staff copy — whoever just clicked Complete does not need telling.
     */
    public function orderCompleted(Order $order, ?User $completedBy = null): void
    {
        $this->send($order->customer, new OrderCompleted($order));

        $staff = $this->staff()->reject(fn (User $u) => $completedBy && $u->id === $completedBy->id);

        $this->send($staff, new OrderCompleted($order, forCustomer: false));
    }

    public function orderDelivered(Order $order): void
    {
        $this->send($order->customer, new OrderDelivered($order));
    }

    /**
     * The work is in but unsigned. Staff only — the customer is told nothing
     * until somebody approves it, which is the whole point of the gate.
     *
     * $submittedBy is an employee, so there is nobody to exclude from the
     * staff list the way orderCompleted() does.
     */
    public function orderAwaitingApproval(Order $order, ?User $submittedBy = null): void
    {
        $this->send($this->staff(), new OrderAwaitingApproval($order, $submittedBy));
    }

    /**
     * Everyone with a stake: the floor, the customer, and any employee who
     * was holding a task on it — their queue just changed.
     */
    public function orderCancelled(Order $order, User $cancelledBy): void
    {
        $this->send($this->staff(), new OrderCancelled($order, $cancelledBy));

        // Not when they cancelled it themselves — they just clicked the button.
        if ($order->customer && $order->customer_id !== $cancelledBy->id) {
            $this->send($order->customer, new OrderCancelled($order, $cancelledBy, forCustomer: true));
        }

        $employees = User::whereIn(
            'id',
            $order->tasks()->pluck('employee_id')->filter()->unique(),
        )->where('id', '!=', $cancelledBy->id)->get();

        $this->send($employees, new OrderCancelled($order, $cancelledBy));
    }

    /** Honours the "notify the employee" switch on the assign dialog. */
    public function taskAssigned(OrderTask $task): void
    {
        if (! $task->notify) {
            return;
        }

        $this->send($task->employee, new TaskAssigned($task));
    }

    public function taskCompleted(OrderTask $task): void
    {
        $this->send($this->staff(), new TaskCompleted($task));
    }

    public function revisionRequested(OrderMessage $revision): void
    {
        $this->send($this->staff(), new RevisionRequested($revision));
    }

    public function revisionAssigned(OrderMessage $revision, ?User $employee): void
    {
        $this->send($revision->order?->customer, new RevisionAssigned($revision));
        $this->send($employee, new RevisionAssigned($revision, forCustomer: false));
    }

    public function revisionCompleted(OrderMessage $revision): void
    {
        $this->send($revision->order?->customer, new RevisionCompleted($revision));
    }

    public function revisionRejected(OrderMessage $revision): void
    {
        $this->send($revision->order?->customer, new RevisionRejected($revision));
    }

    /*
    |--------------------------------------------------------------------------
    | Quotes
    |--------------------------------------------------------------------------
    */

    public function quoteRequested(Quote $quote): void
    {
        $this->send($this->staff(), new QuoteRequested($quote));
    }

    public function quotePriced(Quote $quote): void
    {
        $this->send($quote->customer, new QuotePriced($quote));
    }

    public function quoteApproved(Quote $quote, Order $order): void
    {
        $this->send($this->staff(), new QuoteApproved($quote, $order));
        $this->send($quote->customer, new QuoteApproved($quote, $order, forCustomer: true));
    }

    public function quoteRejected(Quote $quote): void
    {
        $this->send($this->staff(), new QuoteRejected($quote));
    }

    /**
     * Best-effort delivery.
     *
     * These are sent inline (the notifications do not implement ShouldQueue,
     * because no queue worker is guaranteed), so a failure here would
     * otherwise surface as a 500 on an action that actually succeeded.
     */
    private function send(User|Collection|null $notifiable, $notification): void
    {
        if ($notifiable === null || ($notifiable instanceof Collection && $notifiable->isEmpty())) {
            return;
        }

        try {
            Notification::send($notifiable, $notification);
        } catch (\Throwable $e) {
            report($e);
        }
    }
}
