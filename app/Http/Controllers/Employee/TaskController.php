<?php

namespace App\Http\Controllers\Employee;

use App\Enums\TaskSlaStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Tasks\CompleteTaskRequest;
use App\Models\OrderTask;
use App\Services\TaskAssignmentService;
use App\Support\OrderRowPresenter;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class TaskController extends Controller
{
    public function __construct(
        private readonly OrderRowPresenter $presenter,
        private readonly TaskAssignmentService $tasks,
    ) {}

    public function index(Request $request): View
    {
        $tasks = OrderTask::query()
            ->forLiveOrder()
            ->forEmployee($request->user())
            ->with(['order:id,order_number,design_name', 'employee:id,name,type'])
            ->latest('assigned_at')
            ->get();

        return view('employee.tasks.index', [
            'tasks' => $this->presenter->tasksFor($tasks),
            'statusMeta' => TaskSlaStatus::metaMap(),
        ]);
    }

    public function start(Request $request, OrderTask $task): RedirectResponse
    {
        $this->authorize('start', $task);

        $this->tasks->start($task, $request->user());

        return back()->with('status', 'Task started.');
    }

    public function complete(CompleteTaskRequest $request, OrderTask $task): RedirectResponse
    {
        $this->authorize('complete', $task);

        $this->tasks->complete(
            $task,
            $request->validated('note'),
            $request->file('files', []),
            $request->user(),
        );

        $sla = $task->refresh()->sla();

        return back()->with('status', $sla->isBreached()
            ? "Task completed {$sla->formattedDelta()} over the allowed time."
            : "Task completed {$sla->formattedDelta()} inside the allowed time.");
    }
}
