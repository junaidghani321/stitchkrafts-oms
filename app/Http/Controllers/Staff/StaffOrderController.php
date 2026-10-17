<?php

namespace App\Http\Controllers\Staff;

use App\Enums\DeliveryStatus;
use App\Enums\OrderStatus;
use App\Enums\OrderType;
use App\Http\Controllers\Concerns\StoresOrders;
use App\Http\Controllers\Controller;
use App\Http\Requests\Orders\ApproveOrderRequest;
use App\Http\Requests\Orders\CancelOrderRequest;
use App\Http\Requests\Orders\ReplaceOrderFileRequest;
use App\Http\Requests\Orders\Staff\StoreDigitizingOrderRequest;
use App\Http\Requests\Orders\Staff\StorePatchOrderRequest;
use App\Http\Requests\Orders\Staff\StoreVectorOrderRequest;
use App\Http\Requests\Orders\UpdateOrderPriceRequest;
use App\Http\Requests\Orders\UpdateOrderRequest;
use App\Http\Requests\Orders\UpdateOrderStatusRequest;
use App\Models\Order;
use App\Models\OrderActivity;
use App\Models\OrderFile;
use App\Models\User;
use App\Services\OrderApprovalService;
use App\Services\OrderCancellationService;
use App\Services\OrderNotifier;
use App\Support\OrderRowPresenter;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Shared by admin and manager, whose order screens are byte-identical apart
 * from the breadcrumb route.
 *
 * An abstract base rather than Manager extends Admin: the latter would encode
 * "a manager is a kind of admin", which stops being true the first time the
 * roles diverge (billing already does).
 */
abstract class StaffOrderController extends Controller
{
    use StoresOrders;

    public function __construct(protected readonly OrderRowPresenter $presenter) {}

    /** 'admin' | 'manager' — supplied by the concrete subclass. */
    abstract protected function role(): string;

    protected function view(string $name): string
    {
        return $this->role().'.orders.'.$name;
    }

    /**
     * Returns a full collection, never a paginator: oms-table.js does the
     * search, filtering, page-size and pagination client-side over the
     * rendered rows, and the stat tiles sum the rendered set.
     */
    public function index(): View
    {
        $orders = Order::query()
            ->with('customer:id,name,type')
            ->forIndex()
            ->get();

        return view($this->view('index'), [
            'orders' => $this->presenter->forStaff($orders),
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Create forms
    |--------------------------------------------------------------------------
    |
    | Three separate routes rather than one with a {type} parameter, mirroring
    | the three existing create pages. The type comes from the route and is
    | never read from the payload, so it cannot be spoofed.
    |
    */

    public function digitizing(): View
    {
        return $this->form(OrderType::Digitizing);
    }

    public function vector(): View
    {
        return $this->form(OrderType::Vector);
    }

    public function patch(): View
    {
        return $this->form(OrderType::Patch);
    }

    protected function form(OrderType $type): View
    {
        return view($this->view($type->value), [
            'type' => $type,
            'customers' => User::customers()->active()->orderBy('name')->get(['id', 'name', 'company']),
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Store — the customer comes from the validated payload, not the session
    |--------------------------------------------------------------------------
    */

    public function storeDigitizing(StoreDigitizingOrderRequest $request): RedirectResponse
    {
        return $this->redirectToIndex(
            $this->persistOrder($request, OrderType::Digitizing, (int) $request->validated('customer_id')),
        );
    }

    public function storeVector(StoreVectorOrderRequest $request): RedirectResponse
    {
        return $this->redirectToIndex(
            $this->persistOrder($request, OrderType::Vector, (int) $request->validated('customer_id')),
        );
    }

    public function storePatch(StorePatchOrderRequest $request): RedirectResponse
    {
        return $this->redirectToIndex(
            $this->persistOrder($request, OrderType::Patch, (int) $request->validated('customer_id')),
        );
    }

    protected function redirectToIndex(Order $order): RedirectResponse
    {
        return redirect()
            ->route($this->role().'.orders.index')
            ->with('status', "Order {$order->order_number} has been created.");
    }

    /*
    |--------------------------------------------------------------------------
    | Show / edit / update
    |--------------------------------------------------------------------------
    */

    public function show(Order $order): View
    {
        $this->authorize('view', $order);

        return view($this->view('show'), [
            'order' => $this->loadDetail($order),
            'role' => $this->role(),
            'canPrice' => request()->user()->can('price', $order),
        ]);
    }

    public function edit(Order $order): View
    {
        $this->authorize('update', $order);

        return view($this->view('edit'), [
            'order' => $order,
            'role' => $this->role(),
            'customers' => User::customers()->active()->orderBy('name')->get(['id', 'name', 'company']),
        ]);
    }

    public function update(UpdateOrderRequest $request, Order $order): RedirectResponse
    {
        $this->authorize('update', $order);

        $this->applyUpdate($request, $order);

        return redirect()
            ->route($this->role().'.orders.show', $order)
            ->with('status', "Order {$order->order_number} has been updated.");
    }

    public function updatePrice(UpdateOrderPriceRequest $request, Order $order): RedirectResponse
    {
        $this->authorize('price', $order);

        $order->forceFill([
            'price' => $request->validated('price'),
            'priced_by_id' => $request->user()->id,
            'priced_at' => now(),
        ])->save();

        OrderActivity::create([
            'order_id' => $order->id,
            'user_id' => $request->user()->id,
            'event' => OrderActivity::ORDER_PRICED,
            'description' => 'Order priced at $ '.number_format((float) $order->price, 2),
            'properties' => ['price' => $order->price],
        ]);

        app(OrderNotifier::class)->orderPriced($order->loadMissing('customer'));

        return back()->with('status', "Price saved for {$order->order_number}.");
    }

    /**
     * Soft delete — the row, its files, tasks and revisions all survive and
     * can be restored. Admin only, enforced by OrderPolicy@delete.
     *
     * The activity row is written before the delete so it is still there if
     * the order is ever restored.
     */
    public function destroy(Request $request, Order $order): RedirectResponse
    {
        $this->authorize('delete', $order);

        $number = $order->order_number;

        OrderActivity::create([
            'order_id' => $order->id,
            'user_id' => $request->user()->id,
            'event' => OrderActivity::ORDER_DELETED,
            'description' => "Order {$number} was deleted",
            'properties' => ['status' => $order->status->value],
        ]);

        $order->delete();

        return redirect()
            ->route($this->role().'.orders.index')
            ->with('status', "Order {$number} has been deleted.");
    }

    public function updateStatus(UpdateOrderStatusRequest $request, Order $order): RedirectResponse
    {
        $this->authorize('updateStatus', $order);

        $next = OrderStatus::from($request->validated('status'));
        $was = $order->status;

        // Cancelling has its own action so the reason, the actor and the
        // stand-down of open tasks are always captured. Allowing it here too
        // would give a second, silent path to the same end state.
        if ($next === OrderStatus::Cancelled) {
            return back()->withErrors([
                'status' => 'Use Cancel Order to call this order off, so the reason is recorded.',
            ]);
        }

        if (! $was->canTransitionTo($next)) {
            return back()->withErrors([
                'status' => "An order cannot move from {$was->label()} to {$next->label()}.",
            ]);
        }

        $order->status = $next;

        if ($next === OrderStatus::Delivered) {
            $order->delivery_status = DeliveryStatus::Delivered;
            $order->delivered_at = now();
        }

        $order->save();

        OrderActivity::create([
            'order_id' => $order->id,
            'user_id' => $request->user()->id,
            'event' => OrderActivity::ORDER_STATUS_CHANGED,
            'description' => "Order moved from {$was->label()} to {$next->label()}",
            'properties' => ['old' => $was->value, 'new' => $next->value],
        ]);

        $order->loadMissing('customer');

        if ($next === OrderStatus::Delivered) {
            app(OrderNotifier::class)->orderDelivered($order);
        } elseif ($next === OrderStatus::Completed) {
            app(OrderNotifier::class)->orderCompleted($order, $request->user());
        }

        return back()->with('status', "{$order->order_number} is now {$next->label()}.");
    }

    /*
    |--------------------------------------------------------------------------
    | Final approval
    |--------------------------------------------------------------------------
    |
    | An employee finishing the last task on an order parks it at
    | PendingApproval. Nothing has reached the customer yet. Here the approver
    | may swap a submitted file for their own version, then sign the order off
    | — which is what finally tells the customer their files are ready.
    |
    | Sending work back for rework is the ordinary status action: the
    | transitions out of PendingApproval include InProgress and Revision.
    |
    */

    public function approve(ApproveOrderRequest $request, Order $order): RedirectResponse
    {
        $this->authorize('approve', $order);

        app(OrderApprovalService::class)->approve(
            $order,
            $request->user(),
            $request->validated('note'),
        );

        return back()->with('status', "{$order->order_number} has been approved and the customer has been notified.");
    }

    /**
     * Replace one submitted file with the approver's own version. The
     * employee's original is deleted outright, bytes included — a file an
     * approver rejected must not stay downloadable. The file.replaced
     * activity row is what preserves the record of what was handed in.
     *
     * Works for both routes a deliverable arrives by: attached to the order,
     * or attached to the revision reply it was handed in with. The {file}
     * binding is therefore not scoped to the order and the ownership check is
     * made here — a file id from another order 404s before the policy runs.
     */
    public function replaceFile(ReplaceOrderFileRequest $request, Order $order, OrderFile $file): RedirectResponse
    {
        abort_unless($file->order()?->is($order) ?? false, 404);

        $this->authorize('replace', $file);

        $was = $file->original_name;

        $replacement = app(OrderApprovalService::class)->replaceDeliverable(
            $file,
            $request->file('file'),
            $request->user(),
        );

        return back()->with('status', "{$was} was replaced with {$replacement->original_name}.");
    }

    /**
     * Call the order off.
     *
     * Staff may cancel work that is already assigned, not only Pending
     * work — see OrderPolicy::cancel(). The service stands down any open
     * task and writes the audit entry.
     */
    public function cancel(CancelOrderRequest $request, Order $order): RedirectResponse
    {
        $this->authorize('cancel', $order);

        app(OrderCancellationService::class)->cancel(
            $order,
            $request->user(),
            $request->validated('reason'),
        );

        app(OrderNotifier::class)->orderCancelled($order->loadMissing('customer'), $request->user());

        return redirect()
            ->route($this->role().'.orders.index')
            ->with('status', "Order {$order->order_number} has been cancelled.");
    }
}
