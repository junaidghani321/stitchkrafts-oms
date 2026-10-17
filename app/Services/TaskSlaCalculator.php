<?php

namespace App\Services;

use App\Enums\TaskSlaStatus;
use App\Models\OrderTask;
use Illuminate\Support\Carbon;

/**
 * The single home for the SLA maths that is currently duplicated across
 * admin/tasks, manager/tasks, employee/tasks, employee/revisions and
 * admin/employees/show.
 *
 * The logic is a faithful port of the block in those views:
 *
 *     $allowed = $task['duration'] * 60;
 *     if ($task['completed_after'] !== null) {
 *         $status  = $task['completed_after'] <= $allowed ? 'on_time' : 'late';
 *         $percent = min(100, (int) round($task['completed_after'] / $allowed * 100));
 *         $delta   = $allowed - $task['completed_after'];
 *     } else {
 *         $status  = $task['assigned_ago'] <= $allowed ? 'running' : 'overdue';
 *         $percent = min(100, (int) round($task['assigned_ago'] / $allowed * 100));
 *         $delta   = $allowed - $task['assigned_ago'];
 *     }
 */
class TaskSlaCalculator
{
    public function for(OrderTask $task): TaskSla
    {
        return $this->make(
            durationHours: $task->duration_hours,
            assignedAt: $task->assigned_at,
            completedAt: $task->completed_at,
            deadlineAt: $task->deadline_at,
        );
    }

    /**
     * The same calculation from raw parts, for callers that have not loaded a
     * model (the employee revisions screen, for one).
     */
    public function make(
        int $durationHours,
        Carbon $assignedAt,
        ?Carbon $completedAt = null,
        ?Carbon $deadlineAt = null,
    ): TaskSla {
        // A zero duration would divide by zero in the percent calculation.
        // Validation rejects it, but a legacy or hand-edited row must not
        // take a page down.
        $allowed = max(1, $durationHours) * 60;

        // Prefer the stored deadline so an extension is reflected; fall back
        // to the derived value, which is what the views assume.
        $deadline = $deadlineAt ?? $assignedAt->copy()->addMinutes($allowed);

        $assignedAgo = (int) $assignedAt->diffInMinutes(now());
        $completedAfter = $completedAt ? (int) $assignedAt->diffInMinutes($completedAt) : null;

        if ($completedAfter !== null) {
            $status = $completedAfter <= $allowed ? TaskSlaStatus::OnTime : TaskSlaStatus::Late;
            $percent = min(100, (int) round($completedAfter / $allowed * 100));
            $delta = $allowed - $completedAfter;
        } else {
            $status = $assignedAgo <= $allowed ? TaskSlaStatus::Running : TaskSlaStatus::Overdue;
            $percent = min(100, (int) round($assignedAgo / $allowed * 100));
            $delta = $allowed - $assignedAgo;
        }

        return new TaskSla(
            allowed: $allowed,
            assignedAt: $assignedAt,
            deadline: $deadline,
            assignedAgo: $assignedAgo,
            completedAfter: $completedAfter,
            status: $status,
            percent: $percent,
            delta: $delta,
        );
    }

    /**
     * On-time rate across a set of tasks, as a whole percentage.
     * Drives the "On-Time Rate" tile and the employee performance score.
     *
     * @param  iterable<OrderTask>  $tasks
     */
    public function onTimeRate(iterable $tasks): int
    {
        $finished = 0;
        $onTime = 0;

        foreach ($tasks as $task) {
            $sla = $task instanceof OrderTask ? $task->sla() : $task;

            if (! $sla->isFinished()) {
                continue;
            }

            $finished++;

            if ($sla->status === TaskSlaStatus::OnTime) {
                $onTime++;
            }
        }

        return $finished > 0 ? (int) round($onTime / $finished * 100) : 0;
    }
}
