<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\Revisions\StoreRevisionRequest;
use App\Models\Order;
use App\Models\OrderMessage;
use App\Services\RevisionService;
use Illuminate\Http\RedirectResponse;

class RevisionController extends Controller
{
    public function __construct(private readonly RevisionService $revisions) {}

    public function store(StoreRevisionRequest $request): RedirectResponse
    {
        $order = Order::findOrFail($request->validated('order_id'));

        // Enforces both ownership and "the order must be delivered".
        $this->authorize('requestRevision', $order);

        $revision = $this->revisions->request(
            $order,
            $request->validated() + ['source' => OrderMessage::SOURCE_CUSTOMER],
            $request->file('files', []),
            $request->user(),
        );

        // back(), not a redirect to a list: the revision is listed on the
        // order's own page, which is where the customer just was.
        return back()->with('status', "Revision {$revision->revision_number} has been requested.");
    }
}
