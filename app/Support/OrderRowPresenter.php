<?php

namespace App\Support;

use App\Models\Order;
use App\Models\OrderTask;
use App\Models\Quote;
use Illuminate\Support\Collection;

/**
 * Turns models into the exact array shapes the existing Blade files already
 * consume, so wiring a screen up means deleting its @php literal and nothing
 * else.
 *
 * The keys here are a contract with the views. Two in particular:
 *
 *  - `price` is coalesced to 0, never null. The views call number_format() on
 *    it and compare it to 0 for the greyed-out styling; null would raise a
 *    deprecation on every un-priced order.
 *  - dates arrive pre-formatted ('d-m-Y', 'd-m-Y H:i') because the views echo
 *    them raw.
 */
class OrderRowPresenter
{
    /**
     * user/orders/index.blade.php
     * keys: no, type, design, date, status, price (+ id for the action links)
     */
    public function forCustomer(Collection $orders): Collection
    {
        return $orders->map(fn (Order $order) => [
            'id' => $order->id,
            'no' => $order->order_number,
            'type' => $order->type->value,
            'design' => $order->design_name,
            'date' => $order->created_at->format('d-m-Y'),
            'status' => $order->status->badge(),
            'price' => $order->display_price,
        ]);
    }

    /**
     * admin/orders/index.blade.php and manager/orders/index.blade.php
     * Same as the customer shape plus `customer`.
     */
    public function forStaff(Collection $orders): Collection
    {
        return $orders->map(fn (Order $order) => [
            'id' => $order->id,
            'no' => $order->order_number,
            'customer' => $order->customer?->name ?? '—',
            'type' => $order->type->value,
            'design' => $order->design_name,
            'date' => $order->created_at->format('d-m-Y'),
            'status' => $order->status->badge(),
            'price' => $order->display_price,
        ]);
    }

    /**
     * employee/orders/index.blade.php
     * Adds `due` and uses the employee status vocabulary, which has a fourth
     * value (`revision`) and no `pending`.
     *
     * Carries neither `price` nor `customer`: what an order is worth and who
     * it is for are both none of an employee's business. Left out here rather
     * than hidden in the view, so neither reaches the page at all.
     */
    public function forEmployee(Collection $orders): Collection
    {
        return $orders->map(fn (Order $order) => [
            'id' => $order->id,
            'no' => $order->order_number,
            'type' => $order->type->value,
            'design' => $order->design_name,
            'date' => $order->created_at->format('d-m-Y'),
            'due' => $order->due_at?->format('d-m-Y H:i') ?? '—',
            'status' => $order->status->employeeBadge(),
        ]);
    }

    /**
     * admin/tasks/index.blade.php and manager/tasks/index.blade.php
     *
     * Emits assigned_ago / duration / completed_after — the same units the
     * mock data used — so the existing @php SLA block keeps working verbatim.
     * `sla` is supplied alongside for views that have moved on to it.
     *
     * Note `employee_id`: the filter dropdown currently keys on employee NAME,
     * which collides for two people with the same name. The id is provided so
     * the markup can switch to it.
     */
    public function tasksFor(Collection $tasks): Collection
    {
        return $tasks->map(function (OrderTask $task) {
            $sla = $task->sla();

            return [
                'id' => $task->id,
                'order_id' => $task->order_id,
                'order' => $task->order?->order_number ?? '—',
                'design' => $task->order?->design_name ?? '—',
                'type' => $task->task_type->value,
                'employee' => $task->employee?->name ?? '—',
                'employee_id' => $task->employee_id,
                'assigned_ago' => $sla->assignedAgo,
                'duration' => $task->duration_hours,
                'completed_after' => $sla->completedAfter,
                'priority' => $task->priority->value,
                'instructions' => $task->instructions,
                'sla' => $sla,
            ];
        });
    }

    /*
    |--------------------------------------------------------------------------
    | Quotes
    |--------------------------------------------------------------------------
    |
    | The quote views keep TWO maps — $statusLabels and $statusStyles — because
    | the quote vocabulary (pending/quoted/approved) does not line up with the
    | shared badge class names. `status` below is the raw value; the view looks
    | the badge class up from $statusStyles.
    |
    */

    /** user/quotes/index: no, type, design, date, status, price (+ id). */
    public function quotesForCustomer(Collection $quotes): Collection
    {
        return $quotes->map(fn (Quote $quote) => [
            'id' => $quote->id,
            'no' => $quote->quote_number,
            'type' => $quote->type->value,
            'design' => $quote->design_name,
            'date' => $quote->created_at->format('d-m-Y'),
            'status' => $quote->status->value,
            'price' => $quote->display_price,
            'decidable' => $quote->status->isDecidable(),
        ]);
    }

    /** admin/manager/employee quote lists add the customer column. */
    public function quotesForStaff(Collection $quotes): Collection
    {
        return $quotes->map(fn (Quote $quote) => [
            'id' => $quote->id,
            'no' => $quote->quote_number,
            'customer' => $quote->customer?->name ?? '—',
            'type' => $quote->type->value,
            'design' => $quote->design_name,
            'date' => $quote->created_at->format('d-m-Y'),
            'status' => $quote->status->value,
            'price' => $quote->display_price,
            'decidable' => $quote->status->isDecidable(),
        ]);
    }

    /**
     * The order_id select options for the assign-task and request-revision
     * modals: real primary keys, with the order number only as the label.
     *
     * `type`/`type_label` are what the assign dialog reports as the task type
     * once an order is picked, so callers must select the `type` column.
     */
    public function selectOptions(Collection $orders): Collection
    {
        return $orders->map(fn (Order $order) => [
            'id' => $order->id,
            'label' => $order->order_number.' — '.$order->design_name,
            'type' => $order->type->value,
            'type_label' => $order->type->label(),
        ]);
    }
}
