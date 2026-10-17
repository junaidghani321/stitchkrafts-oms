<?php

namespace App\Services;

use App\Models\OrderActivity;
use App\Models\OrderTask;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Everything the employees list, the employee profile and the employee's own
 * performance page display.
 *
 * All of it comes out of `order_tasks`, which has recorded every assignment
 * and every completion since the order module went in — "on time" is simply
 * completed_at <= deadline_at, the same rule TaskSlaCalculator applies to a
 * single row.
 *
 * Like the dashboard, every query is scoped with forLiveOrder(): work on a
 * deleted order should not count towards anybody's score.
 */
class EmployeePerformanceService
{
    /*
    |--------------------------------------------------------------------------
    | Listing
    |--------------------------------------------------------------------------
    */

    /**
     * The team table.
     * keys: id, name, designation, email, active, completed, on_time, late, status
     */
    public function team(): Collection
    {
        $employees = User::employees()->orderBy('name')->get();

        $stats = $this->statsFor($employees->pluck('id')->all());

        return $employees->map(function (User $employee) use ($stats) {
            $row = $stats[$employee->id] ?? ['active' => 0, 'completed' => 0, 'on_time' => 0];

            return [
                'id' => $employee->id,
                'name' => $employee->name,
                'designation' => $employee->designation ?: '—',
                'email' => $employee->email,
                'active' => $row['active'],
                'completed' => $row['completed'],
                'on_time' => $row['on_time'],
                'late' => $row['completed'] - $row['on_time'],
                'status' => $employee->status,
            ];
        });
    }

    /**
     * Active / completed / on-time for many employees in three queries rather
     * than three per employee.
     *
     * @param  array<int, int>  $ids
     */
    private function statsFor(array $ids): array
    {
        if ($ids === []) {
            return [];
        }

        $base = fn () => OrderTask::query()->forLiveOrder()->whereIn('employee_id', $ids);

        $active = (clone $base())->open()
            ->select('employee_id', DB::raw('count(*) c'))->groupBy('employee_id')->pluck('c', 'employee_id');

        $completed = (clone $base())->whereNotNull('completed_at')
            ->select('employee_id', DB::raw('count(*) c'))->groupBy('employee_id')->pluck('c', 'employee_id');

        $onTime = (clone $base())->whereNotNull('completed_at')->whereColumn('completed_at', '<=', 'deadline_at')
            ->select('employee_id', DB::raw('count(*) c'))->groupBy('employee_id')->pluck('c', 'employee_id');

        $out = [];

        foreach ($ids as $id) {
            $out[$id] = [
                'active' => (int) ($active[$id] ?? 0),
                'completed' => (int) ($completed[$id] ?? 0),
                'on_time' => (int) ($onTime[$id] ?? 0),
            ];
        }

        return $out;
    }

    /*
    |--------------------------------------------------------------------------
    | Individual profile
    |--------------------------------------------------------------------------
    */

    public function profile(User $employee): array
    {
        return [
            'name' => $employee->name,
            'designation' => $employee->designation ?: '—',
            'email' => $employee->email,
            'phone' => $employee->phone ?: '—',
            'department' => $employee->department ?: '—',
            'shift' => $employee->shift ?: '—',
            'joined' => $employee->created_at->format('d-m-Y'),
            'status' => $employee->status,
        ];
    }

    /**
     * keys: completed, on_time, late, active, pending, revisions, avg_hours
     */
    public function totals(User $employee): array
    {
        $base = fn () => OrderTask::query()->forLiveOrder()->forEmployee($employee->id);

        $completed = (clone $base())->whereNotNull('completed_at')->count();
        $onTime = (clone $base())->whereNotNull('completed_at')
            ->whereColumn('completed_at', '<=', 'deadline_at')->count();

        // Average minutes actually taken, in hours to one decimal.
        $avgMinutes = (clone $base())->whereNotNull('completed_at')
            ->select(DB::raw('AVG(TIMESTAMPDIFF(MINUTE, assigned_at, completed_at)) m'))
            ->value('m');

        return [
            'completed' => $completed,
            'on_time' => $onTime,
            'late' => $completed - $onTime,
            'active' => (clone $base())->open()->count(),
            // Handed over but not started yet.
            'pending' => (clone $base())->where('status', 'assigned')->count(),
            'revisions' => (clone $base())->where('task_type', 'revision')->count(),
            'avg_hours' => round(((float) $avgMinutes) / 60, 1),
        ];
    }

    public function score(array $totals): int
    {
        return $totals['completed'] > 0
            ? (int) round($totals['on_time'] / $totals['completed'] * 100)
            : 0;
    }

    /**
     * The seven-day column chart: on-time vs late completions per day.
     * keys: day, on_time, late
     */
    public function week(User $employee): Collection
    {
        $from = today()->subDays(6);

        $rows = OrderTask::query()
            ->forLiveOrder()
            ->forEmployee($employee->id)
            ->whereNotNull('completed_at')
            ->where('completed_at', '>=', $from)
            ->select(
                DB::raw('DATE(completed_at) d'),
                DB::raw('SUM(completed_at <= deadline_at) on_time'),
                DB::raw('SUM(completed_at > deadline_at) late'),
            )
            ->groupBy('d')
            ->get()
            ->keyBy(fn ($row) => (string) $row->d);

        // Build all seven days so the chart never has gaps.
        return collect(range(0, 6))->map(function (int $offset) use ($from, $rows) {
            $day = $from->copy()->addDays($offset);
            $row = $rows[$day->toDateString()] ?? null;

            return [
                'day' => $day->format('D'),
                'on_time' => (int) ($row->on_time ?? 0),
                'late' => (int) ($row->late ?? 0),
            ];
        });
    }

    /** keys: order, design, type, assigned_ago, duration */
    public function currentTasks(User $employee): Collection
    {
        return OrderTask::query()
            ->forLiveOrder()
            ->forEmployee($employee->id)
            ->open()
            ->with('order:id,order_number,design_name')
            ->orderBy('deadline_at')
            ->get()
            ->map(fn (OrderTask $task) => [
                'order' => $task->order?->order_number ?? '—',
                'design' => $task->order?->design_name ?? '—',
                'type' => $task->task_type->label(),
                'assigned_ago' => (int) $task->assigned_at->diffInMinutes(now()),
                'duration' => $task->duration_hours,
            ]);
    }

    /** keys: order, design, allowed (hours), taken (minutes), finished */
    public function recentlyCompleted(User $employee, int $limit = 6): Collection
    {
        return OrderTask::query()
            ->forLiveOrder()
            ->forEmployee($employee->id)
            ->whereNotNull('completed_at')
            ->with('order:id,order_number,design_name')
            ->latest('completed_at')
            ->limit($limit)
            ->get()
            ->map(fn (OrderTask $task) => [
                'order' => $task->order?->order_number ?? '—',
                'design' => $task->order?->design_name ?? '—',
                'allowed' => $task->duration_hours,
                'taken' => (int) $task->assigned_at->diffInMinutes($task->completed_at),
                'finished' => $task->completed_at->format('d-m-Y H:i'),
            ]);
    }

    /**
     * Recent activity, straight from the audit trail this employee generated.
     * keys: title, meta, tone
     */
    public function timeline(User $employee, int $limit = 6): Collection
    {
        return OrderActivity::query()
            ->where('user_id', $employee->id)
            ->with('order:id,order_number')
            ->latest()
            ->limit($limit)
            ->get()
            ->map(fn (OrderActivity $activity) => [
                'title' => $activity->description ?: $activity->event,
                'meta' => $activity->order?->order_number
                    ? $activity->order->order_number.' · '.$activity->created_at->diffForHumans()
                    : $activity->created_at->diffForHumans(),
                'tone' => $activity->tone(),
            ]);
    }

    /** Everything the profile page needs, in one call. */
    public function fullProfile(User $employee): array
    {
        $totals = $this->totals($employee);
        $week = $this->week($employee);

        return [
            'employee' => $this->profile($employee),
            'totals' => $totals,
            'score' => $this->score($totals),
            'latePc' => 100 - $this->score($totals),
            'week' => $week,
            // max(1) so the chart's height maths never divides by zero.
            'peak' => max(1, $week->max(fn (array $day) => $day['on_time'] + $day['late'])),
            'current' => $this->currentTasks($employee),
            'recent' => $this->recentlyCompleted($employee),
            'timeline' => $this->timeline($employee),
        ];
    }
}
