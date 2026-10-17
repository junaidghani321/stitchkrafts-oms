<?php

namespace App\Http\Controllers\User;

use App\Enums\OrderType;
use App\Http\Controllers\Concerns\StoresOrders;
use App\Http\Controllers\Controller;
use App\Http\Requests\Orders\CancelOrderRequest;
use App\Http\Requests\Orders\UpdateOrderRequest;
use App\Http\Requests\Orders\User\StoreDigitizingOrderRequest;
use App\Http\Requests\Orders\User\StorePatchOrderRequest;
use App\Http\Requests\Orders\User\StoreVectorOrderRequest;
use App\Models\Order;
use App\Services\OrderCancellationService;
use App\Services\OrderNotifier;
use App\Support\OrderRowPresenter;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    use StoresOrders;

    public function __construct(private readonly OrderRowPresenter $presenter) {}

    /** Scoped to the signed-in customer — never a query parameter. */
    public function index(Request $request): View
    {
        $orders = Order::query()
            ->forCustomer($request->user())
            ->forIndex()
            ->get();

        return view('user.orders.index', [
            'orders' => $this->presenter->forCustomer($orders),
        ]);
    }

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

    private function form(OrderType $type): View
    {
        return view('user.orders.'.$type->value, ['type' => $type]);
    }

    /*
    |--------------------------------------------------------------------------
    | Store
    |--------------------------------------------------------------------------
    |
    | One action per type, each with its own type-hinted request. The order
    | type comes from the route, so it cannot be spoofed via the payload.
    |
    */

    public function storeDigitizing(StoreDigitizingOrderRequest $request): RedirectResponse
    {
        return $this->redirectToOrder($this->persistOrder($request, OrderType::Digitizing));
    }

    public function storeVector(StoreVectorOrderRequest $request): RedirectResponse
    {
        return $this->redirectToOrder($this->persistOrder($request, OrderType::Vector));
    }

    public function storePatch(StorePatchOrderRequest $request): RedirectResponse
    {
        return $this->redirectToOrder($this->persistOrder($request, OrderType::Patch));
    }

    private function redirectToOrder(Order $order): RedirectResponse
    {
        return redirect()
            ->route('user.orders.index')
            ->with('status', "Order {$order->order_number} has been placed.");
    }

    /*
    |--------------------------------------------------------------------------
    | Show / edit / update
    |--------------------------------------------------------------------------
    |
    | The policy does the record-level work: view is limited to the owner, and
    | update additionally requires the order to still be Pending — once work
    | has started, changes go through a revision instead.
    |
    */

    public function show(Order $order): View
    {
        $this->authorize('view', $order);

        return view('user.orders.show', [
            'order' => $this->loadDetail($order),
            'role' => 'user',
            'canPrice' => false,
        ]);
    }

    public function edit(Order $order): View
    {
        $this->authorize('update', $order);

        return view('user.orders.edit', [
            'order' => $order,
            'role' => 'user',
        ]);
    }

    public function update(UpdateOrderRequest $request, Order $order): RedirectResponse
    {
        $this->authorize('update', $order);

        $this->applyUpdate($request, $order);

        return redirect()
            ->route('user.orders.show', $order)
            ->with('status', "Order {$order->order_number} has been updated.");
    }

    /**
     * Call the order off.
     *
     * The policy allows this only while the order is still Pending; the
     * service stands down any open task and writes the audit entry.
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
            ->route('user.orders.index')
            ->with('status', "Order {$order->order_number} has been cancelled.");
    }
}
