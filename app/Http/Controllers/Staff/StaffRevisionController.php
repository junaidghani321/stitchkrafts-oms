<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Http\Requests\Revisions\AssignRevisionRequest;
use App\Http\Requests\Revisions\RejectRevisionRequest;
use App\Http\Requests\Revisions\StoreRevisionRequest;
use App\Models\Order;
use App\Models\OrderMessage;
use App\Services\RevisionService;
use Illuminate\Http\RedirectResponse;

/**
 * Raising, assigning and rejecting a revision. There is no index: a revision
 * is worked from the order page's Revisions card, and the employee closes it
 * out from My Tasks.
 */
abstract class StaffRevisionController extends Controller
{
    public function __construct(
        protected readonly RevisionService $revisions,
    ) {}

    abstract protected function role(): string;

    /** Staff can raise a revision on the customer's behalf. */
    public function store(StoreRevisionRequest $request): RedirectResponse
    {
        $order = Order::findOrFail($request->validated('order_id'));

        $this->authorize('requestRevision', $order);

        $revision = $this->revisions->request(
            $order,
            $request->validated() + ['source' => OrderMessage::SOURCE_QUALITY_CHECK],
            $request->file('files', []),
            $request->user(),
        );

        return back()->with('status', "Revision {$revision->revision_number} was raised on {$order->order_number}.");
    }

    public function assign(AssignRevisionRequest $request, OrderMessage $revision): RedirectResponse
    {
        $this->authorize('assign', $revision);

        $this->revisions->assign($revision, $request->validated(), $request->user());

        return back()->with('status', "Revision {$revision->revision_number} has been assigned.");
    }

    public function reject(RejectRevisionRequest $request, OrderMessage $revision): RedirectResponse
    {
        $this->authorize('reject', $revision);

        $this->revisions->reject($revision, $request->validated('rejection_reason'), $request->user());

        return back()->with('status', "Revision {$revision->revision_number} was rejected.");
    }
}
