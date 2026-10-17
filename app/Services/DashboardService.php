<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Enums\OrderType;
use App\Enums\QuoteStatus;
use App\Enums\RevisionStatus;
use App\Models\Invoice;
use App\Models\Order;
use App\Models\OrderMessage;
use App\Models\OrderTask;
use App\Models\Quote;
use App\Models\User;
use App\Support\Money;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Every figure on the four dashboards.
 *
 * Every task and revision aggregate is scoped with forLiveOrder(). Orders are
 * soft-deleted and that does not cascade, so without it a tile would count
 * work on deleted orders and disagree with the very list it links to.
 *
 * The SLA counts here are done in SQL rather than through TaskSlaCalculator:
 * the calculator answers "what is the state of THIS task" and is right for a
 * row being rendered, but instantiating it per row just to count overdue ones
 * would load the whole task table on every dashboard hit. `deadline_at` is
 * stored precisely so these can be aggregate queries.
 */
class DashboardService
{
    /*
    |--------------------------------------------------------------------------
    | Shared counters
    |--------------------------------------------------------------------------
    */

    /** Open tasks whose deadline has already passed. */
    private function overdueTaskCount(?int $employeeId = null): int
    {
        return OrderTask::query()
            ->forLiveOrder()
            ->open()
            ->when($employeeId, fn ($q) => $q->where('employee_id', $employeeId))
            ->where('deadline_at', '<', now())
            ->count();
    }

    /**
     * Percentage of finished tasks delivered inside their allowance.
     * Mirrors TaskSlaCalculator's on_time rule: completed_at <= deadline_at.
     */
    private function onTimeRate(?int $employeeId = null, ?int $days = null): int
    {
        $base = OrderTask::query()
            ->forLiveOrder()
            ->whereNotNull('completed_at')
            ->when($employeeId, fn ($q) => $q->where('employee_id', $employeeId))
            ->when($days, fn ($q) => $q->where('completed_at', '>=', now()->subDays($days)));

        $finished = (clone $base)->count();

        if ($finished === 0) {
            return 0;
        }

        $onTime = (clone $base)->whereColumn('completed_at', '<=', 'deadline_at')->count();

        return (int) round($onTime / $finished * 100);
    }

    /**
     * Money owed — read from invoices, not orders, so the dashboard tile and
     * the billing page can never disagree. You are not owed for work you have
     * not billed yet.
     */
    private function outstanding(?int $customerId = null): array
    {
        $query = Invoice::query()
            ->unpaid()
            ->when($customerId, fn ($q) => $q->where('customer_id', $customerId));

        $cents = (int) $query->sum('total_cents') - (int) $query->sum('paid_cents');

        return [
            'total' => Money::toDecimal(max(0, $cents)),
            'count' => $query->count(),
        ];
    }

    private function tile(string $label, $value, string $icon, string $tone, string $route, string $note): array
    {
        return compact('label', 'value', 'icon', 'tone', 'route', 'note');
    }

    private function plural(int $n, string $singular, ?string $plural = null): string
    {
        return $n.' '.($n === 1 ? $singular : ($plural ?? $singular.'s'));
    }

    /*
    |--------------------------------------------------------------------------
    | Admin
    |--------------------------------------------------------------------------
    */

    public function forAdmin(User $user): array
    {
        $orderCounts = $this->orderStatusCounts();
        $inProgress = $orderCounts['open'];

        $quotesAwaiting = Quote::where('status', QuoteStatus::Pending->value)->count();
        $activeTasks = OrderTask::forLiveOrder()->open()->count();
        $overdue = $this->overdueTaskCount();
        $openRevisions = OrderMessage::revisions()->forLiveOrder()->open()->count();
        $urgent = OrderMessage::revisions()->forLiveOrder()->open()->where('priority', 'urgent')->count();
        $owed = $this->outstanding();
        $newCustomers = User::customers()->where('created_at', '>=', now()->startOfMonth())->count();

        return [
            'modules' => [
                $this->tile('Total Orders', number_format($orderCounts['total']), 'fa-cart-flatbed', '',
                    route('admin.orders.index'), $inProgress.' in progress'),

                $this->tile('Total Quotes', number_format(Quote::count()), 'fa-file-invoice', 'oms-stat__icon--accent',
                    route('admin.quotes.index'), $quotesAwaiting.' awaiting pricing'),

                $this->tile('Total Customers', number_format(User::customers()->count()), 'fa-users', '',
                    route('admin.users.index'), $newCustomers.' added this month'),

                $this->tile('Total Employees', number_format(User::employees()->count()), 'fa-user-gear', '',
                    route('admin.employees.index'), User::employees()->active()->count().' active'),

                $this->tile('Active Tasks', $activeTasks, 'fa-list-check', 'oms-stat__icon--accent',
                    route('admin.tasks.index'), $overdue.' overdue'),

                $this->tile('Open Revisions', $openRevisions, 'fa-rotate-left', $openRevisions > 0 ? 'oms-stat__icon--bad' : '',
                    route('admin.orders.index'), $urgent.' urgent'),

                $this->tile('Payments Outstanding', '$ '.number_format($owed['total'], 2), 'fa-file-invoice-dollar',
                    $owed['total'] > 0 ? 'oms-stat__icon--bad' : '',
                    route('admin.billing'), $this->plural($owed['count'], 'unpaid invoice')),
            ],

            'recent' => $this->recentOrders(),
            'tasks' => $this->runningTasks(),
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Manager — same floor view, no money
    |--------------------------------------------------------------------------
    */

    public function forManager(User $user): array
    {
        $orderCounts = $this->orderStatusCounts();
        $activeTasks = OrderTask::forLiveOrder()->open()->count();
        $overdue = $this->overdueTaskCount();
        $openRevisions = OrderMessage::revisions()->forLiveOrder()->open()->count();
        $urgent = OrderMessage::revisions()->forLiveOrder()->open()->where('priority', 'urgent')->count();

        $completedToday = Order::whereDate('completed_at', today())->count();
        $deliveredToday = Order::whereDate('delivered_at', today())->count();

        return [
            'modules' => [
                $this->tile('Total Orders', number_format($orderCounts['total']), 'fa-cart-flatbed', '',
                    route('manager.orders.index'), $orderCounts['open'].' in progress'),

                $this->tile('Total Quotes', number_format(Quote::count()), 'fa-file-invoice', 'oms-stat__icon--accent',
                    route('manager.quotes.index'), Quote::where('status', QuoteStatus::Pending->value)->count().' awaiting pricing'),

                $this->tile('Employees', number_format(User::employees()->count()), 'fa-user-gear', '',
                    route('manager.employees.index'), User::employees()->active()->count().' active'),

                $this->tile('Active Tasks', $activeTasks, 'fa-list-check', 'oms-stat__icon--accent',
                    route('manager.tasks.index'), $overdue.' overdue'),

                $this->tile('Open Revisions', $openRevisions, 'fa-rotate-left', $openRevisions > 0 ? 'oms-stat__icon--bad' : '',
                    route('manager.orders.index'), $urgent.' urgent'),

                $this->tile('Completed Today', $completedToday, 'fa-circle-check', 'oms-stat__icon--good',
                    route('manager.orders.index'), $deliveredToday.' delivered'),

                $this->tile('Team On-Time', $this->onTimeRate(days: 30).'%', 'fa-stopwatch', 'oms-stat__icon--good',
                    route('manager.employees.index'), 'Across the last 30 days'),
            ],

            'orderStats' => $this->ordersByType($orderCounts['total']),
            'team' => $this->teamPerformance(),
            'tasks' => $this->runningTasks(),
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Employee — only their own work
    |--------------------------------------------------------------------------
    */

    public function forEmployee(User $user): array
    {
        $id = $user->id;

        $completed = OrderTask::forLiveOrder()->forEmployee($id)->whereNotNull('completed_at')->count();
        $onTimeAll = OrderTask::forLiveOrder()->forEmployee($id)->whereNotNull('completed_at')
            ->whereColumn('completed_at', '<=', 'deadline_at')->count();

        $activeTasks = OrderTask::forLiveOrder()->forEmployee($id)->open()->count();
        $dueSoon = OrderTask::forLiveOrder()->forEmployee($id)->open()
            ->whereBetween('deadline_at', [now(), now()->addHour()])->count();

        $myOrders = Order::assignedTo($id);
        $openOrders = (clone $myOrders)->whereNotIn('status', [
            OrderStatus::Completed->value, OrderStatus::Delivered->value,
        ])->count();

        $openRevisions = OrderMessage::revisions()->forLiveOrder()->forEmployee($id)->open()->count();
        $urgent = OrderMessage::revisions()->forLiveOrder()->forEmployee($id)->open()->where('priority', 'urgent')->count();

        return [
            'totals' => ['completed' => $completed, 'on_time' => $onTimeAll, 'late' => $completed - $onTimeAll],
            'score' => $this->onTimeRate($id),

            'modules' => [
                $this->tile('My Active Tasks', $activeTasks, 'fa-list-check', 'oms-stat__icon--accent',
                    route('employee.tasks.index'), $dueSoon.' due within the hour'),

                $this->tile('Completed Tasks', $completed, 'fa-circle-check', 'oms-stat__icon--good',
                    route('employee.performance'), $onTimeAll.' delivered on time'),

                $this->tile('On-Time Rate', $this->onTimeRate($id).'%', 'fa-stopwatch', 'oms-stat__icon--good',
                    route('employee.performance'), 'Across all completed tasks'),

                $this->tile('My Orders', (clone $myOrders)->count(), 'fa-cart-flatbed', '',
                    route('employee.orders.index'), $openOrders.' still in progress'),

                $this->tile('Open Revisions', $openRevisions, 'fa-rotate-left', $openRevisions > 0 ? 'oms-stat__icon--bad' : '',
                    route('employee.tasks.index'), $urgent.' marked urgent'),
            ],

            'tasks' => $this->runningTasks($id, withDesign: true),
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Customer
    |--------------------------------------------------------------------------
    */

    public function forCustomer(User $user): array
    {
        $id = $user->id;

        $mine = Order::forCustomer($id);
        $total = (clone $mine)->count();
        $thisMonth = (clone $mine)->where('created_at', '>=', now()->startOfMonth())->count();
        $inProgress = (clone $mine)->whereIn('status', [
            OrderStatus::Assigned->value, OrderStatus::InProgress->value,
            OrderStatus::QualityCheck->value, OrderStatus::Revision->value,
            // Still in production as far as the customer is concerned: the
            // work is in but unapproved, so it is neither done nor theirs yet.
            OrderStatus::PendingApproval->value,
        ])->count();
        $dueToday = (clone $mine)->whereDate('due_at', today())->count();
        $completed = (clone $mine)->whereIn('status', [OrderStatus::Completed->value, OrderStatus::Delivered->value])->count();

        $quotes = Quote::forCustomer($id);
        $awaitingPricing = (clone $quotes)->where('status', QuoteStatus::Pending->value)->count();

        $revisions = OrderMessage::revisions()->forLiveOrder()->forCustomer($id);
        $revsOpen = (clone $revisions)->where('status', RevisionStatus::InProgress->value)->count();

        $owed = $this->outstanding($id);
        $spent = Money::toDecimal((int) Invoice::forCustomer($id)->sum('paid_cents'));

        return [
            'modules' => [
                $this->tile('My Orders', $total, 'fa-cart-flatbed', '',
                    route('user.orders.index'), $thisMonth.' placed this month'),

                $this->tile('In Progress', $inProgress, 'fa-spinner', 'oms-stat__icon--accent',
                    route('user.orders.index'), $dueToday.' due today'),

                $this->tile('Completed Orders', $completed, 'fa-circle-check', 'oms-stat__icon--good',
                    route('user.orders.index'), 'Files ready to download'),

                $this->tile('My Quotes', (clone $quotes)->count(), 'fa-file-invoice', '',
                    route('user.quotes.index'), $awaitingPricing.' awaiting pricing'),

                $this->tile('Revision Requests', (clone $revisions)->count(), 'fa-rotate-left', 'oms-stat__icon--accent',
                    route('user.orders.index'), $revsOpen.' in progress'),

                $this->tile('Outstanding Balance', '$ '.number_format($owed['total'], 2), 'fa-file-invoice-dollar',
                    $owed['total'] > 0 ? 'oms-stat__icon--bad' : '',
                    route('user.billing'), $this->plural($owed['count'], 'unpaid invoice')),

                $this->tile('Total Spent', '$ '.number_format($spent, 2), 'fa-wallet', 'oms-stat__icon--good',
                    route('user.billing'), 'Across all invoices'),
            ],

            'active' => $this->customerActiveOrders($id),
            'recent' => $this->customerRecentOrders($id),
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Building blocks
    |--------------------------------------------------------------------------
    */

    /** One query for the whole status breakdown, not one per tile. */
    private function orderStatusCounts(): array
    {
        $rows = Order::notCancelled()
            ->select('status', DB::raw('count(*) as c'))
            ->groupBy('status')
            ->pluck('c', 'status');

        $done = ($rows[OrderStatus::Completed->value] ?? 0) + ($rows[OrderStatus::Delivered->value] ?? 0);
        $all = $rows->sum();

        return [
            'total' => $all,
            'done' => $done,
            'open' => $all - $done - ($rows[OrderStatus::Pending->value] ?? 0),
        ];
    }

    private function ordersByType(int $total): array
    {
        $rows = Order::notCancelled()
            ->select('type', DB::raw('count(*) as c'))
            ->groupBy('type')
            ->pluck('c', 'type');

        $bars = ['digitizing' => '', 'vector' => 'warn', 'patch' => 'good'];

        return collect(OrderType::cases())
            ->map(fn (OrderType $type) => [
                'label' => $type->label(),
                'value' => (int) ($rows[$type->value] ?? 0),
                // max(1) keeps the percentage maths safe on an empty system.
                'total' => max(1, $total),
                'bar' => $bars[$type->value] ?? '',
            ])
            ->all();
    }

    /** keys: no, customer, design, date, status */
    private function recentOrders(int $limit = 5): Collection
    {
        return Order::notCancelled()
            ->with('customer:id,name,type')
            ->latest('created_at')
            ->limit($limit)
            ->get()
            ->map(fn (Order $order) => [
                'no' => $order->order_number,
                'customer' => $order->customer?->name ?? '—',
                'design' => $order->design_name,
                'date' => $order->created_at->format('d-m-Y'),
                'status' => $order->status->badge(),
            ]);
    }

    /** keys: order, employee, assigned_ago, duration (+ design/type/priority) */
    private function runningTasks(?int $employeeId = null, bool $withDesign = false, int $limit = 4): Collection
    {
        return OrderTask::query()
            ->forLiveOrder()
            ->open()
            ->when($employeeId, fn ($q) => $q->where('employee_id', $employeeId))
            ->with(['order:id,order_number,design_name', 'employee:id,name,type'])
            ->orderBy('deadline_at')
            ->limit($limit)
            ->get()
            ->map(function (OrderTask $task) use ($withDesign) {
                $row = [
                    'order' => $task->order?->order_number ?? '—',
                    'employee' => $task->employee?->name ?? '—',
                    'assigned_ago' => (int) $task->assigned_at->diffInMinutes(now()),
                    'duration' => $task->duration_hours,
                ];

                return $withDesign
                    ? $row + [
                        'design' => $task->order?->design_name ?? '—',
                        'type' => $task->task_type->label(),
                        'priority' => $task->priority->value,
                    ]
                    : $row;
            });
    }

    /**
     * Customer "in production" cards.
     *
     * `percent` is how far through the pipeline the order is; `stage` is that
     * in words. Both are derived from status rather than invented.
     */
    private function customerActiveOrders(int $customerId, int $limit = 3): Collection
    {
        $progress = [
            OrderStatus::Pending->value => [10, 'Awaiting a production slot'],
            OrderStatus::Assigned->value => [35, 'Assigned to a digitizer'],
            OrderStatus::InProgress->value => [70, 'In progress'],
            OrderStatus::QualityCheck->value => [85, 'In quality check'],
            OrderStatus::Revision->value => [60, 'Revision in progress'],
            // Worded for the customer: they do not need to know whose desk it
            // is on, only that it is through the work and being checked.
            OrderStatus::PendingApproval->value => [90, 'Final checks'],
        ];

        return Order::forCustomer($customerId)
            ->whereIn('status', array_keys($progress))
            ->orderBy('due_at')
            ->limit($limit)
            ->get()
            ->map(function (Order $order) use ($progress) {
                [$percent, $stage] = $progress[$order->status->value] ?? [0, $order->status->label()];

                return [
                    'no' => $order->order_number,
                    'design' => $order->design_name,
                    'type' => $order->type->label(),
                    'due' => $order->due_at?->format('d-m-Y H:i') ?? '—',
                    'percent' => $percent,
                    'stage' => $stage,
                ];
            });
    }

    /** keys: no, design, type, date, status, price */
    private function customerRecentOrders(int $customerId, int $limit = 5): Collection
    {
        return Order::forCustomer($customerId)
            ->latest('created_at')
            ->limit($limit)
            ->get()
            ->map(fn (Order $order) => [
                'no' => $order->order_number,
                'design' => $order->design_name,
                'type' => $order->type->label(),
                'date' => $order->created_at->format('d-m-Y'),
                'status' => $order->status->badge(),
                'price' => $order->display_price,
            ]);
    }

    /** keys: id, name, active, completed, on_time — the manager team table. */
    private function teamPerformance(): Collection
    {
        return User::employees()
            ->orderBy('name')
            ->get(['id', 'name', 'type'])
            ->map(function (User $employee) {
                $base = OrderTask::forLiveOrder()->forEmployee($employee->id);

                return [
                    'id' => $employee->id,
                    'name' => $employee->name,
                    'active' => (clone $base)->open()->count(),
                    'completed' => (clone $base)->whereNotNull('completed_at')->count(),
                    'on_time' => (clone $base)->whereNotNull('completed_at')
                        ->whereColumn('completed_at', '<=', 'deadline_at')->count(),
                ];
            });
    }
}
