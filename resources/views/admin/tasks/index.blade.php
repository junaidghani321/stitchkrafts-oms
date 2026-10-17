@extends('layouts.app')

@section('title', 'Tasks')
@section('page_title', 'Task Management')
@section('page_heading', 'Task Management')

@section('content')

@php
    /* $tasks and $employees come from the controller via OrderRowPresenter.
     |
     | assigned_ago    – minutes since the task was handed to the employee
     | duration        – hours allowed for the task
     | completed_after – minutes actually taken, or null while still running
     */
    $now = now();

    /* Tasks have five types, not three: revision and quality_check are
       work that is not itself an order type. */
    $typeLabels = App\Enums\TaskType::options();

    $statusMeta = [
        'running' => ['label' => 'In Progress',        'badge' => 'progress', 'bar' => 'warn'],
        'overdue' => ['label' => 'Overdue',            'badge' => 'unpaid',   'bar' => 'bad'],
        'on_time' => ['label' => 'Completed On Time',  'badge' => 'done',     'bar' => 'good'],
        'late'    => ['label' => 'Completed Late',     'badge' => 'unpaid',   'bar' => 'bad'],
    ];

    /* Turn "3h 05m" out of a minute count. */
    $asDuration = function ($minutes) {
        $minutes = (int) abs($minutes);
        return intdiv($minutes, 60) . 'h ' . str_pad((string) ($minutes % 60), 2, '0', STR_PAD_LEFT) . 'm';
    };

    /* Derive deadline, progress and status for every row up front. */
    $rows = [];

    foreach ($tasks as $task) {
        $allowed    = $task['duration'] * 60;
        $assignedAt = $now->copy()->subMinutes($task['assigned_ago']);
        $deadline   = $assignedAt->copy()->addMinutes($allowed);

        if ($task['completed_after'] !== null) {
            $status  = $task['completed_after'] <= $allowed ? 'on_time' : 'late';
            $percent = min(100, (int) round($task['completed_after'] / $allowed * 100));
            $delta   = $allowed - $task['completed_after'];
        } else {
            $status  = $task['assigned_ago'] <= $allowed ? 'running' : 'overdue';
            $percent = min(100, (int) round($task['assigned_ago'] / $allowed * 100));
            $delta   = $allowed - $task['assigned_ago'];
        }

        $rows[] = $task + [
            'allowed'  => $allowed,
            'assigned' => $assignedAt,
            'deadline' => $deadline,
            'status'   => $status,
            'percent'  => $percent,
            'delta'    => $delta,
        ];
    }

    $active    = collect($rows)->whereIn('status', ['running', 'overdue']);
    $finished  = collect($rows)->whereIn('status', ['on_time', 'late']);
    $onTime    = $finished->where('status', 'on_time')->count();
    $onTimePct = $finished->count() ? (int) round($onTime / $finished->count() * 100) : 0;
@endphp

    {{-- Summary --}}
    <div class="row g-3 mb-3">
        <div class="col-12 col-md-6 col-xl-3">
            <div class="oms-stat">
                <div class="oms-stat__icon"><i class="fa-solid fa-list-check"></i></div>
                <div>
                    <div class="oms-stat__label">Active Tasks</div>
                    <div class="oms-stat__value">{{ $active->count() }}</div>
                </div>
            </div>
        </div>

        <div class="col-12 col-md-6 col-xl-3">
            <div class="oms-stat">
                <div class="oms-stat__icon oms-stat__icon--bad"><i class="fa-solid fa-triangle-exclamation"></i></div>
                <div>
                    <div class="oms-stat__label">Overdue</div>
                    <div class="oms-stat__value">{{ $active->where('status', 'overdue')->count() }}</div>
                </div>
            </div>
        </div>

        <div class="col-12 col-md-6 col-xl-3">
            <div class="oms-stat">
                <div class="oms-stat__icon oms-stat__icon--good"><i class="fa-solid fa-circle-check"></i></div>
                <div>
                    <div class="oms-stat__label">Completed</div>
                    <div class="oms-stat__value">{{ $finished->count() }}</div>
                </div>
            </div>
        </div>

        <div class="col-12 col-md-6 col-xl-3">
            <div class="oms-stat">
                <div class="oms-stat__icon oms-stat__icon--accent"><i class="fa-solid fa-stopwatch"></i></div>
                <div>
                    <div class="oms-stat__label">On-Time Rate</div>
                    <div class="oms-stat__value">{{ $onTimePct }}%</div>
                </div>
            </div>
        </div>
    </div>

    {{-- Task list --}}
    <div class="card">
        <div class="card-header d-flex flex-wrap align-items-center justify-content-between gap-2">
            <span>Assigned Tasks</span>
            <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#assignTaskModal">
                <i class="fa-solid fa-plus me-1"></i> Assign Task
            </button>
        </div>
        <div class="card-body p-3 p-md-4" data-oms-table>

            <div class="oms-table-toolbar">
                <div class="oms-table-filters">
                    <label>
                        Show
                        <select class="form-select" data-oms-entries>
                            <option value="10">10</option>
                            <option value="25">25</option>
                            <option value="50">50</option>
                        </select>
                        entries
                    </label>

                    <label>
                        Employee
                        <select class="form-select" data-oms-filter="employee">
                            <option value="">All</option>
                            @foreach ($employees as $employee)
                                <option value="{{ $employee->id }}">{{ $employee->name }}</option>
                            @endforeach
                        </select>
                    </label>

                    <label>
                        Status
                        <select class="form-select" data-oms-filter="status">
                            <option value="">All</option>
                            @foreach ($statusMeta as $value => $meta)
                                <option value="{{ $value }}">{{ $meta['label'] }}</option>
                            @endforeach
                        </select>
                    </label>
                </div>

                <label>
                    Search:
                    <input type="search" class="form-control" data-oms-search placeholder="Type to filter…">
                </label>
            </div>

            <div class="oms-table-wrap">
                <table class="oms-table">
                    <thead>
                        <tr>
                            <th>Order #</th>
                            <th>Design Name</th>
                            <th>Type</th>
                            <th>Employee</th>
                            <th>Assigned</th>
                            <th>Allowed</th>
                            <th>Deadline</th>
                            <th>Time Left</th>
                            <th>Progress</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($rows as $row)
                            <tr data-row data-status="{{ $row['status'] }}" data-employee="{{ $row['employee_id'] }}">
                                <td>{{ $row['order'] }}</td>
                                <td>{{ $row['design'] }}</td>
                                <td>{{ $typeLabels[$row['type']] }}</td>
                                <td>{{ $row['employee'] }}</td>
                                <td>{{ $row['assigned']->format('d-m-Y H:i') }}</td>
                                <td>{{ $row['duration'] }}h</td>
                                <td>{{ $row['deadline']->format('d-m-Y H:i') }}</td>
                                <td>
                                    @if ($row['status'] === 'running' || $row['status'] === 'overdue')
                                        {{-- Ticks live via oms-timer.js --}}
                                        <span class="oms-timer" data-oms-countdown="{{ $row['deadline']->getTimestamp() }}">
                                            {{ $asDuration($row['delta']) }}
                                        </span>
                                    @elseif ($row['status'] === 'on_time')
                                        <span class="oms-timer" style="color:#146c43">{{ $asDuration($row['delta']) }} early</span>
                                    @else
                                        <span class="oms-timer oms-timer--danger">{{ $asDuration($row['delta']) }} over</span>
                                    @endif
                                </td>
                                <td style="min-width:120px">
                                    <div class="oms-progress">
                                        <div class="oms-progress__bar oms-progress__bar--{{ $statusMeta[$row['status']]['bar'] }}"
                                             style="width: {{ $row['percent'] }}%"></div>
                                    </div>
                                    <small class="text-muted">{{ $row['percent'] }}% of allowed time</small>
                                </td>
                                <td>
                                    <span class="oms-badge oms-badge--{{ $statusMeta[$row['status']]['badge'] }}">
                                        {{ $statusMeta[$row['status']]['label'] }}
                                    </span>
                                </td>
                                <td>
                                    <div class="oms-actions">
                                        <a href="{{ route('admin.orders.show', $row['order_id']) }}" class="oms-action" title="View task" aria-label="View task"><i class="fa-solid fa-eye"></i></a>
                                        @if ($row['status'] === 'running' || $row['status'] === 'overdue')
                                            <button type="button" class="oms-action" title="Extend time" aria-label="Extend time"
                                                    data-bs-toggle="modal" data-bs-target="#extendTaskModal"
                                                    data-action="{{ route('admin.tasks.extend', $row['id']) }}"
                                                    data-order="{{ $row['order'] }}"
                                                    data-design="{{ $row['design'] }}"
                                                    data-allowed="{{ $row['duration'] }}">
                                                <i class="fa-solid fa-clock-rotate-left"></i>
                                            </button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach

                        <tr data-oms-empty hidden>
                            <td colspan="11" class="oms-table-empty">No data available in table</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="oms-table-foot">
                <div data-oms-info></div>
                <div class="oms-pager" data-oms-pager></div>
            </div>

        </div>
    </div>


    {{-- Assign + extend are now separate dialogs: extending a deadline is a
         duration change, not a re-assignment. --}}
    @include('orders.partials._assign_task_modal', [
        'action'    => route('admin.tasks.store'),
        'employees' => $employees,
        'orders'    => $orders,
    ])

    @include('orders.partials._extend_task_modal')

@endsection
