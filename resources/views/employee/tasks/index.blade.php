@extends('layouts.app')

@section('title', 'My Tasks')
@section('page_title', 'My Tasks')
@section('page_heading', 'My Tasks')

@section('content')

@php
    /* $tasks comes from Employee\TaskController@index via OrderRowPresenter.
     |
     | assigned_ago    – minutes since the task was assigned
     | duration        – hours allowed for the task
     | completed_after – minutes actually taken, or null while still running
     */
    $now = now();

    /* Tasks have five types, not three: revision and quality_check are
       work that is not itself an order type. */
    $typeLabels = App\Enums\TaskType::options();

    $statusMeta = [
        'running' => ['label' => 'In Progress',       'badge' => 'progress', 'bar' => 'warn'],
        'overdue' => ['label' => 'Overdue',           'badge' => 'unpaid',   'bar' => 'bad'],
        'on_time' => ['label' => 'Completed On Time', 'badge' => 'done',     'bar' => 'good'],
        'late'    => ['label' => 'Completed Late',    'badge' => 'unpaid',   'bar' => 'bad'],
    ];

    $asDuration = function ($minutes) {
        $minutes = (int) abs($minutes);
        return intdiv($minutes, 60) . 'h ' . str_pad((string) ($minutes % 60), 2, '0', STR_PAD_LEFT) . 'm';
    };

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

    $open      = collect($rows)->whereIn('status', ['running', 'overdue']);
    $finished  = collect($rows)->whereIn('status', ['on_time', 'late']);
    $onTime    = $finished->where('status', 'on_time')->count();
    $onTimePct = $finished->count() ? (int) round($onTime / $finished->count() * 100) : 0;
@endphp

    {{-- Summary --}}
    <div class="row g-3 mb-3">
        <div class="col-12 col-md-6 col-xl-3">
            <div class="oms-stat">
                <div class="oms-stat__icon oms-stat__icon--accent"><i class="fa-solid fa-list-check"></i></div>
                <div>
                    <div class="oms-stat__label">Open Tasks</div>
                    <div class="oms-stat__value">{{ $open->count() }}</div>
                </div>
            </div>
        </div>

        <div class="col-12 col-md-6 col-xl-3">
            <div class="oms-stat">
                <div class="oms-stat__icon oms-stat__icon--bad"><i class="fa-solid fa-triangle-exclamation"></i></div>
                <div>
                    <div class="oms-stat__label">Overdue</div>
                    <div class="oms-stat__value">{{ $open->where('status', 'overdue')->count() }}</div>
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
                <div class="oms-stat__icon oms-stat__icon--good"><i class="fa-solid fa-stopwatch"></i></div>
                <div>
                    <div class="oms-stat__label">On-Time Rate</div>
                    <div class="oms-stat__value">{{ $onTimePct }}%</div>
                </div>
            </div>
        </div>
    </div>

    {{-- Task list --}}
    <div class="card">
        <div class="card-header">Tasks Assigned To Me</div>
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
                            <th>Allowed</th>
                            <th>Time Left</th>
                            <th>Progress</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($rows as $row)
                            <tr data-row data-status="{{ $row['status'] }}">
                                <td>
                                    {{ $row['order'] }}
                                    @if ($row['priority'] === 'urgent')
                                        <span class="oms-badge oms-badge--urgent ms-1">Urgent</span>
                                    @endif
                                </td>
                                <td>{{ $row['design'] }}</td>
                                <td>{{ $typeLabels[$row['type']] }}</td>
                                <td>{{ $row['duration'] }}h</td>
                                <td>
                                    @if ($row['status'] === 'running' || $row['status'] === 'overdue')
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
                                        @if ($row['status'] === 'running' || $row['status'] === 'overdue')
                                            <button type="button" class="oms-action" title="Mark as complete" aria-label="Mark as complete"
                                                    data-bs-toggle="modal" data-bs-target="#completeTaskModal"
                                                    data-action="{{ route('employee.tasks.complete', $row['id']) }}"
                                                    data-order="{{ $row['order'] }}"
                                                    data-design="{{ $row['design'] }}"
                                                    data-instructions="{{ $row['instructions'] }}">
                                                <i class="fa-solid fa-check"></i>
                                            </button>
                                        @else
                                            <span class="text-muted small">—</span>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach

                        <tr data-oms-empty hidden>
                            <td colspan="8" class="oms-table-empty">No data available in table</td>
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

    {{-- Complete a task --}}
    <div class="modal fade" id="completeTaskModal" tabindex="-1" aria-labelledby="completeTaskModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">

                <div class="modal-header">
                    <h5 class="modal-title" id="completeTaskModalLabel">Mark Task as Complete</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                {{-- TODO: point the action at the update route once the backend lands. --}}
                <form action="#" method="POST" enctype="multipart/form-data" id="completeTaskForm">
                    @csrf

                    <div class="modal-body">

                        <div class="oms-review mb-3">
                            <div class="oms-meter__label">Order</div>
                            <div class="fw-semibold mb-2" data-task-order>—</div>
                            <div class="oms-meter__label">Instructions</div>
                            <div class="small" data-task-instructions>—</div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label" for="complete_files">Completed Files</label>
                            <label class="oms-dropzone" for="complete_files">
                                <i class="fa-solid fa-cloud-arrow-up"></i>
                                <span class="oms-dropzone__title">Click to upload the finished files</span>
                                <span class="oms-dropzone__hint">Multiple files allowed</span>
                            </label>
                            <input type="file" class="d-none" id="complete_files" name="files[]" multiple>
                            <ul class="oms-filelist" data-task-files hidden></ul>
                        </div>

                        <div>
                            <label class="form-label" for="complete_note">Completion Note</label>
                            <textarea class="form-control" id="complete_note" name="note" rows="3"
                                      placeholder="Anything the reviewer should know"></textarea>
                        </div>

                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-brand" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Mark as Complete</button>
                    </div>
                </form>

            </div>
        </div>
    </div>

@endsection

@push('scripts')
<script>
    (function () {
        var modal = document.getElementById('completeTaskModal');
        var completeForm = modal ? modal.querySelector('#completeTaskForm') : null;
        if (!modal) return;

        var files    = modal.querySelector('#complete_files');
        var fileList = modal.querySelector('[data-task-files]');

        modal.addEventListener('show.bs.modal', function (event) {
            var trigger = event.relatedTarget;
            if (!trigger) return;

            modal.querySelector('[data-task-order]').textContent =
                trigger.getAttribute('data-order') + ' — ' + trigger.getAttribute('data-design');
            modal.querySelector('[data-task-instructions]').textContent = trigger.getAttribute('data-instructions');

            // Route binding carries the task id, so there is no hidden field to mismatch.
            if (completeForm) completeForm.action = trigger.getAttribute('data-action') || '#';
        });

        files.addEventListener('change', function () {
            fileList.innerHTML = '';
            fileList.hidden    = files.files.length === 0;

            Array.prototype.forEach.call(files.files, function (file) {
                var item = document.createElement('li');
                var size = (file.size / 1024).toFixed(0);

                item.innerHTML = '<i class="fa-regular fa-file"></i>';
                item.appendChild(document.createTextNode(file.name + ' (' + size + ' KB)'));
                fileList.appendChild(item);
            });
        });
    })();
</script>
@endpush
