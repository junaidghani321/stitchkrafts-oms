<?php

namespace App\Http\Controllers\Staff;

use App\Enums\TaskSlaStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Tasks\AssignTaskRequest;
use App\Http\Requests\Tasks\ExtendTaskRequest;
use App\Models\Order;
use App\Models\OrderTask;
use App\Models\User;
use App\Services\TaskAssignmentService;
use App\Support\OrderRowPresenter;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

/**
 * admin/tasks/index and manager/tasks/index are byte-identical, so both roles
 * share this base.
 */
abstract class StaffTaskController extends Controller
{
    public function __construct(
        protected readonly OrderRowPresenter $presenter,
        protected readonly TaskAssignmentService $tasks,
    ) {}

    abstract protected function role(): string;

    public function index(): View
    {
        // Full collection, not a paginator: the filters, search, page-size and
        // the four stat tiles are all computed client-side over the rendered
        // rows by oms-table.js.
        $tasks = OrderTask::query()
            ->forLiveOrder()
            ->with(['order:id,order_number,design_name', 'employee:id,name,type'])
            ->latest('assigned_at')
            ->limit(config('orders.index.max_rows', 1000))
            ->get();

        return view($this->role().'.tasks.index', [
            'tasks' => $this->presenter->tasksFor($tasks),
            'employees' => User::employees()->active()->orderBy('name')->get(['id', 'name']),
            'statusMeta' => TaskSlaStatus::metaMap(),
            'orders' => $this->presenter->selectOptions(
                Order::notCancelled()->latest('created_at')->get(['id', 'order_number', 'design_name', 'type']),
            ),
            'role' => $this->role(),
        ]);
    }

    public function store(AssignTaskRequest $request): RedirectResponse
    {
        $order = Order::findOrFail($request->validated('order_id'));

        $this->authorize('assignTask', $order);

        $task = $this->tasks->assign($order, $request->validated(), $request->user());

        return back()->with('status', "Task assigned to {$task->employee->name} on {$order->order_number}.");
    }

    public function extend(ExtendTaskRequest $request, OrderTask $task): RedirectResponse
    {
        $this->authorize('extend', $task);

        $this->tasks->extend($task, (int) $request->validated('additional_hours'), $request->user());

        return back()->with('status', "Deadline extended to {$task->duration_hours}h.");
    }
}
