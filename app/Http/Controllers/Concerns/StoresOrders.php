<?php

namespace App\Http\Controllers\Concerns;

use App\Enums\OrderType;
use App\Http\Requests\Orders\UpdateOrderRequest;
use App\Models\Order;
use App\Models\OrderActivity;
use App\Models\OrderFile;
use App\Services\OrderFileService;
use App\Services\OrderNotifier;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\DB;

/**
 * The one place an order is created or edited, shared by the customer and
 * staff controllers so the two can never diverge on ownership, validation or
 * file handling.
 */
trait StoresOrders
{
    /**
     * @param  int|null  $customerId  null means "the signed-in customer"
     */
    protected function persistOrder(FormRequest $request, OrderType $type, ?int $customerId = null): Order
    {
        // validated(), never all(): only rule-covered keys survive, so a
        // customer_id posted to a customer form is dropped here.
        $data = $request->validated();
        $actor = $request->user();

        unset($data['artwork']);

        $order = DB::transaction(function () use ($data, $type, $customerId, $actor, $request) {
            $order = new Order($data);

            $order->type = $type;
            $order->customer_id = $customerId ?? $actor->id;

            // Only record created_by when someone acted on another's behalf.
            $order->created_by_id = $order->customer_id === $actor->id ? null : $actor->id;

            $order->save();

            // due_at needs created_at, so it is set once the row exists.
            $order->due_at = $order->computeDueAt();
            $order->saveQuietly();

            app(OrderFileService::class)->store(
                $request->file('artwork', []),
                $order,
                OrderFile::CATEGORY_ARTWORK,
                $actor,
            );

            return $order;
        });

        $order->refresh()->loadMissing('customer');

        app(OrderNotifier::class)->orderPlaced($order, $actor);

        return $order;
    }

    /**
     * Apply an edit. UpdateOrderRequest derives its rules from the bound
     * order's type, so create and edit validate identically.
     */
    protected function applyUpdate(UpdateOrderRequest $request, Order $order): Order
    {
        $data = $request->validated();

        // Neither the type nor the owner is editable — changing either would
        // re-home the order and invalidate every ownership check already made.
        unset($data['artwork'], $data['type'], $data['customer_id']);

        return DB::transaction(function () use ($order, $data, $request) {
            $order->fill($data);
            $order->due_at = $order->computeDueAt();
            $order->save();

            // New artwork is additive; existing files are never dropped.
            if ($request->hasFile('artwork')) {
                app(OrderFileService::class)->store(
                    $request->file('artwork', []),
                    $order,
                    OrderFile::CATEGORY_ARTWORK,
                    $request->user(),
                );
            }

            OrderActivity::create([
                'order_id' => $order->id,
                'user_id' => $request->user()->id,
                'event' => OrderActivity::ORDER_UPDATED,
                'description' => 'Order details were updated',
            ]);

            return $order;
        });
    }

    /**
     * Everything the shared detail partial renders, eager-loaded in one pass.
     *
     * `type` is included in every constrained user select on purpose: the User
     * model's role accessor reads that column, and omitting it would silently
     * report every name on the page as a customer.
     */
    protected function loadDetail(Order $order): Order
    {
        return $order->load([
            'customer:id,name,type,company',
            'pricedBy:id,name,type',
            'approvedBy:id,name,type',
            'artwork',
            'deliverables',
            /*
             | The other half of "what is waiting on approval": work handed in
             | for a revision hangs off the reply, not the order. The approval
             | panel reads both through Order::deliverablesAwaitingApproval().
             */
            'revisionDeliverables',
            'tasks.employee:id,name,type',
            'activities' => fn ($query) => $query->with('user:id,name,type')->latest(),
            /*
             | Revisions are kind = revision rows in here, so the detail
             | partial filters this collection for its Revisions card rather
             | than querying for them separately. `attachments` is what the
             | customer sent with the request; `deliverables` is the work
             | handed in, which the Delivered Files card reads.
             |
             | visibleTo() is what keeps an unassigned revision off the
             | employee's screen — every card reads this one collection, so
             | the rule is applied once here rather than in each of them.
             */
            'messages' => fn ($query) => $query->with([
                'attachments',
                'deliverables',
                'latestTask.employee:id,name,type',
            ])->visibleTo(request()->user())->oldest(),
        ]);
    }
}
