<?php

namespace App\Http\Controllers\Admin;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Users\StoreUserRequest;
use App\Http\Requests\Users\UpdateUserRequest;
use App\Models\Order;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class UserController extends Controller
{
    /** Full collection — oms-table.js pages client-side. */
    public function index(): View
    {
        $this->authorize('viewAny', User::class);

        // One grouped query for the order counts rather than one per row.
        $orderCounts = Order::query()
            ->select('customer_id', DB::raw('count(*) c'))
            ->groupBy('customer_id')
            ->pluck('c', 'customer_id');

        $users = User::query()
            ->orderBy('name')
            ->limit(config('orders.index.max_rows', 1000))
            ->get()
            ->map(fn (User $user) => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'type' => $user->type,
                'company' => $user->company ?: '—',
                'status' => $user->status,
                'orders' => (int) ($orderCounts[$user->id] ?? 0),
                'joined' => $user->created_at->format('d-m-Y'),
                // Drives whether the row offers a delete button at all.
                'deletable' => request()->user()->can('delete', $user),
            ]);

        return view('admin.users.index', ['users' => $users]);
    }

    public function create(): View
    {
        $this->authorize('create', User::class);

        return view('admin.users.create');
    }

    public function store(StoreUserRequest $request): RedirectResponse
    {
        $this->authorize('create', User::class);

        $data = $request->validated();

        // `type` and `password` are excluded from the model's fillable list —
        // one is a privilege column, the other must never arrive by mass
        // assignment — so both are set explicitly here.
        $user = new User(collect($data)->except(['type', 'password', 'password_confirmation'])->all());
        $user->type = $data['type'];
        $user->password = $data['password'];   // hashed by the model cast
        $user->save();

        return redirect()
            ->route('admin.users.index')
            ->with('status', "{$user->name} has been created.");
    }

    public function show(User $user): View
    {
        $this->authorize('view', $user);

        return view('admin.users.show', [
            'user' => $this->profile($user),
            'stats' => $this->stats($user),
            'orders' => $this->recentOrders($user),
        ]);
    }

    public function edit(User $user): View
    {
        $this->authorize('update', $user);

        return view('admin.users.edit', [
            'user' => $this->profile($user),
            'canDelete' => request()->user()->can('delete', $user),
        ]);
    }

    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        $this->authorize('update', $user);

        $data = $request->validated();

        // Guard the two ways an admin could lock everyone out.
        if ($data['type'] !== $user->type && ! $request->user()->can('changeRole', [$user, $data['type']])) {
            return back()
                ->withInput()
                ->withErrors(['type' => 'That role change would leave the system without an administrator.']);
        }

        $user->fill(collect($data)->except(['type', 'password', 'password_confirmation'])->all());
        $user->type = $data['type'];

        // Blank password means "leave it alone".
        if (! empty($data['password'])) {
            $user->password = $data['password'];
        }

        $user->save();

        return redirect()
            ->route('admin.users.show', $user)
            ->with('status', "{$user->name} has been updated.");
    }

    /**
     * Soft delete. Orders, tasks and revisions keep pointing at the row, so
     * history stays intact and the foreign keys hold.
     */
    public function destroy(User $user): RedirectResponse
    {
        $this->authorize('delete', $user);

        $name = $user->name;
        $user->delete();

        return redirect()
            ->route('admin.users.index')
            ->with('status', "{$name} has been deleted. Their orders and history remain.");
    }

    /*
    |--------------------------------------------------------------------------
    | Detail helpers
    |--------------------------------------------------------------------------
    */

    private function profile(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'type' => $user->type,
            'status' => $user->status,
            'phone' => $user->phone,
            'company' => $user->company,
            'designation' => $user->designation,
            'department' => $user->department,
            'shift' => $user->shift,
            'notes' => $user->notes,
            'joined' => $user->created_at->format('d-m-Y'),
        ];
    }

    /**
     * The four tiles mean different things per role: a customer's orders are
     * ones they placed, an employee's are ones they worked.
     */
    private function stats(User $user): array
    {
        $orders = $user->isEmployee()
            ? Order::assignedTo($user)
            : Order::forCustomer($user);

        $inProgress = (clone $orders)->whereIn('status', [
            OrderStatus::Assigned->value, OrderStatus::InProgress->value,
            OrderStatus::QualityCheck->value, OrderStatus::Revision->value,
            // Submitted but not signed off — still open work.
            OrderStatus::PendingApproval->value,
        ])->count();

        $paid = (float) (clone $orders)->where('payment_status', PaymentStatus::Paid->value)->sum('price');

        $outstanding = (float) (clone $orders)
            ->whereIn('status', [OrderStatus::Completed->value, OrderStatus::Delivered->value])
            ->where('payment_status', '!=', PaymentStatus::Paid->value)
            ->sum('price');

        return [
            ['label' => $user->isEmployee() ? 'Orders Worked' : 'Total Orders',
                'value' => number_format((clone $orders)->count()), 'icon' => 'fa-cart-flatbed', 'tone' => ''],

            ['label' => 'In Progress', 'value' => number_format($inProgress),
                'icon' => 'fa-spinner', 'tone' => 'oms-stat__icon--accent'],

            ['label' => 'Total Paid', 'value' => '$ '.number_format($paid, 2),
                'icon' => 'fa-circle-check', 'tone' => 'oms-stat__icon--good'],

            ['label' => 'Outstanding', 'value' => '$ '.number_format($outstanding, 2),
                'icon' => 'fa-file-invoice-dollar', 'tone' => $outstanding > 0 ? 'oms-stat__icon--bad' : ''],
        ];
    }

    /** keys: no, design, date, status, price */
    private function recentOrders(User $user, int $limit = 5): Collection
    {
        $orders = $user->isEmployee()
            ? Order::assignedTo($user)
            : Order::forCustomer($user);

        return $orders->latest('created_at')->limit($limit)->get()
            ->map(fn (Order $order) => [
                'id' => $order->id,
                'no' => $order->order_number,
                'design' => $order->design_name,
                'date' => $order->created_at->format('d-m-Y'),
                'status' => $order->status->badge(),
                'price' => $order->display_price,
            ]);
    }
}
