<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Concerns\StoresOrders;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Support\OrderRowPresenter;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    use StoresOrders;

    public function __construct(private readonly OrderRowPresenter $presenter) {}

    /**
     * Only orders this employee holds, or has held, a task on. An employee
     * never sees the unassigned queue, which is why their status vocabulary
     * has no `pending`.
     */
    public function index(Request $request): View
    {
        $orders = Order::query()
            ->with('customer:id,name,type')
            ->assignedTo($request->user())
            ->forIndex()
            ->get();

        return view('employee.orders.index', [
            'orders' => $this->presenter->forEmployee($orders),
        ]);
    }

    /** Read-only: an employee can see the brief but never edit or price it. */
    public function show(Order $order): View
    {
        $this->authorize('view', $order);

        return view('employee.orders.show', [
            'order' => $this->loadDetail($order),
            'role' => 'employee',
            'canPrice' => false,
        ]);
    }
}
